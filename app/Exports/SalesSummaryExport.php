<?php
// app/Exports/SalesSummaryExport.php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesSummaryExport implements FromArray, ShouldAutoSize, WithStyles, WithColumnFormatting
{
    /**
     * $rows = array of:
     * [
     *   'outlet' => string,
     *   'gross' => int|float,
     *   'discount' => int|float,
     *   'refund' => int|float,
     *   'net' => int|float,
     *   'gratuity' => int|float,
     *   'tax' => int|float,
     *   'rounding' => int|float,
     *   'total_collected' => int|float,
     * ]
     */
    protected array $rows;
    protected bool $withTotals;
    protected ?string $period;

    private const TITLE_ROWS = 3;
    private const HEADER_ROW = 4;
    private const LAST_COL   = 'I';

    public function __construct(array $rows, bool $withTotals = true, ?string $period = null)
    {
        $this->rows = $rows;
        $this->withTotals = $withTotals;
        $this->period = $period;
    }

    public function array(): array
    {
        $data = [];

        // Baris judul & periode
        $data[] = ['Sales Summary Report'];
        $data[] = [$this->period ?: 'Periode: -'];
        $data[] = [''];

        // Header tabel
        $data[] = [
            'Outlet', 'Gross Sales', 'Discount', 'Refund', 'Net Sales',
            'Gratuity', 'Tax', 'Rounding', 'Total Collected'
        ];

        $sum = [
            'gross' => 0, 'discount' => 0, 'refund' => 0, 'net' => 0,
            'gratuity' => 0, 'tax' => 0, 'rounding' => 0, 'total_collected' => 0,
        ];

        foreach ($this->rows as $r) {
            $data[] = [
                $r['outlet'] ?? '-',
                (float) ($r['gross'] ?? 0),
                (float) ($r['discount'] ?? 0),
                (float) ($r['refund'] ?? 0),
                (float) ($r['net'] ?? 0),
                (float) ($r['gratuity'] ?? 0),
                (float) ($r['tax'] ?? 0),
                (float) ($r['rounding'] ?? 0),
                (float) ($r['total_collected'] ?? 0),
            ];

            // akumulasi total
            foreach ($sum as $k => $v) {
                $sum[$k] += (float) ($r[$k] ?? 0);
            }
        }

        if ($this->withTotals) {
            $data[] = [
                'Total',
                $sum['gross'], $sum['discount'], $sum['refund'], $sum['net'],
                $sum['gratuity'], $sum['tax'], $sum['rounding'], $sum['total_collected'],
            ];
        }

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        $headerRow = self::HEADER_ROW;
        $lastCol   = self::LAST_COL;
        $lastRow   = $sheet->getHighestRow();

        // ---- Judul ----
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(26);

        // ---- Periode ----
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(11);
        $sheet->getStyle('A2')->getFont()->getColor()->setARGB('FF808080');
        $sheet->getStyle('A2')
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // ---- Header tabel ----
        $headerRange = "A{$headerRow}:{$lastCol}{$headerRow}";
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFont()->getColor()->setARGB(Color::COLOR_WHITE);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD03C3C');
        $sheet->getStyle($headerRange)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
        $sheet->getRowDimension($headerRow)->setRowHeight(20);

        // ---- Border tabel ----
        $tableRange = "A{$headerRow}:{$lastCol}{$lastRow}";
        $sheet->getStyle($tableRange)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->setColor(new Color('FFD9D9D9'));

        // ---- Perataan angka (kolom B..I rata kanan) ----
        if ($lastRow > $headerRow) {
            $sheet->getStyle('B' . ($headerRow + 1) . ":{$lastCol}{$lastRow}")
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        // ---- Baris total ----
        if ($this->withTotals && $lastRow > $headerRow) {
            $totalRange = "A{$lastRow}:{$lastCol}{$lastRow}";
            $sheet->getStyle($totalRange)->getFont()->setBold(true);
            $sheet->getStyle($totalRange)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFF2F2F2');
            $sheet->getStyle($totalRange)->getBorders()->getTop()
                ->setBorderStyle(Border::BORDER_DOUBLE)
                ->setColor(new Color('FF000000'));
        }

        // Bekukan baris header
        $sheet->freezePane('A' . ($headerRow + 1));

        return [];
    }

    public function columnFormats(): array
    {
        // Format angka untuk kolom B..I
        return [
            'B' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'C' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }
}
