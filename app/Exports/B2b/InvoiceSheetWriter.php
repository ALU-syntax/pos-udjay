<?php

namespace App\Exports\B2b;

use App\Models\B2b\Invoice;
use App\Models\B2b\Order;
use App\Models\B2b\Payment;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Menggambar satu invoice B2B ke worksheet dengan template "Invoice External" (Calle):
 * kepala brand, Vendor / Ship To, tabel No–Nama Barang–Qty–Satuan–Harga–Total, Notes/Instruksi, ringkasan, Pengirim/Penerima.
 * Nilai uang dan tanggal disimpan sebagai angka/tanggal Excel supaya bisa dijumlah dan difilter finance.
 */
class InvoiceSheetWriter
{
    private const ACCENT = 'F8CBAD';

    private const MONEY = '"Rp"#,##0';

    private const DATE = 'dd/mm/yyyy';

    /** Kolom: A No, B Nama Barang, C Qty, D Satuan, E Harga/label, F Total/nilai. */
    private const WIDTHS = ['A' => 10, 'B' => 42, 'C' => 9, 'D' => 14, 'E' => 20, 'F' => 18];

    public function __construct(private readonly array $profile) {}

    /** Nama sheet dari nomor invoice ("/" tidak boleh di nama sheet; maksimal 31 karakter). */
    public static function sheetTitle(Invoice $invoice): string
    {
        return mb_substr(str_replace(['/', '\\', '?', '*', '[', ']', ':'], '-', $invoice->invoice_number), 0, 31);
    }

    public function write(Worksheet $sheet, Invoice $invoice): void
    {
        $order = $invoice->order;
        $immediate = $order->payment_type === Order::PAYMENT_IMMEDIATE;
        $payments = $invoice->allocations->pluck('payment')->filter()->where('status', Payment::STATUS_POSTED)->unique('id')->values();

        foreach (self::WIDTHS as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
        $sheet->setShowGridlines(false);

        // ----- Kepala: logo + brand + alamat, judul "Invoice" + info dokumen -----
        $this->logo($sheet);
        $sheet->setCellValue('B1', $this->profile['brand_name']);
        $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(14);
        $sheet->mergeCells('B2:D3');
        $sheet->setCellValue('B2', $this->profile['address']);
        $sheet->getStyle('B2')->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getStyle('B2')->getFont()->setSize(9);

        $sheet->mergeCells('E1:F1');
        $sheet->setCellValue('E1', 'Invoice');
        $sheet->getStyle('E1')->getFont()->setBold(true)->setSize(18);
        $sheet->getStyle('E1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $meta = [
            ['No. Invoice', $invoice->invoice_number],
            ['Tanggal Invoice', ExcelDate::PHPToExcel($invoice->issue_date)],
            [$immediate ? 'Pembayaran' : 'Jatuh Tempo', $immediate ? 'Bayar langsung' : ExcelDate::PHPToExcel($invoice->due_date)],
            ['No. Pesanan', $order->order_number],
            ['Status', $invoice->statusLabel()],
        ];
        $row = 2;
        foreach ($meta as [$label, $value]) {
            $sheet->setCellValue("E{$row}", $label.' :');
            $sheet->setCellValue("F{$row}", $value);
            $sheet->getStyle("E{$row}:F{$row}")->getFont()->setSize(9);
            $sheet->getStyle("F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            if (is_float($value)) {
                $sheet->getStyle("F{$row}")->getNumberFormat()->setFormatCode(self::DATE);
            }
            $row++;
        }

        // ----- Vendor / Ship To -----
        $row = 8;
        $sheet->mergeCells("A{$row}:D{$row}");
        $sheet->mergeCells("E{$row}:F{$row}");
        $sheet->setCellValue("A{$row}", 'Vendor');
        $sheet->setCellValue("E{$row}", 'Ship To');
        $this->header($sheet, "A{$row}:F{$row}");

        $row++;
        $sheet->mergeCells("A{$row}:D{$row}");
        $sheet->mergeCells("E{$row}:F{$row}");
        $sheet->setCellValue("A{$row}", $this->profile['brand_name']);
        $sheet->setCellValue("E{$row}", $invoice->customer?->name);
        $sheet->getRowDimension($row)->setRowHeight(34);
        $sheet->getStyle("A{$row}:F{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}:F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $this->box($sheet, "A{$row}:F{$row}");

        // ----- Tabel barang -----
        $row++;
        foreach (['No', 'Nama Barang', 'Qty', 'Satuan', 'Harga', 'Total'] as $index => $title) {
            $sheet->setCellValue(chr(65 + $index).$row, $title);
        }
        $this->header($sheet, "A{$row}:F{$row}");
        $firstItemRow = $row + 1;

        $lines = $order->items->map(fn ($item) => [
            $item->product_name,
            // Satuan = nama varian kalau berbeda dari nama produk (mis. "1 kg").
            $item->variant_name !== '' && $item->variant_name !== $item->product_name ? $item->variant_name : '',
            (int) $item->qty,
            (int) $item->final_price,
        ]);
        if ($order->shipping_cost) {
            $lines->push(['Ongkir', '', 1, (int) $order->shipping_cost]);
        }
        if ($invoice->unique_code_amount) {
            $lines->push(['Kode unik', '', 1, (int) $invoice->unique_code_amount]);
        }
        foreach ($lines->values() as $index => [$name, $unit, $qty, $price]) {
            $row++;
            $sheet->fromArray([[$index + 1, $name, $qty, $unit, $price]], null, "A{$row}");
            $sheet->setCellValue("F{$row}", "=C{$row}*E{$row}");
        }
        $lastItemRow = $row;
        $sheet->getStyle("A{$firstItemRow}:F{$lastItemRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("A{$firstItemRow}:A{$lastItemRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("C{$firstItemRow}:D{$lastItemRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E{$firstItemRow}:F{$lastItemRow}")->getNumberFormat()->setFormatCode(self::MONEY);

        // ----- Notes / Instruksi (kiri) + ringkasan (kanan) -----
        $row++;
        $notesTop = $row;
        $sheet->mergeCells("A{$row}:D{$row}");
        $sheet->setCellValue("A{$row}", 'Notes / Instruksi');
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $this->box($sheet, "A{$row}:D{$row}");

        $paid = (int) $invoice->paid_amount;
        $summary = [
            ['Subtotal :', "=SUM(F{$firstItemRow}:F{$lastItemRow})"],
            ['Discount :', 0],
            ['Sales Tax :', 0],
            ['Total :', '=F'.$row.'-F'.($row + 1).'+F'.($row + 2)],
            ['Dibayar :', $paid],
            ['Sisa Tagihan :', '=MAX(0,F'.($row + 3).'-F'.($row + 4).')'],
        ];
        $summaryTop = $row;
        foreach ($summary as $offset => [$label, $value]) {
            $sheet->setCellValue('E'.($summaryTop + $offset), $label);
            $sheet->setCellValue('F'.($summaryTop + $offset), $value);
        }
        $summaryBottom = $summaryTop + count($summary) - 1;
        $sheet->getStyle("E{$summaryTop}:F{$summaryBottom}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("F{$summaryTop}:F{$summaryBottom}")->getNumberFormat()->setFormatCode(self::MONEY);
        foreach ([$summaryTop + 3, $summaryBottom] as $strong) {
            $sheet->getStyle("E{$strong}:F{$strong}")->getFont()->setBold(true);
        }
        $sheet->getStyle('F'.($summaryTop + 3))->getFont()->setSize(11);
        if ($invoice->status === Invoice::STATUS_VOID) {
            // Invoice batal tidak ditagih: sisa tagihan 0.
            $sheet->setCellValue("F{$summaryBottom}", 0);
        }

        $notesBodyTop = $notesTop + 1;
        $sheet->mergeCells("A{$notesBodyTop}:D{$summaryBottom}");
        $sheet->setCellValue("A{$notesBodyTop}", $this->notes($invoice, $immediate, $payments));
        $sheet->getStyle("A{$notesBodyTop}")->getAlignment()->setWrapText(true)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A{$notesBodyTop}")->getFont()->setBold(true);
        $this->box($sheet, "A{$notesBodyTop}:D{$summaryBottom}");

        // ----- Pengirim / Penerima -----
        $row = $summaryBottom + 3;
        $sheet->setCellValue("B{$row}", 'Pengirim');
        $sheet->setCellValue("E{$row}", 'Penerima');
        $sheet->mergeCells("E{$row}:F{$row}");
        $sheet->getStyle("B{$row}:F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $signRow = $row + 4;
        $sheet->setCellValue("B{$signRow}", '( '.$this->profile['brand_name'].' )');
        $sheet->setCellValue("E{$signRow}", '( '.($invoice->customer?->name ?? '').' )');
        $sheet->mergeCells("E{$signRow}:F{$signRow}");
        $sheet->getStyle("B{$signRow}:F{$signRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Siap cetak: A4 tegak, muat satu halaman lebar.
        $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4)->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
            ->setFitToWidth(1)->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.5)->setBottom(0.5)->setLeft(0.4)->setRight(0.4);
        $sheet->setSelectedCell('A1');
    }

    private function notes(Invoice $invoice, bool $immediate, $payments): string
    {
        if ($invoice->status === Invoice::STATUS_VOID) {
            return 'Invoice dibatalkan'.($invoice->order?->cancel_reason ? ': '.$invoice->order->cancel_reason : '').'.';
        }
        $lines = [];
        if ($invoice->status === Invoice::STATUS_PAID || $immediate) {
            $lines[] = 'LUNAS'.($invoice->paid_at ? ' '.$invoice->paid_at->format('d/m/Y') : '');
        } else {
            $lines[] = 'Transfer via bank '.$this->profile['bank_name'].' '.$this->profile['bank_account_number'].' a.n '.$this->profile['bank_account_name'];
            $lines[] = 'Cantumkan nomor invoice '.$invoice->invoice_number.' di berita transfer';
        }
        foreach ($payments as $payment) {
            $lines[] = $payment->payment_number.' · '.$payment->method_name.' · Rp'.number_format((int) $payment->amount, 0, ',', '.');
        }

        return implode("\n", $lines);
    }

    private function header(Worksheet $sheet, string $range): void
    {
        $style = $sheet->getStyle($range);
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::ACCENT);
        $style->getFont()->setBold(true);
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }

    private function box(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);
    }

    private function logo(Worksheet $sheet): void
    {
        $path = public_path('img/b2b/budiman-logo-pt.png');
        if (! is_file($path)) {
            return;
        }
        $drawing = new Drawing;
        $drawing->setName('Logo')->setPath($path)->setHeight(48)->setCoordinates('A1')->setOffsetX(2)->setOffsetY(2);
        $drawing->setWorksheet($sheet);
        $sheet->getRowDimension(1)->setRowHeight(22);
    }
}
