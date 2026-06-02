<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RushHourExport implements
    FromCollection,
    ShouldAutoSize,
    WithStyles,
    WithStrictNullComparison,
    WithTitle,
    ShouldQueue
{
    use Exportable;

    private const IDR_CURRENCY_FORMAT = '"Rp" #,##0;-"Rp" #,##0;"Rp" 0';

    private ?array $paymentMethods = null;

    public function __construct(
        public readonly ?string $from = null,
        public readonly ?string $to = null,
        public readonly array $outletIds = [],
    ) {}

    public function collection(): Collection
    {
        return collect($this->rows());
    }

    private function rows(): array
    {
        $paymentMethods = $this->paymentMethods();
        $buckets = $this->emptyBuckets($paymentMethods);

        foreach ($this->transactionRows() as $transaction) {
            $hour = (int) Carbon::parse($transaction->created_at)->format('H');
            $paymentMethod = $this->normalizePaymentMethod($transaction->payment_method);

            $gross = (float) $transaction->items_total + (float) ($transaction->total_modifier ?? 0);
            $sales = (int) $transaction->items_count;
            $discount = (float) ($transaction->total_diskon ?? 0);
            $refunds = (float) ($transaction->refunds_total ?? 0);
            $netSales = max(0, $gross - $discount - $refunds);
            $gratuity = 0.0;
            $tax = $this->sumJsonValues($transaction->total_pajak, ['total']);
            $rounding = (float) ($transaction->rounding_amount ?? 0);
            $totalAmount = $netSales + $tax + $rounding;
            $totalCollected = $totalAmount;

            $buckets[$hour]['gross'] += $gross;
            $buckets[$hour]['sales'] += $sales;
            $buckets[$hour]['discount'] += $discount;
            $buckets[$hour]['refunds'] += $refunds;
            $buckets[$hour]['net_sales'] += $netSales;
            $buckets[$hour]['gratuity'] += $gratuity;
            $buckets[$hour]['tax'] += $tax;
            $buckets[$hour]['total_collected'] += $totalCollected;
            $buckets[$hour]['total_amount'] += $totalAmount;
            $buckets[$hour]['payment_methods'][$paymentMethod] = ($buckets[$hour]['payment_methods'][$paymentMethod] ?? 0) + $totalCollected;
        }

        $rows = [[
            'Jam',
            'Gross Sales',
            'Sales',
            'Discount',
            'Refunds',
            'Net Sales',
            'Gratuity',
            'Tax',
            'Total Collected',
            'Total Amount',
            ...$paymentMethods,
        ]];

        $totals = $this->emptyTotalRow($paymentMethods);

        foreach ($buckets as $bucket) {
            $rows[] = [
                $bucket['label'],
                $bucket['gross'],
                $bucket['sales'],
                $bucket['discount'],
                $bucket['refunds'],
                $bucket['net_sales'],
                $bucket['gratuity'],
                $bucket['tax'],
                $bucket['total_collected'],
                $bucket['total_amount'],
                ...array_map(fn ($method) => $bucket['payment_methods'][$method] ?? 0, $paymentMethods),
            ];

            foreach (array_keys($totals) as $key) {
                if ($key === 'payment_methods') {
                    continue;
                }

                $totals[$key] += $bucket[$key];
            }

            foreach ($paymentMethods as $method) {
                $totals['payment_methods'][$method] += $bucket['payment_methods'][$method] ?? 0;
            }
        }

        $rows[] = [
            'Total',
            $totals['gross'],
            $totals['sales'],
            $totals['discount'],
            $totals['refunds'],
            $totals['net_sales'],
            $totals['gratuity'],
            $totals['tax'],
            $totals['total_collected'],
            $totals['total_amount'],
            ...array_map(fn ($method) => $totals['payment_methods'][$method] ?? 0, $paymentMethods),
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $highestColumn = $sheet->getHighestColumn();
        $highestRow = $sheet->getHighestRow();

        $sheet->getStyle("A1:{$highestColumn}1")->getFont()->setBold(true);
        $sheet->getStyle("A{$highestRow}:{$highestColumn}{$highestRow}")->getFont()->setBold(true);

        if ($highestRow > 1) {
            $this->formatCurrencyColumns($sheet, $highestColumn, $highestRow);
        }

        return [];
    }

    public function title(): string
    {
        return 'Rush Hour';
    }

    private function transactionRows(): iterable
    {
        [$startDate, $endDate] = $this->dateRange();

        $itemTotals = DB::table('transaction_items')
            ->selectRaw('transaction_id, SUM(COALESCE(harga, 0)) AS items_total, COUNT(*) AS items_count')
            ->whereNull('deleted_at')
            ->groupBy('transaction_id');

        $refundTotals = DB::table('refund_transactions')
            ->selectRaw('transaction_id, SUM(COALESCE(nominal_refund, 0)) AS refunds_total')
            ->whereNull('deleted_at')
            ->groupBy('transaction_id');

        return DB::table('transactions as t')
            ->leftJoin('category_payments as cp', 'cp.id', '=', 't.category_payment_id')
            ->leftJoin('payments as p', 'p.id', '=', 't.tipe_pembayaran')
            ->leftJoinSub($itemTotals, 'it', 'it.transaction_id', '=', 't.id')
            ->leftJoinSub($refundTotals, 'rf', 'rf.transaction_id', '=', 't.id')
            ->whereNull('t.deleted_at')
            ->whereBetween('t.created_at', [$startDate, $endDate])
            ->when(!empty($this->outletIds), fn ($query) => $query->whereIn('t.outlet_id', $this->outletIds))
            ->orderBy('t.created_at')
            ->select([
                't.id',
                't.created_at',
                't.total_modifier',
                't.total_diskon',
                't.total_pajak',
                't.rounding_amount',
                DB::raw('COALESCE(it.items_total, 0) AS items_total'),
                DB::raw('COALESCE(it.items_count, 0) AS items_count'),
                DB::raw('COALESCE(rf.refunds_total, 0) AS refunds_total'),
                DB::raw("COALESCE(t.nama_tipe_pembayaran, p.name, cp.name, 'Unknown') AS payment_method"),
            ])
            ->cursor();
    }

    private function formatCurrencyColumns(Worksheet $sheet, string $highestColumn, int $highestRow): void
    {
        $currencyHeaders = [
            'gross sales',
            'discount',
            'refunds',
            'net sales',
            'gratuity',
            'tax',
            'total collected',
            'total amount',
            'cash',
            'debit',
            'qris',
        ];

        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

        for ($columnIndex = 1; $columnIndex <= $highestColumnIndex; $columnIndex++) {
            $column = Coordinate::stringFromColumnIndex($columnIndex);
            $header = strtolower(trim((string) $sheet->getCell("{$column}1")->getValue()));

            if (!in_array($header, $currencyHeaders, true)) {
                continue;
            }

            $sheet->getStyle("{$column}2:{$column}{$highestRow}")
                ->getNumberFormat()
                ->setFormatCode(self::IDR_CURRENCY_FORMAT);
        }
    }

    private function paymentMethods(): array
    {
        if ($this->paymentMethods !== null) {
            return $this->paymentMethods;
        }

        [$startDate, $endDate] = $this->dateRange();

        $this->paymentMethods = DB::table('transactions as t')
            ->leftJoin('category_payments as cp', 'cp.id', '=', 't.category_payment_id')
            ->leftJoin('payments as p', 'p.id', '=', 't.tipe_pembayaran')
            ->whereNull('t.deleted_at')
            ->whereBetween('t.created_at', [$startDate, $endDate])
            ->when(!empty($this->outletIds), fn ($query) => $query->whereIn('t.outlet_id', $this->outletIds))
            ->selectRaw("DISTINCT COALESCE(t.nama_tipe_pembayaran, p.name, cp.name, 'Unknown') AS payment_method")
            ->pluck('payment_method')
            ->map(fn ($method) => $this->normalizePaymentMethod($method))
            ->filter()
            ->unique()
            ->sort(fn ($a, $b) => strnatcasecmp($a, $b))
            ->values()
            ->all();

        return $this->paymentMethods;
    }

    private function dateRange(): array
    {
        $startDate = $this->from
            ? Carbon::parse($this->from)->startOfDay()
            : Carbon::now()->startOfDay();

        $endDate = $this->to
            ? Carbon::parse($this->to)->endOfDay()
            : Carbon::now()->endOfDay();

        return [$startDate, $endDate];
    }

    private function emptyBuckets(array $paymentMethods): array
    {
        $buckets = [];

        for ($hour = 0; $hour < 24; $hour++) {
            $buckets[$hour] = [
                'label' => sprintf('%02d:00 - %02d:59', $hour, $hour),
                'gross' => 0.0,
                'sales' => 0,
                'discount' => 0.0,
                'refunds' => 0.0,
                'net_sales' => 0.0,
                'gratuity' => 0.0,
                'tax' => 0.0,
                'total_collected' => 0.0,
                'total_amount' => 0.0,
                'payment_methods' => array_fill_keys($paymentMethods, 0.0),
            ];
        }

        return $buckets;
    }

    private function emptyTotalRow(array $paymentMethods): array
    {
        return [
            'gross' => 0.0,
            'sales' => 0,
            'discount' => 0.0,
            'refunds' => 0.0,
            'net_sales' => 0.0,
            'gratuity' => 0.0,
            'tax' => 0.0,
            'total_collected' => 0.0,
            'total_amount' => 0.0,
            'payment_methods' => array_fill_keys($paymentMethods, 0.0),
        ];
    }

    private function sumJsonValues(?string $json, array $keys): float
    {
        if (!$json) {
            return 0.0;
        }

        $data = json_decode($json, true);

        if (!is_array($data)) {
            return 0.0;
        }

        $sum = 0.0;

        foreach ($data as $item) {
            if (is_numeric($item)) {
                $sum += (float) $item;
                continue;
            }

            if (!is_array($item)) {
                continue;
            }

            foreach ($keys as $key) {
                if (isset($item[$key]) && is_numeric($item[$key])) {
                    $sum += (float) $item[$key];
                    break;
                }
            }
        }

        return $sum;
    }

    private function normalizePaymentMethod(?string $paymentMethod): string
    {
        $paymentMethod = trim((string) $paymentMethod);

        return $paymentMethod !== '' ? $paymentMethod : 'Unknown';
    }
}
