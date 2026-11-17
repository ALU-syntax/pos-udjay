<?php

namespace Tests\Feature\B2b;

use App\Models\B2b\Contract;
use App\Models\VariantProduct;
use App\Services\B2b\PricingService;
use Illuminate\Validation\ValidationException;

class PricingServiceTest extends B2bTestCase
{
    private function quoteOne($customer, $variant, int $qty): array
    {
        return app(PricingService::class)
            ->quote($customer, $variant->product->outlet_id, [['variant_id' => $variant->id, 'qty' => $qty]])['items'][0];
    }

    public function test_customer_without_contract_gets_normal_price(): void
    {
        $outlet = $this->outlet();
        $variant = $this->variant($outlet, 12000);

        $item = $this->quoteOne($this->customer(), $variant, 3);

        $this->assertNull($item['contract_item']);
        $this->assertSame(12000, $item['final_price']);
        $this->assertSame(36000, $item['line_total']);
    }

    public function test_contract_sets_price_per_variant_and_other_items_stay_normal(): void
    {
        $outlet = $this->outlet();
        $a = $this->variant($outlet, 40000, 100, null, 'A');
        $b = $this->variant($outlet, 30000, 100, null, 'B');
        $c = $this->variant($outlet, 35000, 100, null, 'C');
        $customer = $this->customer();
        $this->contract($customer, [
            ['variant_id' => $a->id, 'price' => 38000],
            ['variant_id' => $b->id, 'price' => 27000],
        ]);

        $quote = app(PricingService::class)->quote($customer, $outlet->id, [
            ['variant_id' => $a->id, 'qty' => 2],
            ['variant_id' => $b->id, 'qty' => 1],
            ['variant_id' => $c->id, 'qty' => 1],
        ]);

        $this->assertSame([38000, 27000, 35000], array_column($quote['items'], 'final_price'));
        $this->assertSame([40000, 30000, 35000], array_column($quote['items'], 'base_price'));
        $this->assertNull($quote['items'][2]['b2b_contract_item_id']);
        $this->assertSame(138000, $quote['subtotal']);
        $this->assertSame(138000, $quote['grand_total']);
    }

    public function test_variants_of_the_same_product_can_have_different_prices(): void
    {
        $outlet = $this->outlet();
        $regular = $this->variant($outlet, 20000, 100, null, 'Kopi Susu');
        $large = VariantProduct::create(['product_id' => $regular->product_id, 'name' => 'Large', 'harga' => 25000, 'stok' => 100])->load('product');
        $customer = $this->customer();
        $this->contract($customer, [
            ['variant_id' => $regular->id, 'price' => 18000],
            ['variant_id' => $large->id, 'price' => 23000],
        ]);

        $this->assertSame(18000, $this->quoteOne($customer, $regular, 1)['final_price']);
        $this->assertSame(23000, $this->quoteOne($customer, $large, 1)['final_price']);
    }

    public function test_tier_with_largest_min_qty_not_above_qty_is_used(): void
    {
        $outlet = $this->outlet();
        $variant = $this->variant($outlet, 40000);
        $customer = $this->customer();
        $this->contract($customer, [
            ['variant_id' => $variant->id, 'min_qty' => 1, 'price' => 38000],
            ['variant_id' => $variant->id, 'min_qty' => 10, 'price' => 36000],
            ['variant_id' => $variant->id, 'min_qty' => 50, 'price' => 35000],
        ]);

        $this->assertSame(38000, $this->quoteOne($customer, $variant, 9)['final_price']);
        $tier = $this->quoteOne($customer, $variant, 10);
        $this->assertSame(36000, $tier['final_price']);
        $this->assertSame(10, $tier['min_qty']);
        $this->assertSame(35000, $this->quoteOne($customer, $variant, 80)['final_price']);
    }

    public function test_qty_below_every_tier_falls_back_to_normal_price(): void
    {
        $outlet = $this->outlet();
        $variant = $this->variant($outlet, 40000);
        $customer = $this->customer();
        $this->contract($customer, [['variant_id' => $variant->id, 'min_qty' => 10, 'price' => 36000]]);

        $item = $this->quoteOne($customer, $variant, 5);

        $this->assertNull($item['contract_item']);
        $this->assertSame(40000, $item['final_price']);
    }

    public function test_contract_price_above_normal_price_is_used_as_is(): void
    {
        $outlet = $this->outlet();
        $variant = $this->variant($outlet, 30000);
        $customer = $this->customer();
        $this->contract($customer, [['variant_id' => $variant->id, 'price' => 32000]]);

        $this->assertSame(32000, $this->quoteOne($customer, $variant, 1)['final_price']);
    }

    public function test_one_contract_holds_prices_for_several_outlets(): void
    {
        $outletA = $this->outlet('A');
        $outletB = $this->outlet('B');
        $variantA = $this->variant($outletA, 40000);
        $variantB = $this->variant($outletB, 40000);
        $customer = $this->customer();
        $this->contract($customer, [
            ['variant_id' => $variantA->id, 'price' => 38000],
            ['variant_id' => $variantB->id, 'price' => 39000],
        ]);

        $this->assertSame(38000, $this->quoteOne($customer, $variantA, 1)['final_price']);
        $this->assertSame(39000, $this->quoteOne($customer, $variantB, 1)['final_price']);
    }

    public function test_only_running_contract_and_existing_items_are_used(): void
    {
        $outlet = $this->outlet();
        $variant = $this->variant($outlet, 10000);
        $customer = $this->customer();
        $this->contract($customer, [['variant_id' => $variant->id, 'price' => 5000]], ['status' => Contract::STATUS_TERMINATED]);
        $this->contract($customer, [['variant_id' => $variant->id, 'price' => 6000]], [
            'start_date' => today()->subMonths(3), 'end_date' => today()->subMonths(2),
        ]);
        $this->contract($customer, [['variant_id' => $variant->id, 'price' => 7000]], [
            'start_date' => today()->addDays(30), 'end_date' => today()->addDays(60),
        ]);
        $this->contract($customer, [['variant_id' => $variant->id, 'price' => 8000]])->delete();

        $this->assertSame(10000, $this->quoteOne($customer, $variant, 1)['final_price']);

        $running = $this->contract($customer, [['variant_id' => $variant->id, 'price' => 9000]]);
        $this->assertSame(9000, $this->quoteOne($customer, $variant, 1)['final_price']);

        $running->items->first()->delete();
        $this->assertSame(10000, $this->quoteOne($customer, $variant, 1)['final_price']);
    }

    public function test_variant_from_another_outlet_is_rejected(): void
    {
        $outlet = $this->outlet('A');
        $otherVariant = $this->variant($this->outlet('B'));

        $this->expectException(ValidationException::class);
        app(PricingService::class)->quote($this->customer(), $outlet->id, [['variant_id' => $otherVariant->id, 'qty' => 1]]);
    }

    public function test_insufficient_stock_is_flagged_not_blocked(): void
    {
        $outlet = $this->outlet();
        $variant = $this->variant($outlet, 10000, 2);

        $item = $this->quoteOne($this->customer(), $variant, 5);

        $this->assertTrue($item['stock_insufficient']);
        $this->assertSame(50000, $item['line_total']);
    }
}
