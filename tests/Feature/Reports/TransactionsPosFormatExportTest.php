<?php

namespace Tests\Feature\Reports;

use App\Exports\TransactionsPosFormatExport;
use App\Models\Outlets;
use App\Models\PettyCash;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TransactionsPosFormatExportTest extends TestCase
{
    use DatabaseTransactions;

    public function test_atas_nama_is_exported_as_last_column(): void
    {
        $outlet = Outlets::create(['name' => 'Outlet Export Atas Nama', 'address' => 'Test Address', 'phone' => '0800000003']);
        $user = User::factory()->create(['outlet_id' => [$outlet->id]]);
        $pettyCash = PettyCash::create([
            'outlet_id' => (string) $outlet->id,
            'amount_awal' => 100000,
            'user_id_started' => (string) $user->id,
            'open' => '2026-11-10 08:00:00',
        ]);

        $orderTable = $this->transaction($outlet, $user, $pettyCash, ['atas_nama' => 'Dewi']);
        $cashier = $this->transaction($outlet, $user, $pettyCash);

        $export = new TransactionsPosFormatExport('2026-11-10', '2026-11-10', [$outlet->id]);
        $rows = $export->query()->get()->keyBy('id');

        $headings = $export->headings();
        $this->assertSame('Atas Nama', end($headings));
        // Kolom format Moka sebelumnya tidak bergeser
        $this->assertSame('Payment Method', $headings[count($headings) - 2]);

        $orderTableRow = $export->map($rows[$orderTable->id]);
        $this->assertCount(count($headings), $orderTableRow);
        $this->assertSame('Dewi', end($orderTableRow));

        $cashierRow = $export->map($rows[$cashier->id]);
        $this->assertNull(end($cashierRow));
    }

    private function transaction(Outlets $outlet, User $user, PettyCash $pettyCash, array $attributes = []): Transaction
    {
        return Transaction::create(array_merge([
            'outlet_id' => $outlet->id,
            'user_id' => $user->id,
            'patty_cash_id' => $pettyCash->id,
            'total' => 10000,
            'nominal_bayar' => 10000,
            'change' => 0,
            'total_pajak' => '[]',
            'diskon_all_item' => '[]',
            'created_at' => '2026-11-10 10:00:00',
            'updated_at' => '2026-11-10 10:00:00',
        ], $attributes));
    }
}
