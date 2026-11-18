<?php

namespace Tests\Feature\B2b;

use App\Models\B2b\Contract;
use App\Models\B2b\Customer;
use App\Models\Category;
use App\Models\CategoryPayment;
use App\Models\Outlets;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\VariantProduct;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

abstract class B2bTestCase extends TestCase
{
    use DatabaseTransactions;

    protected function outlet(string $suffix = ''): Outlets
    {
        return Outlets::create([
            'name' => 'B2B Outlet '.$suffix.uniqid(),
            'address' => 'Jl. Test',
            'phone' => '0800000000',
        ]);
    }

    protected function userFor(?Outlets $outlet = null, array $permissions = []): User
    {
        $user = User::factory()->create(['outlet_id' => $outlet ? [$outlet->id] : []]);

        foreach ($permissions as $name) {
            $user->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        }

        return $user;
    }

    protected function category(string $name = 'Kopi'): Category
    {
        return Category::create(['name' => $name.' '.uniqid()]);
    }

    /** Produk dengan satu varian; kembalikan variannya. */
    protected function variant(Outlets $outlet, int $price = 10000, int $stock = 100, ?Category $category = null, string $name = 'Kopi Susu'): VariantProduct
    {
        $product = Product::create([
            'name' => $name,
            'category_id' => $category?->id,
            'outlet_id' => $outlet->id,
            'status' => true,
        ]);

        return VariantProduct::create([
            'product_id' => $product->id,
            'name' => $name,
            'harga' => $price,
            'stok' => $stock,
        ])->load('product');
    }

    protected function customer(array $attributes = []): Customer
    {
        $customer = new Customer(array_merge([
            'type' => 'pt',
            'name' => 'PT Pelanggan '.uniqid(),
            'is_active' => true,
        ], $attributes));
        $customer->code = 'CUS-TEST-'.uniqid();
        $customer->save();

        return $customer;
    }

    /**
     * Kontrak berjalan (default 10 hari lalu s.d. 20 hari lagi) dengan harga per varian.
     *
     * @param  array<int, array{variant_id:int, price:int, min_qty?:int}>  $items
     */
    protected function contract(Customer $customer, array $items = [], array $attributes = []): Contract
    {
        $contract = new Contract;
        $contract->forceFill(array_merge([
            'b2b_customer_id' => $customer->id,
            'start_date' => today()->subDays(10),
            'end_date' => today()->addDays(20),
            'status' => Contract::STATUS_ACTIVE,
        ], $attributes));
        $contract->contract_number = 'KTR-TEST-'.uniqid();
        $contract->save();
        $contract->items()->createMany(array_map(fn (array $item) => array_merge(['min_qty' => 1], $item), $items));

        return $contract->load('items');
    }

    /**
     * Metode bayar di master POS: kategori + sub metode (kosongkan $methods untuk kategori seperti Cash).
     *
     * @return array{0: CategoryPayment, 1: array<string, Payment>}
     */
    protected function paymentCategory(string $name, array $methods = []): array
    {
        $category = CategoryPayment::create(['name' => $name.' '.uniqid(), 'status' => true]);
        $created = [];
        foreach ($methods as $method) {
            $created[$method] = Payment::create(['name' => $method, 'status' => true, 'category_payment_id' => $category->id]);
        }

        return [$category, $created];
    }
}
