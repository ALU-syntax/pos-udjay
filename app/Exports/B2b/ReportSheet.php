<?php

namespace App\Exports\B2b;

use Carbon\CarbonImmutable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Satu sheet laporan B2B: judul, baris keterangan (filter, waktu export), lalu satu atau beberapa tabel.
 * Gaya mengikuti sheet Rekap Invoice: Arial 10, header berwarna, uang & tanggal berupa angka/tanggal Excel,
 * baris total memakai rumus SUM. Sheet dengan satu tabel mendapat autofilter dan freeze pane.
 *
 * Tabel: ['title' => ?string, 'columns' => [[judul, lebar, jenis]], 'rows' => [[...]], 'total' => ?string label,
 *         'sum' => [indeks kolom yang dijumlah], 'empty' => teks bila tanpa baris]
 * Jenis kolom: text, int, money, date, datetime. Nilai tanggal berupa string "Y-m-d" / "Y-m-d H:i:s" atau null.
 */
class ReportSheet implements WithEvents, WithTitle
{
    private const ACCENT = 'F8CBAD';

    private const TOTAL_FILL = 'FBE5D6';

    private const FORMATS = [
        'int' => '#,##0',
        'money' => '"Rp"#,##0;[Red]-"Rp"#,##0',
        'date' => 'dd/mm/yyyy',
        'datetime' => 'dd/mm/yyyy hh:mm',
    ];

    /**
     * @param  list<string>  $notes  baris keterangan di bawah judul
     * @param  list<array>  $tables
     */
    public function __construct(
        private readonly string $title,
        private readonly string $heading,
        private readonly array $notes,
        private readonly array $tables,
    ) {}

    public function title(): string
    {
        return $this->title;
    }

    public function registerEvents(): array
    {
        return [AfterSheet::class => fn (AfterSheet $event) => $this->write($event->sheet->getDelegate())];
    }

    private function write(Worksheet $sheet): void
    {
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
        $width = max(array_map(fn (array $table) => count($table['columns']), $this->tables));
        $lastColumn = Coordinate::stringFromColumnIndex($width);

        $sheet->setCellValue('A1', $this->heading);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $row = 1;
        foreach ($this->notes as $note) {
            $row++;
            $sheet->setCellValue("A{$row}", $note);
            $sheet->getStyle("A{$row}")->getFont()->setSize(9)->getColor()->setRGB('555555');
        }
        foreach (range(1, $row) as $titleRow) {
            $sheet->mergeCells("A{$titleRow}:{$lastColumn}{$titleRow}");
        }

        $widths = [];
        $single = count($this->tables) === 1;
        foreach ($this->tables as $table) {
            $row += 2;
            if (! empty($table['title'])) {
                $sheet->setCellValue("A{$row}", $table['title']);
                $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(11);
                $row++;
            }
            [$row, $headerRow, $firstRow] = $this->table($sheet, $table, $row, $widths);
            if ($single && $firstRow <= $row) {
                $lastData = $row - (empty($table['total']) ? 0 : 1);
                $columns = Coordinate::stringFromColumnIndex(count($table['columns']));
                $sheet->setAutoFilter("A{$headerRow}:{$columns}{$lastData}");
                $sheet->freezePane('A'.$firstRow);
            }
        }

        foreach ($widths as $index => $columnWidth) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($index + 1))->setWidth($columnWidth);
        }
        $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4)->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToWidth(1)->setFitToHeight(0);
        $sheet->setSelectedCell('A1');
    }

    /**
     * Tulis satu tabel mulai baris $row (header). Mengembalikan [baris terakhir, baris header, baris data pertama].
     *
     * @return array{0: int, 1: int, 2: int}
     */
    private function table(Worksheet $sheet, array $table, int $row, array &$widths): array
    {
        $columns = $table['columns'];
        $last = Coordinate::stringFromColumnIndex(count($columns));
        $headerRow = $row;
        foreach ($columns as $index => [$label, $columnWidth]) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1).$row, $label);
            $widths[$index] = max($widths[$index] ?? 0, $columnWidth);
        }
        $header = $sheet->getStyle("A{$row}:{$last}{$row}");
        $header->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::ACCENT);
        $header->getFont()->setBold(true);
        $header->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getRowDimension($row)->setRowHeight(28);

        $firstRow = $row + 1;
        if (! $table['rows']) {
            $row++;
            $sheet->setCellValue("A{$row}", $table['empty'] ?? 'Tidak ada data.');
            $sheet->mergeCells("A{$row}:{$last}{$row}");
            $sheet->getStyle("A{$row}")->getFont()->setItalic(true)->getColor()->setRGB('888888');
            $sheet->getStyle("A{$headerRow}:{$last}{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            return [$row, $headerRow, $firstRow + 1];
        }

        foreach ($table['rows'] as $values) {
            $row++;
            $sheet->fromArray([array_map(
                fn ($value, int $index) => $this->cellValue($value, $columns[$index][2] ?? 'text'),
                $values,
                array_keys($values)
            )], null, "A{$row}", true);
        }
        $lastData = $row;

        if (! empty($table['total'])) {
            $row++;
            $sum = $table['sum'] ?? [];
            $labelEnd = Coordinate::stringFromColumnIndex(max(1, ($sum ? min($sum) : count($columns))));
            $sheet->setCellValue("A{$row}", $table['total']);
            if ($labelEnd !== 'A') {
                $sheet->mergeCells("A{$row}:{$labelEnd}{$row}");
            }
            foreach ($sum as $index) {
                $column = Coordinate::stringFromColumnIndex($index + 1);
                $sheet->setCellValue("{$column}{$row}", "=SUM({$column}{$firstRow}:{$column}{$lastData})");
            }
            $totals = $sheet->getStyle("A{$row}:{$last}{$row}");
            $totals->getFont()->setBold(true);
            $totals->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::TOTAL_FILL);
        }

        $sheet->getStyle("A{$headerRow}:{$last}{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach ($columns as $index => $column) {
            $type = $column[2] ?? 'text';
            $letter = Coordinate::stringFromColumnIndex($index + 1);
            if (isset(self::FORMATS[$type])) {
                $sheet->getStyle("{$letter}{$firstRow}:{$letter}{$row}")->getNumberFormat()->setFormatCode(self::FORMATS[$type]);
            }
            if (in_array($type, ['date', 'datetime'], true)) {
                $sheet->getStyle("{$letter}{$firstRow}:{$letter}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        }

        return [$row, $headerRow, $firstRow];
    }

    private function cellValue(mixed $value, string $type): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($type) {
            'date', 'datetime' => ExcelDate::PHPToExcel(CarbonImmutable::parse($value)),
            'int', 'money' => (int) $value,
            default => (string) $value,
        };
    }
}
