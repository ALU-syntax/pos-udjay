<?php

namespace App\Exports\B2b;

use App\Models\B2b\Invoice;
use App\Models\B2b\Order;
use App\Models\B2b\Payment;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet "Rekap": satu baris per invoice untuk rekap/rekonsiliasi finance.
 * Nomor invoice ber-hyperlink ke sheet invoice-nya; uang dan tanggal berupa angka/tanggal Excel; baris total di bawah.
 */
class InvoiceRecapSheet implements WithEvents, WithTitle
{
    public const TITLE = 'Rekap';

    private const ACCENT = 'F8CBAD';

    private const MONEY = '"Rp"#,##0';

    private const DATE = 'dd/mm/yyyy';

    private const HEADER_ROW = 5;

    /** [judul kolom, lebar] */
    private const COLUMNS = [
        ['No', 6], ['No. Invoice', 22], ['Tgl Invoice', 13], ['Jatuh Tempo', 13], ['No. Pesanan', 22], ['Customer', 30],
        ['Kode Customer', 15], ['Outlet', 20], ['Pembayaran', 15], ['Status', 18], ['Total', 15], ['Dibayar', 15],
        ['Sisa Tagihan', 15], ['Telat (hari)', 11], ['Tgl Lunas', 13], ['Bukti Bayar', 34],
    ];

    /**
     * @param  Collection<int, Invoice>  $invoices
     * @param  list<string>  $filters  keterangan filter yang dipakai, mis. "Status: Belum lunas"
     */
    public function __construct(
        private readonly Collection $invoices,
        private readonly array $filters,
        private readonly bool $linkSheets = true,
    ) {}

    public function title(): string
    {
        return self::TITLE;
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => fn (AfterSheet $event) => $this->write($event->sheet->getDelegate())];
    }

    private function write(Worksheet $sheet): void
    {
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
        $lastColumn = chr(64 + count(self::COLUMNS));

        $sheet->setCellValue('A1', 'Rekap Invoice B2B');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->setCellValue('A2', $this->filters ? 'Filter: '.implode(' · ', $this->filters) : 'Filter: semua invoice');
        $sheet->setCellValue('A3', 'Diekspor '.now()->format('d/m/Y H:i').' · '.$this->invoices->count().' invoice');
        $sheet->getStyle('A2:A3')->getFont()->setSize(9)->getColor()->setRGB('555555');
        foreach ([1, 2, 3] as $titleRow) {
            $sheet->mergeCells("A{$titleRow}:{$lastColumn}{$titleRow}");
        }

        $row = self::HEADER_ROW;
        foreach (self::COLUMNS as $index => [$title, $width]) {
            $column = chr(65 + $index);
            $sheet->setCellValue($column.$row, $title);
            $sheet->getColumnDimension($column)->setWidth($width);
        }
        $header = $sheet->getStyle("A{$row}:{$lastColumn}{$row}");
        $header->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::ACCENT);
        $header->getFont()->setBold(true);
        $header->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getRowDimension($row)->setRowHeight(28);

        $first = $row + 1;
        foreach ($this->invoices->values() as $index => $invoice) {
            $row++;
            $order = $invoice->order;
            $void = $invoice->status === Invoice::STATUS_VOID;
            $immediate = $order?->payment_type === Order::PAYMENT_IMMEDIATE;
            $overdueDays = $invoice->displayStatus() === Invoice::STATUS_OVERDUE ? (int) $invoice->due_date->diffInDays(today()) : null;
            $payments = $invoice->allocations->pluck('payment')->filter()->where('status', Payment::STATUS_POSTED)->unique('id');

            $sheet->fromArray([[
                $index + 1,
                $invoice->invoice_number,
                ExcelDate::PHPToExcel($invoice->issue_date),
                $immediate ? null : ExcelDate::PHPToExcel($invoice->due_date),
                $order?->order_number,
                $invoice->customer?->name,
                $invoice->customer?->code,
                $order?->outlet?->name,
                $order?->paymentTypeLabel(),
                $invoice->statusLabel(),
                (int) $invoice->total_to_pay,
                (int) $invoice->paid_amount,
                $void ? 0 : $invoice->outstandingAmount(),
                $overdueDays,
                $invoice->paid_at && $invoice->status === Invoice::STATUS_PAID ? ExcelDate::PHPToExcel($invoice->paid_at) : null,
                $payments->map(fn ($payment) => $payment->payment_number.' ('.$payment->method_name.')')->implode(', '),
            ]], null, "A{$row}", true);

            if ($this->linkSheets) {
                $sheet->getCell("B{$row}")->getHyperlink()->setUrl("sheet://'".InvoiceSheetWriter::sheetTitle($invoice)."'!A1");
                $sheet->getStyle("B{$row}")->getFont()->setUnderline(true)->getColor()->setRGB('1155CC');
            }
            if ($overdueDays) {
                $sheet->getStyle("J{$row}:N{$row}")->getFont()->getColor()->setRGB('B91C1C');
            }
            if ($void) {
                $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->getFont()->getColor()->setRGB('888888');
            }
        }
        $last = max($row, $first);

        // Baris total (rumus, ikut berubah kalau finance mengedit angka).
        $totalRow = $last + 1;
        $sheet->setCellValue("A{$totalRow}", 'Total');
        $sheet->mergeCells("A{$totalRow}:J{$totalRow}");
        foreach (['K', 'L', 'M'] as $column) {
            $sheet->setCellValue("{$column}{$totalRow}", "=SUM({$column}{$first}:{$column}{$last})");
        }
        $sheet->getStyle("A{$totalRow}:{$lastColumn}{$totalRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$totalRow}:{$lastColumn}{$totalRow}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FBE5D6');

        $table = "A".self::HEADER_ROW.":{$lastColumn}{$totalRow}";
        $sheet->getStyle($table)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("C{$first}:D{$totalRow}")->getNumberFormat()->setFormatCode(self::DATE);
        $sheet->getStyle("O{$first}:O{$totalRow}")->getNumberFormat()->setFormatCode(self::DATE);
        $sheet->getStyle("K{$first}:M{$totalRow}")->getNumberFormat()->setFormatCode(self::MONEY);
        $sheet->getStyle("A{$first}:A{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C{$first}:D{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("N{$first}:O{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("P{$first}:P{$last}")->getAlignment()->setWrapText(true);

        $sheet->setAutoFilter('A'.self::HEADER_ROW.":{$lastColumn}{$last}");
        $sheet->freezePane('C'.$first);
        $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4)->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToWidth(1)->setFitToHeight(0);
        $sheet->setSelectedCell('A1');
    }
}
