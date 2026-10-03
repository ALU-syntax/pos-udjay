<?php

namespace App\Http\Controllers\OrderTable;

use App\DataTables\OrderTable\VoucherDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrderTable\StoreVoucherRequest;
use App\Http\Requests\OrderTable\UpdateVoucherRequest;
use App\Models\OrderTable\Voucher;
use App\Models\Outlets;
use App\Services\OrderTable\AuditLogger;
use App\Services\OrderTable\CatalogSelectorService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class VoucherController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly CatalogSelectorService $catalogSelector,
    ) {
        $this->abilities = [];
    }

    public function index(VoucherDataTable $dataTable)
    {
        $this->authorize('read order-table/vouchers');

        return $dataTable->render('layouts.order-table.vouchers.index', [
            'outlets' => $this->outlets(),
            'canManageGlobal' => $this->catalogSelector->canUseGlobal(request()->user()),
        ]);
    }

    public function create()
    {
        $this->authorize('create order-table/vouchers');

        return view('layouts.order-table.vouchers.create', [
            'voucher' => new Voucher([
                'type' => 'percent',
                'min_spend' => 0,
                'scope' => 'all',
                'status' => true,
            ]),
            'outlets' => $this->outlets(),
            'canUseGlobal' => $this->catalogSelector->canUseGlobal(request()->user()),
        ]);
    }

    public function store(StoreVoucherRequest $request)
    {
        $voucher = DB::transaction(function () use ($request) {
            $voucher = Voucher::create($this->payload($request->validated()));
            $this->auditLogger->log('voucher.created', $voucher, null, $voucher->attributesToArray());

            return $voucher;
        });

        return responseSuccess(false, false, ['id' => $voucher->id]);
    }

    public function edit(int $voucher)
    {
        $this->authorize('update order-table/vouchers');
        $voucher = $this->voucher($voucher);

        return view('layouts.order-table.vouchers.edit', [
            'voucher' => $voucher,
            'outlets' => $this->outlets(),
            'canUseGlobal' => $this->catalogSelector->canUseGlobal(request()->user()),
            'targetOptions' => $this->catalogSelector->optionsByIds(
                $voucher->scope,
                request()->user(),
                $voucher->outlet_id,
                $voucher->scope_ids ?? [],
            ),
        ]);
    }

    public function update(UpdateVoucherRequest $request, int $voucher)
    {
        $voucher = $this->voucher($voucher);

        DB::transaction(function () use ($request, $voucher) {
            $before = $voucher->attributesToArray();
            $voucher->fill($this->payload($request->validated()));
            $voucher->save();

            $this->auditLogger->log('voucher.updated', $voucher, $before, $voucher->fresh()->attributesToArray());
        });

        return responseSuccess(true);
    }

    public function toggle(Request $request, int $voucher)
    {
        $this->authorize('update order-table/vouchers');
        $voucher = $this->voucher($voucher);

        DB::transaction(function () use ($voucher) {
            $voucher = Voucher::query()->whereKey($voucher->id)->lockForUpdate()->firstOrFail();
            if (! $voucher->status) {
                abort_unless($this->voucherTargetIsValid($voucher), 422, 'Target voucher tidak lagi tersedia. Perbarui voucher sebelum mengaktifkannya.');
            }
            $before = $voucher->attributesToArray();
            $voucher->status = ! $voucher->status;
            $voucher->save();

            $this->auditLogger->log(
                $voucher->status ? 'voucher.enabled' : 'voucher.disabled',
                $voucher,
                $before,
                $voucher->fresh()->attributesToArray(),
            );
        });
        $voucher->refresh();

        return responseSuccess(true, $voucher->status ? 'Voucher diaktifkan' : 'Voucher dinonaktifkan');
    }

    private function voucher(int $id): Voucher
    {
        $user = request()->user();

        return Voucher::query()
            ->whereKey($id)
            ->where(function (Builder $query) use ($user) {
                $query->whereIn('outlet_id', $user->outletIds());
                if ($this->catalogSelector->canUseGlobal($user)) {
                    $query->orWhereNull('outlet_id');
                }
            })
            ->firstOrFail();
    }

    private function outlets()
    {
        return Outlets::query()
            ->whereIn('id', request()->user()->outletIds())
            ->orderBy('name')
            ->get();
    }

    private function payload(array $validated): array
    {
        $payload = Arr::except($validated, ['quota_used']);
        if (($payload['type'] ?? null) === 'fixed') {
            $payload['max_discount'] = null;
        }

        return $payload;
    }

    private function voucherTargetIsValid(Voucher $voucher): bool
    {
        if ($voucher->outlet_id === null) {
            return $voucher->scope === 'all';
        }
        if ($voucher->scope === 'all') {
            return true;
        }

        return $this->catalogSelector->validIds(
            $voucher->scope,
            request()->user(),
            $voucher->outlet_id,
            $voucher->scope_ids ?? [],
        );
    }
}
