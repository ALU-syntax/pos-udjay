<?php

namespace App\Exports\B2b;

use App\Models\B2b\Invoice;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

/** Satu sheet Excel bertemplate "Invoice External" untuk satu invoice B2B (lihat InvoiceSheetWriter). */
class InvoiceSheet implements WithEvents, WithTitle
{
    public function __construct(
        private readonly Invoice $invoice,
        private readonly array $profile,
    ) {}

    public function title(): string
    {
        return InvoiceSheetWriter::sheetTitle($this->invoice);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => fn (AfterSheet $event) => (new InvoiceSheetWriter($this->profile))
                ->write($event->sheet->getDelegate(), $this->invoice),
        ];
    }

    /** Nama file unduhan, mis. "Invoice 07-INV-BGR-11-2026 - Raksa Koffie.xlsx". */
    public static function filename(Invoice $invoice): string
    {
        $customer = preg_replace('/[^\pL\pN ._-]+/u', '', (string) $invoice->customer?->name);

        return trim('Invoice '.InvoiceSheetWriter::sheetTitle($invoice).($customer !== '' ? ' - '.$customer : '')).'.xlsx';
    }
}
