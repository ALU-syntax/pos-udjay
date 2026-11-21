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
    public const SECTIONS = ['summary', 'trend', 'customers', 'orders'];

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
        ], [
            'to.after_or_equal' => 'Tanggal akhir periode tidak boleh sebelum tanggal awal.',
            'outlet_id.in' => 'Anda tidak punya akses ke outlet ini.',
        ]);
        $filter = ReportFilter::resolve($request->query(), $outletIds);

        $data = match ($section) {
            'summary' => $this->reports->summary($filter),
            'trend' => $this->reports->trend($filter, $request->query('group')),
            'customers' => $this->reports->salesByCustomer($filter),
            'orders' => $this->reports->orders($filter, $request->integer('page', 1)),
        };

        return response()->json(['data' => $data, 'filter' => $filter->toQuery()]);
    }
}
