<?php

namespace App\Support\B2b;

use App\Models\B2b\Customer;
use App\Models\B2b\Invoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Rekap tagihan (Statement of Account) satu customer: semua invoice belum lunas per hari ini.
 * Hanya dokumen: tanpa nomor dan tanpa tabel; invoice tetap dibayar satu per satu.
 */
final class CustomerStatement
{
    /**
     * @param  Collection<int, array{invoice: Invoice, outstanding: int, overdue_days: ?int}>  $rows
     */
    private function __construct(
        public readonly Customer $customer,
        public readonly Carbon $date,
        public readonly Collection $rows,
    ) {}

    /** @param  list<int>  $outletIds  outlet yang boleh dilihat user (sama dengan menu Invoice) */
    public static function for(Customer $customer, array $outletIds): self
    {
        $today = today();
        $rows = Invoice::query()
            ->where('b2b_customer_id', $customer->id)
            ->whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL, Invoice::STATUS_OVERDUE])
            ->whereHas('order', fn ($order) => $order->withTrashed()->whereIn('outlet_id', $outletIds))
            ->with('order:id,order_number')
            ->orderBy('due_date')
            ->orderBy('issue_date')
            ->orderBy('id')
            ->get()
            ->map(fn (Invoice $invoice) => [
                'invoice' => $invoice,
                'outstanding' => $invoice->outstandingAmount(),
                'overdue_days' => $invoice->due_date->lt($today) ? (int) $invoice->due_date->diffInDays($today) : null,
            ]);

        return new self($customer, $today, $rows);
    }

    /** "Statement of Account - PT Maju - 2026-11-23.pdf" */
    public function pdfFilename(): string
    {
        $customer = preg_replace('/[^\pL\pN ._-]+/u', '', $this->customer->name);

        return 'Statement of Account'.($customer !== '' ? ' - '.$customer : '').' - '.$this->date->format('Y-m-d').'.pdf';
    }

    public function total(): int
    {
        return (int) $this->rows->sum(fn ($row) => $row['invoice']->total_to_pay);
    }

    public function paid(): int
    {
        return (int) $this->rows->sum(fn ($row) => $row['invoice']->paid_amount);
    }

    public function balance(): int
    {
        return (int) $this->rows->sum('outstanding');
    }

    public function overdueBalance(): int
    {
        return (int) $this->rows->whereNotNull('overdue_days')->sum('outstanding');
    }

    public function overdueCount(): int
    {
        return $this->rows->whereNotNull('overdue_days')->count();
    }
}
