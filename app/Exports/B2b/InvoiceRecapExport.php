<?php

namespace App\Exports\B2b;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/** Workbook rekap invoice: sheet "Rekap" lalu satu sheet bertemplate "Invoice External" per invoice. */
class InvoiceRecapExport implements WithMultipleSheets
{
    /** Batas jumlah invoice per export supaya file (satu sheet per invoice) tidak terlalu berat. */
    public const MAX_INVOICES = 200;

    public function __construct(
        private readonly Collection $invoices,
        private readonly array $profile,
        private readonly array $filters = [],
    ) {}

    public function sheets(): array
    {
        return [
            new InvoiceRecapSheet($this->invoices, $this->filters),
            ...$this->invoices->map(fn ($invoice) => new InvoiceSheet($invoice, $this->profile))->all(),
        ];
    }
}
