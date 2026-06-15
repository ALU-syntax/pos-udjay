<?php

namespace App\Http\Controllers;

use App\Exports\RushHourExport;
use App\Models\Outlets;
use App\Models\Payment;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RushHourController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today()->toDateString();
        $from = $this->dateOrDefault($request->input('from'), $today);
        $to = $this->dateOrDefault($request->input('to'), $from);

        if (Carbon::parse($to)->lt(Carbon::parse($from))) {
            [$from, $to] = [$to, $from];
        }

        $allowedOutletIds = $this->allowedOutletIds();

        $outlets = Outlets::whereIn('id', $allowedOutletIds->all())
            ->orderBy('name')
            ->get();

        $paymentMethods = Payment::where('status', true)
            ->orderBy('name')
            ->get();

        $selectedOutletIds = collect((array) $request->input('outlet_id', []))
            ->flatten()
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->intersect($outlets->pluck('id'))
            ->values();

        if ($selectedOutletIds->isEmpty()) {
            $selectedOutletIds = $outlets->pluck('id')->values();
        }

        $selectedPaymentMethodIds = collect((array) $request->input('payment_method', []))
            ->flatten()
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->intersect($paymentMethods->pluck('id'))
            ->values();

        if ($selectedPaymentMethodIds->isEmpty()) {
            $selectedPaymentMethodIds = $paymentMethods->pluck('id')->values();
        }

        $selectedDayOfWeek = $this->normalizeDayOfWeek($request->input('day_of_week'));

        return view('layouts.reports.rush-hour', [
            'outlets' => $outlets,
            'paymentMethods' => $paymentMethods,
            'selectedFrom' => $from,
            'selectedTo' => $to,
            'selectedOutletIds' => $selectedOutletIds->all(),
            'selectedPaymentMethodIds' => $selectedPaymentMethodIds->all(),
            'dayOfWeekOptions' => $this->dayOfWeekOptions(),
            'selectedDayOfWeek' => $selectedDayOfWeek,
        ]);
    }

    public function exportRushHour(Request $r)
    {
        $r->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'outlet_id' => ['nullable', 'array'],
            'day_of_week' => ['nullable', 'integer', 'between:1,7'],
        ]);

        $allowedOutletIds = $this->allowedOutletIds();
        $dayOfWeek = $this->normalizeDayOfWeek($r->input('day_of_week'));

        $requestedOutletIds = collect((array) $r->input('outlet_id', []))
            ->flatten()
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        $outletIds = $requestedOutletIds->isNotEmpty()
            ? $requestedOutletIds->intersect($allowedOutletIds)->values()->all()
            : $allowedOutletIds->all();

        abort_if(empty($outletIds), 403);

        $outletName = 'selected_outlets';

        if (count($outletIds) === 1) {
            $outletName = Str::slug(Outlets::find($outletIds[0])?->name ?? 'outlet', '_');
        }

        $path = 'exports/' . 'rush_hour_' . $outletName . '_' . now()->format('Ymd_His') . '.xlsx';

        (new RushHourExport(
            from: $r->input('from'),
            to: $r->input('to'),
            outletIds: $outletIds,
            dayOfWeek: $dayOfWeek
        ))->queue($path, 'public');

        return response()->json([
            'ok' => true,
            'message' => 'Export Rush Hour dimulai di background. File akan tersedia ketika proses selesai.',
            'path' => $path,
            'download_url' => Storage::disk('public')->url($path),
        ]);
    }

    public function summary(Request $request)
    {
        $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'outlet_id' => ['nullable', 'array'],
            'payment_method' => ['nullable', 'array'],
            'day_of_week' => ['nullable', 'integer', 'between:1,7'],
        ]);

        [$startDate, $endDate] = $this->dateRangeFromRequest($request);
        $outletIds = $this->filteredOutletIds($request);
        $paymentFilter = $this->filteredPaymentMethods($request);
        $dayOfWeek = $this->normalizeDayOfWeek($request->input('day_of_week'));

        abort_if(empty($outletIds), 403);

        $itemTotals = DB::table('transaction_items')
            ->selectRaw('transaction_id, SUM(COALESCE(harga, 0)) AS items_total')
            ->whereNull('deleted_at')
            ->groupBy('transaction_id');

        $refundTotals = DB::table('refund_transactions')
            ->selectRaw('transaction_id, SUM(COALESCE(nominal_refund, 0)) AS refunds_total')
            ->whereNull('deleted_at')
            ->groupBy('transaction_id');

        $transactions = Transaction::withoutGlobalScopes()
            ->from('transactions as t')
            ->leftJoin('category_payments as cp', 'cp.id', '=', 't.category_payment_id')
            ->leftJoin('payments as p', 'p.id', '=', 't.tipe_pembayaran')
            ->leftJoinSub($itemTotals, 'it', 'it.transaction_id', '=', 't.id')
            ->leftJoinSub($refundTotals, 'rf', 'rf.transaction_id', '=', 't.id')
            ->whereNull('t.deleted_at')
            ->whereBetween('t.created_at', [$startDate, $endDate])
            ->whereIn('t.outlet_id', $outletIds)
            ->when($dayOfWeek, fn ($query) => $this->applyDayOfWeekFilter($query, $dayOfWeek))
            ->when(!$paymentFilter['all'], function ($query) use ($paymentFilter) {
                $query->where(function ($paymentQuery) use ($paymentFilter) {
                    $paymentQuery->whereIn('t.tipe_pembayaran', $paymentFilter['ids']);

                    if (!empty($paymentFilter['names'])) {
                        $paymentQuery->orWhereIn('t.nama_tipe_pembayaran', $paymentFilter['names']);
                    }
                });
            })
            ->select([
                't.id',
                't.created_at',
                't.outlet_id',
                't.total',
                't.total_modifier',
                't.total_diskon',
                't.total_pajak',
                't.rounding_amount',
                DB::raw('COALESCE(it.items_total, 0) AS items_total'),
                DB::raw('COALESCE(rf.refunds_total, 0) AS refunds_total'),
                DB::raw("COALESCE(NULLIF(t.nama_tipe_pembayaran, ''), p.name, cp.name, 'Unknown') AS payment_method"),
            ])
            ->get();

        $summary = $this->emptySummary();
        $hourlyBuckets = [];
        $hourlyOutletBuckets = [];
        $paymentComposition = [];
        $outletNames = Outlets::whereIn('id', $outletIds)
            ->orderBy('name')
            ->pluck('name', 'id');

        for ($hour = 0; $hour <= 24; $hour++) {
            $hourLabel = sprintf('%02d:00', $hour);

            $hourlyBuckets[$hourLabel] = [
                'hour' => $hourLabel,
                'transaction_count' => 0,
                'summary' => $this->emptySummary(),
            ];
        }

        foreach ($outletNames as $outletId => $outletName) {
            $hourlyOutletBuckets[(int) $outletId] = [
                'outlet_id' => (int) $outletId,
                'outlet_name' => $outletName,
                'hours' => collect($hourlyBuckets)
                    ->mapWithKeys(fn ($bucket) => [
                        $bucket['hour'] => [
                            'transaction_count' => 0,
                            'summary' => $this->emptySummary(),
                        ],
                    ])
                    ->all(),
            ];
        }

        foreach ($transactions as $transaction) {
            $itemsTotal = (float) ($transaction->items_total ?? 0);
            $gross = $itemsTotal + (float) ($transaction->total_modifier ?? 0);
            $discount = (float) ($transaction->total_diskon ?? 0);
            $refunds = (float) ($transaction->refunds_total ?? 0);
            $tax = $this->sumJsonValues($transaction->total_pajak, ['total']);
            $rounding = (float) ($transaction->rounding_amount ?? 0);
            $netSales = max(0, $gross - $discount - $refunds);
            $totalAmount = $netSales + $tax + $rounding;
            $totalCollected = (float) ($transaction->total ?? $totalAmount);
            $paymentMethodName = trim((string) ($transaction->payment_method ?? 'Unknown'));
            $paymentMethodName = $paymentMethodName !== '' ? $paymentMethodName : 'Unknown';
            $paymentMethodKey = Str::lower($paymentMethodName);
            $hourLabel = Carbon::parse($transaction->created_at)->format('H:00');
            $hourSummary = [
                'gross_sales' => $gross,
                'total_sales' => $itemsTotal,
                'discount' => $discount,
                'refunds' => $refunds,
                'net_sales' => $netSales,
                'gratuity' => 0.0,
                'tax' => $tax,
                'total_collected' => $totalCollected,
                'total_amount' => $totalAmount,
            ];

            foreach ($hourSummary as $key => $value) {
                $summary[$key] += $value;

                if (isset($hourlyBuckets[$hourLabel])) {
                    $hourlyBuckets[$hourLabel]['summary'][$key] += $value;
                }
            }

            if (isset($hourlyBuckets[$hourLabel])) {
                $hourlyBuckets[$hourLabel]['transaction_count']++;
            }

            $transactionOutletId = (int) ($transaction->outlet_id ?? 0);

            if (isset($hourlyOutletBuckets[$transactionOutletId]['hours'][$hourLabel])) {
                $hourlyOutletBuckets[$transactionOutletId]['hours'][$hourLabel]['transaction_count']++;

                foreach ($hourSummary as $key => $value) {
                    $hourlyOutletBuckets[$transactionOutletId]['hours'][$hourLabel]['summary'][$key] += $value;
                }
            }

            if (!isset($paymentComposition[$paymentMethodKey])) {
                $paymentComposition[$paymentMethodKey] = [
                    'name' => $paymentMethodName,
                    'value' => 0.0,
                ];
            }

            $paymentComposition[$paymentMethodKey]['value'] += $totalCollected;
        }

        $paymentCompositionTotal = collect($paymentComposition)->sum('value');
        $paymentCompositionItems = collect($paymentComposition)
            ->filter(fn ($item) => (float) ($item['value'] ?? 0) > 0)
            ->sortByDesc('value')
            ->values()
            ->map(fn ($item) => [
                'name' => $item['name'],
                'value' => (float) $item['value'],
                'percentage' => $paymentCompositionTotal > 0
                    ? round(((float) $item['value'] / $paymentCompositionTotal) * 100, 2)
                    : 0.0,
            ])
            ->all();

        $hourlyPerformance = collect($hourlyBuckets)
            ->values()
            ->map(fn ($bucket) => [
                'hour' => $bucket['hour'],
                'transaction_count' => (int) ($bucket['transaction_count'] ?? 0),
                'value' => (float) ($bucket['summary']['total_collected'] ?? 0),
                'summary' => array_map(fn ($value) => (float) $value, $bucket['summary']),
            ])
            ->all();

        $hourlyOutletPerformance = collect($hourlyOutletBuckets)
            ->values()
            ->map(fn ($bucket) => [
                'outlet_id' => $bucket['outlet_id'],
                'outlet_name' => $bucket['outlet_name'],
                'data' => collect($bucket['hours'])
                    ->map(fn ($detail, $hour) => [
                        'hour' => $hour,
                        'value' => (float) ($detail['summary']['total_collected'] ?? 0),
                    ])
                    ->values()
                    ->all(),
            ])
            ->all();

        $hourlyDetail = collect($hourlyBuckets)
            ->values()
            ->map(fn ($bucket) => [
                'hour' => $bucket['hour'],
                'transaction_count' => (int) ($bucket['transaction_count'] ?? 0),
                'summary' => array_map(fn ($value) => (float) $value, $bucket['summary']),
                'outlets' => collect($hourlyOutletBuckets)
                    ->values()
                    ->map(fn ($outletBucket) => [
                        'outlet_id' => $outletBucket['outlet_id'],
                        'outlet_name' => $outletBucket['outlet_name'],
                        'transaction_count' => (int) ($outletBucket['hours'][$bucket['hour']]['transaction_count'] ?? 0),
                        'summary' => array_map(
                            fn ($value) => (float) $value,
                            $outletBucket['hours'][$bucket['hour']]['summary'] ?? $this->emptySummary()
                        ),
                    ])
                    ->all(),
            ])
            ->all();

        return response()->json([
            'ok' => true,
            'summary' => array_map(fn ($value) => (float) $value, $summary),
            'payment_composition' => [
                'total' => (float) $paymentCompositionTotal,
                'items' => $paymentCompositionItems,
            ],
            'hourly_performance' => $hourlyPerformance,
            'hourly_outlet_performance' => $hourlyOutletPerformance,
            'hourly_detail' => $hourlyDetail,
        ]);
    }

    private function allowedOutletIds()
    {
        return collect(auth()->user()?->outletIds() ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();
    }

    private function dayOfWeekOptions(): array
    {
        return [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];
    }

    private function normalizeDayOfWeek($dayOfWeek): ?int
    {
        if ($dayOfWeek === null || $dayOfWeek === '' || $dayOfWeek === 'all') {
            return null;
        }

        $dayOfWeek = (int) $dayOfWeek;

        return array_key_exists($dayOfWeek, $this->dayOfWeekOptions())
            ? $dayOfWeek
            : null;
    }

    private function applyDayOfWeekFilter($query, ?int $dayOfWeek, string $column = 't.created_at')
    {
        if (!$dayOfWeek) {
            return $query;
        }

        return $query->whereRaw($this->dayOfWeekExpression($column) . ' = ?', [$dayOfWeek]);
    }

    private function dayOfWeekExpression(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'pgsql' => "EXTRACT(ISODOW FROM {$column})",
            'sqlite' => "CASE WHEN strftime('%w', {$column}) = '0' THEN 7 ELSE CAST(strftime('%w', {$column}) AS INTEGER) END",
            default => "WEEKDAY({$column}) + 1",
        };
    }

    private function dateOrDefault(?string $date, string $default): string
    {
        if (!$date) {
            return $default;
        }

        try {
            $parsed = Carbon::createFromFormat('Y-m-d', $date);

            return $parsed && $parsed->format('Y-m-d') === $date ? $date : $default;
        } catch (\Throwable) {
            return $default;
        }
    }

    private function dateRangeFromRequest(Request $request): array
    {
        $today = Carbon::today()->toDateString();
        $from = $this->dateOrDefault($request->input('from'), $today);
        $to = $this->dateOrDefault($request->input('to'), $from);

        if (Carbon::parse($to)->lt(Carbon::parse($from))) {
            [$from, $to] = [$to, $from];
        }

        return [
            Carbon::parse($from)->startOfDay(),
            Carbon::parse($to)->endOfDay(),
        ];
    }

    private function filteredOutletIds(Request $request): array
    {
        $allowedOutletIds = $this->allowedOutletIds();

        $requestedOutletIds = collect((array) $request->input('outlet_id', []))
            ->flatten()
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        return $requestedOutletIds->isNotEmpty()
            ? $requestedOutletIds->intersect($allowedOutletIds)->values()->all()
            : $allowedOutletIds->all();
    }

    private function filteredPaymentMethods(Request $request): array
    {
        $activePayments = Payment::where('status', true)
            ->select('id', 'name')
            ->get();

        $activePaymentIds = $activePayments->pluck('id')->map(fn ($id) => (int) $id)->values();

        $requestedPaymentIds = collect((array) $request->input('payment_method', []))
            ->flatten()
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->intersect($activePaymentIds)
            ->values();

        if ($requestedPaymentIds->isEmpty() || $requestedPaymentIds->count() === $activePaymentIds->count()) {
            return [
                'all' => true,
                'ids' => $activePaymentIds->all(),
                'names' => $activePayments->pluck('name')->filter()->values()->all(),
            ];
        }

        return [
            'all' => false,
            'ids' => $requestedPaymentIds->all(),
            'names' => $activePayments
                ->whereIn('id', $requestedPaymentIds->all())
                ->pluck('name')
                ->filter()
                ->values()
                ->all(),
        ];
    }

    private function emptySummary(): array
    {
        return [
            'gross_sales' => 0.0,
            'total_sales' => 0.0,
            'discount' => 0.0,
            'refunds' => 0.0,
            'net_sales' => 0.0,
            'gratuity' => 0.0,
            'tax' => 0.0,
            'total_collected' => 0.0,
            'total_amount' => 0.0,
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
}
