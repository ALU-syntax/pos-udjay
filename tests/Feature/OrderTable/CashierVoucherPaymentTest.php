<?php

namespace Tests\Feature\OrderTable;

use Illuminate\Support\Facades\DB;

/** Pencatatan voucher OT ke transaksi POS mengikuti konvensi diskon rupiah vs persen. */
class CashierVoucherPaymentTest extends CashierHttpTestCase
{
    public function test_fixed_voucher_is_recorded_once_at_transaction_level(): void
    {
        $this->applyVoucher(['code' => 'HEMAT5', 'type' => 'fixed', 'value' => 5000], 5000);

        $transaction = $this->pay();

        $this->assertSame(5000, (int) $transaction->total_diskon);
        $this->assertSame([[
            'id' => 'ot-voucher:1', 'nama' => 'Voucher HEMAT5', 'satuan' => 'rupiah', 'value' => 5000,
        ]], json_decode($transaction->diskon_all_item, true));
        $this->assertSame(['[]', '[]'], $this->itemDiscounts($transaction->id));
    }

    public function test_capped_percent_voucher_is_split_per_item_and_sums_to_the_discount(): void
    {
        // 15% dari 22.000 = 3.300, dibatasi 3.001 supaya sisa pembulatan ikut teruji.
        $this->applyVoucher(['code' => 'PAGI15', 'type' => 'percent', 'value' => 15, 'max_discount' => 3001], 3001);

        $transaction = $this->pay();

        $this->assertSame(3001, (int) $transaction->total_diskon);
        $this->assertSame([], json_decode($transaction->diskon_all_item, true));
        $entries = array_map(fn ($json) => json_decode($json, true)[0], $this->itemDiscounts($transaction->id));
        $this->assertSame([1501, 1500], array_column($entries, 'result'));
        $this->assertSame(['id' => 'ot-voucher:1', 'nama' => 'Voucher PAGI15', 'result' => 1501,
            'satuan' => 'percent', 'value' => 15], $entries[0]);
    }

    public function test_percent_voucher_falls_back_to_transaction_level_when_scope_no_longer_matches(): void
    {
        $this->applyVoucher(['code' => 'PRODUK9', 'type' => 'percent', 'value' => 10,
            'scope' => 'product', 'scope_ids' => json_encode([999])], 2200);

        $transaction = $this->pay();

        $this->assertSame([[
            'id' => 'ot-voucher:1', 'nama' => 'Voucher PRODUK9', 'satuan' => 'rupiah', 'value' => 2200,
        ]], json_decode($transaction->diskon_all_item, true));
        $this->assertSame(['[]', '[]'], $this->itemDiscounts($transaction->id));
    }

    public function test_order_without_voucher_keeps_empty_discount_columns(): void
    {
        $transaction = $this->pay();

        $this->assertSame('[]', $transaction->diskon_all_item);
        $this->assertSame(['[]', '[]'], $this->itemDiscounts($transaction->id));
    }

    private function applyVoucher(array $voucher, int $discount): void
    {
        DB::table('ot_vouchers')->insert(array_merge([
            'id' => 1, 'outlet_id' => 11, 'min_spend' => 0, 'scope' => 'all', 'status' => true,
        ], $voucher));
        DB::table('ot_orders')->where('id', 1)->update([
            'voucher_id' => 1, 'voucher_discount' => $discount, 'grand_total' => 22000 - $discount,
        ]);
        DB::table('ot_order_payments')->where('order_id', 1)->update(['amount' => 22000 - $discount]);
    }

    private function pay(): object
    {
        $id = $this->postJson('/api/v1/order-table/orders/1/pay', [
            'claimToken' => $this->claimToken(), 'paymentId' => null, 'nominalBayar' => 22000,
        ])->assertOk()->json('data.posTransactionId');

        return DB::table('transactions')->find($id);
    }

    private function itemDiscounts(int $transactionId): array
    {
        return DB::table('transaction_items')->where('transaction_id', $transactionId)
            ->orderBy('id')->pluck('discount_id')->all();
    }
}
