<?php

namespace App\Services\B2b;

use App\Models\B2b\Invoice;
use App\Support\B2b\CustomerStatement;
use Barryvdh\DomPDF\Facade\Pdf;

/** PDF invoice dan rekap tagihan (dompdf) untuk lampiran reminder dan unduhan dari menu Invoice. */
class InvoicePdfService
{
    public function __construct(private readonly B2bSettings $settings) {}

    public function output(Invoice $invoice): string
    {
        $invoice->loadMissing(['order.items', 'customer', 'allocations.payment']);

        return Pdf::loadView('layouts.b2b.invoices.pdf', [
            'invoice' => $invoice,
            'profile' => $this->settings->invoiceProfile(),
        ])
            ->setPaper('a4')
            // Hanya huruf yang dipakai yang ditanam; tanpa ini font DejaVu ikut utuh dan PDF jadi ±1 MB per invoice.
            ->setOption('isFontSubsettingEnabled', true)
            ->output();
    }

    /** PDF rekap tagihan customer (Statement of Account). */
    public function statement(CustomerStatement $statement): string
    {
        return Pdf::loadView('layouts.b2b.invoices.statement-pdf', [
            'statement' => $statement,
            'profile' => $this->settings->invoiceProfile(),
        ])
            ->setPaper('a4')
            ->setOption('isFontSubsettingEnabled', true)
            ->output();
    }
}
