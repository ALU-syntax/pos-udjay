<?php

namespace Tests\Feature\B2b;

use App\Models\B2b\Contract;
use App\Services\B2b\PricingService;

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
        $customer = $this->customer();

        $item = $this->quoteOne($customer, $variant, 3);

        $this->assertNull($item['rule']);
        $this->assertSame(12000, $item['final_price']);
        $this->assertSame(36000, $item['line_total']);
    }

    public function test_most_specific_rule_wins_over_broader_rules(): void
    {
        $outlet = $this->outlet();
        $category = $this->category();
        $variant = $this->variant($outlet, 10000, 100, $category);
        $customer = $this->customer();
        $this->contract($customer, [
            ['scope' => 'all', 'discount_type' => 'percent', 'discount_value' => 5],
            ['scope' => 'category', 'category_id' => $category->id, 'discount_type' => 'percent', 'discount_value' => 10],
            ['scope' => 'product', 'product_id' => $variant->product_id, 'outlet_id' => $outlet->id, 'discount_type' => 'nominal', 'discount_value' => 1500],
            ['scope' => 'variant', 'variant_id' => $variant->id, 'product_id' => $variant->product_id, 'outlet_id' => $outlet->id, 'discount_type' => 'fixed_price', 'discount_value' => 7000],
        ]);

        $item = $this->quoteOne($customer, $variant, 1);

        $this->assertSame('variant', $item['rule']->scope);
        $this->assertSame(3000, $item['discount_per_unit']);
        $this->assertSame(7000, $item['final_price']);
    }

    public function test_tiered_min_qty_picks_the_largest_tier_not_above_qty(): void
    {
        $outlet = $this->outlet();
        $variant = $this->variant($outlet, 10000);
        $customer = $this->customer();
        $this->contract($customer, [
            ['scope' => 'product', 'product_id' => $variant->product_id, 'outlet_id' => $outlet->id, 'discount_value' => 5, 'min_qty' => 1],
            ['scope' => 'product', 'product_id' => $variant->product_id, 'outlet_id' => $outlet->id, 'discount_value' => 10, 'min_qty' => 10],
            ['scope' => 'product', 'product_id' => $variant->product_id, 'outlet_id' => $outlet->id, 'discount_value' => 15, 'min_qty' => 50],
        ]);

        $this->assertSame(9500, $this->quoteOne($customer, $variant, 9)['final_price']);
        $this->assertSame(9000, $this->quoteOne($customer, $variant, 10)['final_price']);
        $this->assertSame(8500, $this->quoteOne($customer, $variant, 80)['final_price']);
    }

    public function test_falls_back_to_broader_rule_when_specific_tier_is_not_reached(): void
    {
        $outlet = $this->outlet();
        $variant = $this->variant($outlet, 10000);
        $customer = $this->customer();
        $this->contract($customer, [
            ['scope' => 'all', 'discount_value' => 5],
            ['scope' => 'product', 'product_id' => $variant->product_id, 'outlet_id' => $outlet->id, 'discount_value' => 20, 'min_qty' => 100],
        ]);

        $item = $this->quoteOne($customer, $variant, 2);

        $this->assertSame('all', $item['rule']->scope);
        $this->assertSame(9500, $item['final_price']);
    }

    public function test_outlet_specific_rule_beats_all_outlet_rule_and_other_outlet_rules_are_ignored(): void
    {
        $outlet = $this->outlet('A');
        $otherOutlet = $this->outlet('B');
        $category = $this->category();
        $variant = $this->variant($outlet, 10000, 100, $category);
        $customer = $this->customer();
        $this->contract($customer, [
            ['scope' => 'category', 'category_id' => $category->id, 'discount_value' => 10],
            ['scope' => 'category', 'category_id' => $category->id, 'outlet_id' => $outlet->id, 'discount_value' => 12],
            ['scope' => 'category', 'category_id' => $category->id, 'outlet_id' => $otherOutlet->id, 'discount_value' => 50],
        ]);

        $this->assertSame(8800, $this->quoteOne($customer, $variant, 1)['final_price']);
    }

    public function test_discount_never_goes_below_zero_or_raises_price(): void
    {
        $outlet = $this->outlet();
        $cheap = $this->variant($outlet, 3000, 100, null, 'Air');
        $pricey = $this->variant($outlet, 3000, 100, null, 'Teh');
        $customer = $this->customer();
        $this->contract($customer, [
            ['scope' => 'variant', 'variant_id' => $cheap->id, 'discount_type' => 'nominal', 'discount_value' => 5000],
            ['scope' => 'variant', 'variant_id' => $pricey->id, 'discount_type' => 'fixed_price', 'discount_value' => 4000],
        ]);

        $this->assertSame(0, $this->quoteOne($customer, $cheap, 1)['final_price']);
        $this->assertSame(3000, $this->quoteOne($customer, $pricey, 1)['final_price']);
    }

    public function test_percent_discount_is_rounded_to_rupiah(): void
    {
        $outlet = $this->outlet();
        $variant = $this->variant($outlet, 9999);
        $customer = $this->customer();
        $this->contract($customer, [['scope' => 'all', 'discount_type' => 'percent', 'discount_value' => 12.5]]);

        // 12,5% dari 9.999 = 1.249,875 → 1.250
        $this->assertSame(1250, $this->quoteOne($customer, $variant, 1)['discount_per_unit']);
    }

    public function test_only_effective_active_contract_is_used(): void
    {
        $outlet = $this->outlet();
        $variant = $this->variant($outlet, 10000);
        $customer = $this->customer();
        $this->contract($customer, [['discount_value' => 50]], ['status' => Contract::STATUS_DRAFT]);
        $this->contract($customer, [['discount_value' => 40]], [
            'start_date' => today()->subMonths(3), 'end_date' => today()->subMonths(2), 'status' => Contract::STATUS_ACTIVE,
        ]);
        $this->contract($customer, [['discount_value' => 30]], [
            'start_date' => today()->addDays(30), 'end_date' => today()->addDays(60), 'status' => Contract::STATUS_ACTIVE,
        ]);

        $this->assertSame(10000, $this->quoteOne($customer, $variant, 1)['final_price']);
    }

    public function test_variant_from_another_outlet_is_rejected(): void
    {
        $outlet = $this->outlet('A');
        $otherVariant = $this->variant($this->outlet('B'));
        $customer = $this->customer();

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(PricingService::class)->quote($customer, $outlet->id, [['variant_id' => $otherVariant->id, 'qty' => 1]]);
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
