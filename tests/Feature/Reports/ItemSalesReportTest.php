<?php

namespace Tests\Feature\Reports;

use App\Models\Category;
use App\Models\Outlets;
use App\Models\PettyCash;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Models\VariantProduct;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ItemSalesReportTest extends TestCase
{
    use DatabaseTransactions;

    public function test_item_sales_columns_sort_globally_using_report_values(): void
    {
        $outlet = Outlets::create([
            'name' => 'Item Sales Outlet',
            'address' => 'Test Address',
            'phone' => '0800000011',
        ]);
        $otherOutlet = Outlets::create([
            'name' => 'Other Item Sales Outlet',
            'address' => 'Other Address',
            'phone' => '0800000012',
        ]);
        $user = User::factory()->create([
            'outlet_id' => [$outlet->id, $otherOutlet->id],
        ]);
        $pettyCash = $this->createPettyCash($outlet, $user);
        $otherPettyCash = $this->createPettyCash($otherOutlet, $user);

        $categoryB = Category::create(['name' => 'Item Sales Sort B']);
        $categoryA = Category::create(['name' => 'Item Sales Sort A']);

        $variantLow = $this->createVariant($outlet, $categoryB, 'Item Sales Sort Alpha', 'Variant Z', 1000);
        $variantHigh = $this->createVariant($outlet, $categoryA, 'Item Sales Sort Beta', 'Variant A', 2000);
        $variantZero = $this->createVariant($outlet, $categoryA, 'Item Sales Sort Gamma', 'Variant M', 3000);
        $otherVariant = $this->createVariant($otherOutlet, $categoryA, 'Item Sales Sort Other', 'Variant A', 9000);

        $transaction = $this->createTransaction($outlet, $user, $pettyCash, '2030-01-02 10:00:00');
        $this->createItems($transaction, $variantLow, 1, 100, '2030-01-02 10:00:00');
        $this->createItems($transaction, $variantHigh, 3, 200, '2030-01-02 10:00:00');

        $outsideDate = $this->createTransaction($outlet, $user, $pettyCash, '2030-02-02 10:00:00');
        $this->createItems($outsideDate, $variantZero, 5, 0, '2030-02-02 10:00:00');

        $otherTransaction = $this->createTransaction($otherOutlet, $user, $otherPettyCash, '2030-01-02 10:00:00');
        $this->createItems($otherTransaction, $otherVariant, 10, 0, '2030-01-02 10:00:00');

        $response = $this->getItemSales($user, $outlet, 2, 'desc', 1);
        $response->assertOk();
        $this->assertSame('Item Sales Sort Beta - Variant A', $response->json('data.0.name'));
        $this->assertSame(3, $response->json('data.0.item_sold'));
        $this->assertSame('Rp. 6.000', $response->json('data.0.gross_sales'));
        $this->assertSame('Rp. 600', $response->json('data.0.discounts'));
        $this->assertSame('Rp. 5.400', $response->json('data.0.net_sales'));

        $nameResponse = $this->getItemSales($user, $outlet, 0, 'asc');
        $this->assertSame([
            'Item Sales Sort Alpha - Variant Z',
            'Item Sales Sort Beta - Variant A',
            'Item Sales Sort Gamma - Variant M',
        ], collect($nameResponse->json('data'))->pluck('name')->values()->all());

        $categoryResponse = $this->getItemSales($user, $outlet, 1, 'asc');
        $this->assertSame([
            'Item Sales Sort Beta - Variant A',
            'Item Sales Sort Gamma - Variant M',
            'Item Sales Sort Alpha - Variant Z',
        ], collect($categoryResponse->json('data'))->pluck('name')->values()->all());

        $zeroRow = collect($nameResponse->json('data'))->firstWhere('name', 'Item Sales Sort Gamma - Variant M');
        $this->assertSame(0, $zeroRow['item_sold']);
        $this->assertSame('Rp. 0', $zeroRow['gross_sales']);
        $this->assertNull(collect($nameResponse->json('data'))->firstWhere('name', 'Item Sales Sort Other - Variant A'));
    }

    private function getItemSales(User $user, Outlets $outlet, int $column, string $direction, int $length = -1)
    {
        $this->app->forgetInstance('datatables');
        $this->app->forgetInstance('datatables.request');

        $columns = collect([
            'name',
            'category',
            'item_sold',
            'gross_sales',
            'discounts',
            'net_sales',
            'gross_profit',
            'gross_margin',
        ])->map(fn ($name) => [
            'data' => $name,
            'name' => $name,
            'searchable' => 'true',
            'orderable' => 'true',
            'search' => ['value' => '', 'regex' => 'false'],
        ])->all();

        return $this->actingAs($user)->getJson('/report/sales/item-sales?'.http_build_query([
            'date' => '2030/01/01 - 2030/01/07',
            'outlet' => $outlet->id,
            'draw' => 1,
            'columns' => $columns,
            'order' => [['column' => $column, 'dir' => $direction]],
            'start' => 0,
            'length' => $length,
            'search' => ['value' => 'Item Sales Sort', 'regex' => 'false'],
        ]));
    }

    private function createPettyCash(Outlets $outlet, User $user): PettyCash
    {
        return PettyCash::create([
            'outlet_id' => (string) $outlet->id,
            'amount_awal' => 100000,
            'user_id_started' => (string) $user->id,
            'open' => '2030-01-01 08:00:00',
        ]);
    }

    private function createVariant(
        Outlets $outlet,
        Category $category,
        string $productName,
        string $variantName,
        int $price
    ): VariantProduct {
        $product = Product::create([
            'name' => $productName,
            'category_id' => $category->id,
            'outlet_id' => $outlet->id,
            'harga_modal' => 0,
            'status' => true,
        ]);

        return VariantProduct::create([
            'name' => $variantName,
            'harga' => $price,
            'stok' => 100,
            'product_id' => $product->id,
        ]);
    }

    private function createTransaction(
        Outlets $outlet,
        User $user,
        PettyCash $pettyCash,
        string $createdAt
    ): Transaction {
        return Transaction::create([
            'outlet_id' => $outlet->id,
            'user_id' => $user->id,
            'patty_cash_id' => $pettyCash->id,
            'total' => 10000,
            'nominal_bayar' => 10000,
            'change' => 0,
            'total_pajak' => '[]',
            'diskon_all_item' => '[]',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function createItems(
        Transaction $transaction,
        VariantProduct $variant,
        int $quantity,
        int $discount,
        string $createdAt
    ): void {
        for ($index = 0; $index < $quantity; $index++) {
            TransactionItem::create([
                'transaction_id' => $transaction->id,
                'product_id' => $variant->product_id,
                'variant_id' => $variant->id,
                'harga' => $variant->harga,
                'modifier_id' => '[]',
                'discount_id' => $discount ? json_encode([['result' => $discount]]) : '[]',
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }
}
