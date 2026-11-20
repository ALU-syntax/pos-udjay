<?php

namespace App\Services\B2b;

use App\Models\B2b\Invoice;
use App\Models\B2b\InvoiceSchedule;
use App\Models\B2b\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Menandai tagihan tempo yang lewat jatuh tempo (dipanggil b2b:mark-overdue tiap 00:05 WIB).
 * Status `overdue` + `overdue_at` ditulis ke invoice dan jadwal tagihan supaya mudah difilter dan dilacak.
 * Pembayaran berikutnya menghitung ulang status lewat PaymentService dengan aturan yang sama.
 */
class OverdueService
{
    private const OPEN = [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL];

    public function __construct(private readonly ActivityLogger $logger) {}

    /** @return array{invoices:int, schedules:int} */
    public function markOverdue(): array
    {
        $today = today();

        // Jadwal tagihan: sekarang selalu 1 per invoice, tapi cicilan nanti bisa telat walau invoice belum.
        $schedules = InvoiceSchedule::query()
            ->whereIn('status', self::OPEN)
            ->whereDate('due_date', '<', $today)
            ->whereHas('invoice', fn (Builder $invoice) => $this->creditInvoices($invoice, [...self::OPEN, Invoice::STATUS_OVERDUE]))
            // Jam dari aplikasi (WIB), bukan NOW() MySQL yang zona waktunya bisa berbeda.
            ->update(['status' => Invoice::STATUS_OVERDUE, 'overdue_at' => DB::raw("COALESCE(overdue_at, '".now()->format('Y-m-d H:i:s')."')")]);

        $invoices = 0;
        $this->creditInvoices(Invoice::query(), self::OPEN)
            ->whereDate('due_date', '<', $today)
            ->select('id')
            ->chunkById(200, function ($rows) use (&$invoices, $today) {
                foreach ($rows as $row) {
                    $invoices += $this->markInvoice($row->id, $today) ? 1 : 0;
                }
            });

        return ['invoices' => $invoices, 'schedules' => $schedules];
    }

    private function markInvoice(int $id, $today): bool
    {
        return DB::transaction(function () use ($id, $today) {
            // Dicek ulang setelah dikunci: bisa saja baru dibayar di antara query dan update.
            $invoice = Invoice::query()->whereKey($id)->lockForUpdate()->first();
            if (! $invoice || ! in_array($invoice->status, self::OPEN, true) || ! $invoice->due_date->lt($today)) {
                return false;
            }

            $invoice->forceFill([
                'status' => Invoice::STATUS_OVERDUE,
                'overdue_at' => $invoice->overdue_at ?? now(),
            ])->save();

            $this->logger->log('invoice.overdue', $invoice, [
                'invoice_number' => $invoice->invoice_number,
                'due_date' => $invoice->due_date->toDateString(),
                'outstanding' => $invoice->outstandingAmount(),
            ]);

            return true;
        });
    }

    /** Invoice tempo dengan status tertentu yang pesanannya tidak dibatalkan. */
    private function creditInvoices(Builder $query, array $statuses): Builder
    {
        return $query
            ->whereIn('status', $statuses)
            ->whereHas('order', fn (Builder $order) => $order
                ->where('payment_type', Order::PAYMENT_CREDIT)
                ->where('status', '!=', Order::STATUS_CANCELLED));
    }
}
