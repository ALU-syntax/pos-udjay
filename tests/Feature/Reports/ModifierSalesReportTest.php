<?php

namespace Tests\Feature\Reports;

use App\Models\ModifierGroup;
use App\Models\Modifiers;
use App\Models\Outlets;
use App\Models\PettyCash;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ModifierSalesReportTest extends TestCase
{
    use DatabaseTransactions;

    public function test_report_uses_snapshot_prices_and_keeps_unsold_and_deleted_modifiers(): void
    {
        $outlet = Outlets::create([
            'name' => 'Outlet Modifier Report',
            'address' => 'Test Address',
            'phone' => '0800000001',
        ]);
        $otherOutlet = Outlets::create([
            'name' => 'Other Outlet Modifier Report',
            'address' => 'Other Address',
            'phone' => '0800000002',
        ]);
        $user = User::factory()->create([
            'outlet_id' => [$outlet->id, $otherOutlet->id],
        ]);
        $pettyCash = PettyCash::create([
            'outlet_id' => (string) $outlet->id,
            'amount_awal' => 100000,
            'user_id_started' => (string) $user->id,
            'open' => '2026-09-22 08:00:00',
        ]);
        $otherPettyCash = PettyCash::create([
            'outlet_id' => (string) $otherOutlet->id,
            'amount_awal' => 100000,
            'user_id_started' => (string) $user->id,
            'open' => '2026-09-22 08:00:00',
        ]);

        $group = ModifierGroup::create([
            'name' => 'Topping Test',
            'outlet_id' => (string) $outlet->id,
        ]);
        $soldModifier = Modifiers::create([
            'name' => 'Cheese Current Name',
            'harga' => 9000,
            'modifiers_group_id' => $group->id,
        ]);
        Modifiers::create([
            'name' => 'Unsold Modifier',
            'harga' => 3000,
            'modifiers_group_id' => $group->id,
        ]);
        $deletedModifier = Modifiers::create([
            'name' => 'Deleted Current Name',
            'harga' => 8000,
            'modifiers_group_id' => $group->id,
        ]);
        $emptyGroup = ModifierGroup::create([
            'name' => 'Empty Group',
            'outlet_id' => (string) $outlet->id,
        ]);
        Modifiers::create([
            'name' => 'Empty Child',
            'harga' => 500,
            'modifiers_group_id' => $emptyGroup->id,
        ]);

        $transaction = $this->createTransaction($outlet, $user, $pettyCash, '2026-09-23 10:00:00');
        TransactionItem::create([
            'transaction_id' => $transaction->id,
            'modifier_id' => json_encode([
                ['id' => (string) $soldModifier->id, 'nama' => 'Cheese Historical', 'harga' => 1000],
                ['id' => (string) $deletedModifier->id, 'nama' => 'Deleted Historical', 'harga' => 2000],
            ]),
            'discount_id' => json_encode([
                ['value' => 10],
            ]),
            'harga' => 10000,
            'created_at' => '2026-09-23 10:00:00',
            'updated_at' => '2026-09-23 10:00:00',
        ]);
        $deletedModifier->delete();

        $otherTransaction = $this->createTransaction($otherOutlet, $user, $otherPettyCash, '2026-09-23 11:00:00');
        TransactionItem::create([
            'transaction_id' => $otherTransaction->id,
            'modifier_id' => json_encode([
                ['id' => (string) $soldModifier->id, 'nama' => 'Cheese Historical', 'harga' => 5000],
            ]),
            'discount_id' => '[]',
            'harga' => 10000,
            'created_at' => '2026-09-23 11:00:00',
            'updated_at' => '2026-09-23 11:00:00',
        ]);

        $response = $this->actingAs($user)->getJson('/report/sales/modifier-sales?'.http_build_query([
            'date' => '2026/09/22 - 2026/09/28',
            'outlet' => $outlet->id,
            'draw' => 1,
            'start' => 0,
            'length' => -1,
            'order' => [
                ['column' => 1, 'dir' => 'desc'],
            ],
        ]));

        $response->assertOk();
        $rows = collect($response->json('data'));

        $soldRow = $rows->firstWhere('name', '- Cheese Current Name');
        $this->assertSame(1, $soldRow['quantity_sold']);
        $this->assertSame('Rp. 1.000', $soldRow['gross_sales']);
        $this->assertSame('Rp. 100', $soldRow['discounts']);
        $this->assertSame('Rp. 900', $soldRow['net_sales']);

        $unsoldRow = $rows->firstWhere('name', '- Unsold Modifier');
        $this->assertSame(0, $unsoldRow['quantity_sold']);
        $this->assertSame('Rp. 0', $unsoldRow['gross_sales']);

        $deletedRow = $rows->firstWhere('name', '- Deleted Historical');
        $this->assertSame(1, $deletedRow['quantity_sold']);
        $this->assertSame('Rp. 2.000', $deletedRow['gross_sales']);
        $this->assertSame('Rp. 200', $deletedRow['discounts']);
        $this->assertSame('Rp. 1.800', $deletedRow['net_sales']);

        $groupRow = $rows->firstWhere('name', 'Topping Test');
        $this->assertSame(2, $groupRow['quantity_sold']);
        $this->assertSame('Rp. 3.000', $groupRow['gross_sales']);
        $this->assertSame([
            'Topping Test',
            '- Cheese Current Name',
            '- Unsold Modifier',
            '- Deleted Historical',
            'Empty Group',
            '- Empty Child',
        ], $rows->pluck('name')->all());
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
}
