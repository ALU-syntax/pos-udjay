<?php
// app/Exports/SalesSummaryTableExport.php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
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

    public function __construct(array $rows, bool $withTotals = true)
    {
        $this->rows = $rows;
        $this->withTotals = $withTotals;
    }

    public function array(): array
    {

        $data = [];
        // header persis seperti template
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
        // Bold header (row 1)
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);

        // Bold baris total (baris terakhir)
        $lastRow = $sheet->getHighestRow();
        if ($lastRow > 1) {
            $sheet->getStyle("A{$lastRow}:I{$lastRow}")->getFont()->setBold(true);
        }

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
