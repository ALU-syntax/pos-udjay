<?php

namespace App\Http\Controllers\B2b;

use App\Http\Controllers\Controller;
use App\Models\B2b\Customer;
use App\Models\Outlets;
use App\Services\B2b\ReportService;
use App\Support\B2b\ReportFilter;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Laporan B2B: satu halaman berisi semua bagian. Halaman hanya kerangka + filter;
 * setiap bagian mengambil datanya sendiri dari data() supaya ganti filter cukup memuat ulang bagian-bagiannya.
 */
class ReportController extends Controller
{
    /** Bagian laporan yang punya endpoint data. Bertambah seiring tahap pengerjaan. */
    public const SECTIONS = ['summary', 'trend', 'breakdown', 'orders', 'aging', 'debtors', 'invoices', 'cashflow', 'mutations', 'contracts'];

    public function __construct(private readonly ReportService $reports)
    {
        $this->abilities = [];
    }

    public function index(Request $request)
    {
        $this->authorize('read b2b/reports');

        $outletIds = $request->user()->outletIds();
        $filter = ReportFilter::resolve($request->query(), $outletIds);

        return view('layouts.b2b.reports.index', [
            'filter' => $filter,
            'outlets' => Outlets::query()->whereIn('id', $outletIds)->orderBy('name')->get(['id', 'name']),
            'customer' => $filter->customerId ? Customer::withTrashed()->find($filter->customerId, ['id', 'name']) : null,
        ]);
    }

    /**
     * Kartu piutang satu customer: halaman baru di dalam menu laporan. Ikut filter periode dan outlet dari URL
     * (sama dengan halaman laporan); customer selalu customer ini.
     */
    public function customer(Request $request, Customer $customer)
    {
        $this->authorize('read b2b/reports');

        $outletIds = $request->user()->outletIds();
        $filter = ReportFilter::resolve(['customer_id' => $customer->id] + $request->query(), $outletIds);

        return view('layouts.b2b.reports.customer', [
            'customer' => $customer,
            'filter' => $filter,
            'outlets' => Outlets::query()->whereIn('id', $outletIds)->orderBy('name')->get(['id', 'name']),
            'contract' => $customer->contracts()->effectiveOn(today())->first(['id', 'contract_number', 'start_date', 'end_date']),
            'card' => $this->reports->customerCard($filter),
        ]);
    }

    public function data(Request $request, string $section)
    {
        $this->authorize('read b2b/reports');

        $outletIds = $request->user()->outletIds();
        $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'outlet_id' => ['nullable', 'integer', Rule::in($outletIds)],
            'customer_id' => ['nullable', 'integer'],
            'group' => ['nullable', Rule::in(['day', 'week', 'month'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'by' => match ($section) {
                'breakdown' => ['required', Rule::in(ReportService::BREAKDOWNS)],
                'cashflow' => ['required', Rule::in(ReportService::CASH_GROUPS)],
                default => ['nullable', Rule::in(ReportService::CASH_GROUPS)],
            },
            // Kunci kelompok rekap kas untuk mutasi: nama metode, id outlet (0 = pusat), atau id kasir (0 = tidak tercatat).
            'key' => [Rule::requiredIf($section === 'mutations' && $request->filled('by')), 'nullable', 'string', 'max:255'],
            'product' => ['nullable', 'string', 'max:255'],
            'variant' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'min:0'],
            'bucket' => ['nullable', Rule::in(ReportService::AGING_FILTERS)],
        ], [
            'to.after_or_equal' => 'Tanggal akhir periode tidak boleh sebelum tanggal awal.',
            'outlet_id.in' => 'Anda tidak punya akses ke outlet ini.',
        ]);
        $filter = ReportFilter::resolve($request->query(), $outletIds);

        $data = match ($section) {
            'summary' => $this->reports->summary($filter),
            'trend' => $this->reports->trend($filter, $request->query('group')),
            'breakdown' => $this->reports->breakdown($filter, $request->query('by')),
            'orders' => $this->reports->orders($filter, $request->integer('page', 1)),
            'aging' => $this->reports->aging($filter),
            'debtors' => $this->reports->debtors($filter, $request->query('bucket')),
            'invoices' => $this->reports->invoices($filter, $request->query('bucket'), $request->integer('page', 1)),
            'cashflow' => $this->reports->cashflow($filter, $request->query('by')),
            'contracts' => $this->reports->contracts($filter),
            'mutations' => $this->reports->mutations($filter, $request->query('by'), $request->query('key'), $request->integer('page', 1)),
        };

        return response()->json(['data' => $data, 'filter' => $filter->toQuery()]);
    }
}
