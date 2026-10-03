<?php

namespace App\Http\Controllers\OrderTable;

use App\DataTables\OrderTable\DiningTableDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrderTable\StoreDiningTableRequest;
use App\Http\Requests\OrderTable\UpdateDiningTableRequest;
use App\Models\OrderTable\DiningTable;
use App\Models\Outlets;
use App\Services\OrderTable\AuditLogger;
use App\Services\OrderTable\DiningTableQrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DiningTableController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly DiningTableQrService $qrService,
    ) {
        $this->abilities = [];
    }

    public function index(DiningTableDataTable $dataTable)
    {
        $this->authorize('read order-table/dining-tables');

        return $dataTable->render('layouts.order-table.dining-tables.index', [
            'outlets' => $this->outlets(),
        ]);
    }

    public function create()
    {
        $this->authorize('create order-table/dining-tables');

        return view('layouts.order-table.dining-tables.create', [
            'diningTable' => new DiningTable,
            'outlets' => $this->outlets(),
        ]);
    }

    public function store(StoreDiningTableRequest $request)
    {
        $table = DB::transaction(function () use ($request) {
            $table = new DiningTable($request->safe()->except('qr_token'));
            $table->qr_token = $this->qrService->generateToken();
            $table->save();

            $this->auditLogger->log('dining-table.created', $table, null, $table->attributesToArray());

            return $table;
        });

        return responseSuccess(false, false, ['id' => $table->id]);
    }

    public function edit(int $diningTable)
    {
        $this->authorize('update order-table/dining-tables');

        return view('layouts.order-table.dining-tables.edit', [
            'diningTable' => $this->table($diningTable),
            'outlets' => $this->outlets(),
        ]);
    }

    public function update(UpdateDiningTableRequest $request, int $diningTable)
    {
        $table = $this->table($diningTable);

        DB::transaction(function () use ($request, $table) {
            $before = $table->attributesToArray();
            $table->fill($request->safe()->except('qr_token'));
            $table->save();

            $this->auditLogger->log('dining-table.updated', $table, $before, $table->fresh()->attributesToArray());
        });

        return responseSuccess(true);
    }

    public function destroy(int $diningTable)
    {
        $this->authorize('delete order-table/dining-tables');
        $table = $this->table($diningTable);

        DB::transaction(function () use ($table) {
            $before = $table->attributesToArray();
            $table->delete();

            $this->auditLogger->log('dining-table.deleted', $table, $before, $table->fresh()->attributesToArray());
        });

        return responseSuccess(false, 'Meja berhasil dihapus');
    }

    public function toggleQr(Request $request, int $diningTable)
    {
        $this->authorize('update order-table/dining-tables');
        $table = $this->table($diningTable);

        DB::transaction(function () use ($table) {
            $before = $table->attributesToArray();
            $table->qr_active = ! $table->qr_active;
            $table->save();

            $this->auditLogger->log(
                $table->qr_active ? 'dining-table.qr-enabled' : 'dining-table.qr-disabled',
                $table,
                $before,
                $table->fresh()->attributesToArray(),
            );
        });

        return responseSuccess(true, $table->qr_active ? 'QR meja diaktifkan' : 'QR meja dinonaktifkan');
    }

    public function rotateToken(Request $request, int $diningTable)
    {
        $this->authorize('rotate order-table/dining-tables');
        $table = $this->table($diningTable);

        DB::transaction(function () use ($table) {
            $before = $table->attributesToArray();
            $table->qr_token = $this->qrService->generateToken();
            $table->save();

            $this->auditLogger->log('dining-table.qr-rotated', $table, $before, $table->fresh()->attributesToArray());
        });

        return responseSuccess(true, 'Token QR meja berhasil dirotasi');
    }

    public function preview(int $diningTable)
    {
        $this->authorize('read order-table/dining-tables');
        $table = $this->table($diningTable);

        return view('layouts.order-table.dining-tables.qr', [
            'diningTable' => $table,
            'qrSvg' => $this->qrService->svg($table),
            'qrUrl' => $this->qrService->url($table),
        ]);
    }

    public function download(int $diningTable)
    {
        $this->authorize('download order-table/dining-tables');

        return $this->qrService->download($this->table($diningTable));
    }

    public function print(int $diningTable)
    {
        $this->authorize('print order-table/dining-tables');
        $table = $this->table($diningTable);

        return view('layouts.order-table.dining-tables.print', [
            'diningTable' => $table,
            'qrSvg' => $this->qrService->svg($table, 480),
            'qrUrl' => $this->qrService->url($table),
        ]);
    }

    private function table(int $id): DiningTable
    {
        return DiningTable::query()
            ->whereKey($id)
            ->whereIn('outlet_id', request()->user()->outletIds())
            ->firstOrFail();
    }

    private function outlets()
    {
        return Outlets::query()
            ->whereIn('id', request()->user()->outletIds())
            ->orderBy('name')
            ->get();
    }
}
