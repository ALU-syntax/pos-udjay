<?php

namespace App\Http\Controllers;

use App\DataTables\RequestOrderDataTable;
use App\Http\Requests\RequestOrderRequest;
use App\Models\Inventory;
use App\Models\InventoryRawMaterialStockBalance;
use App\Models\RawMaterialRequestStatus;
use App\Models\RawMaterialUnitConversions;
use App\Models\RawMaterialRequests;
use App\Models\RawMaterials;
use App\Models\Satuan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequestOrderController extends Controller
{
    public function index(RequestOrderDataTable $datatable)
    {
        return $datatable->render('layouts.request_order.index', [
            'stats' => $this->stats(),
            'statuses' => RawMaterialRequestStatus::where('is_active', 1)->orderBy('sort_order')->get(),
            'inventories' => Inventory::where('is_active', 1)->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('layouts.request_order.form', $this->formData(
            new RawMaterialRequests(),
            route('warehouse/request-order/store'),
            'Tambah Request Order'
        ));
    }

    public function store(RequestOrderRequest $request)
    {
        $requestOrder = DB::transaction(function () use ($request) {
            $validated = $request->validated();
            $items = $validated['items'];
            unset($validated['items']);

            $requestOrder = RawMaterialRequests::create(array_merge($validated, [
                'request_number' => $validated['request_number'] ?: $this->nextRequestNumber(),
                'status_id' => $this->statusId('draft'),
                'requested_by' => auth()->id(),
                'requested_at' => null,
                'approved_by' => null,
                'approved_at' => null,
            ]));

            $this->syncItems($requestOrder, $items);

            return $requestOrder;
        });

        if ($request->expectsJson()) {
            return responseSuccess(false, 'Request order berhasil dibuat sebagai draft', $this->stats());
        }

        return redirect()
            ->route('warehouse/request-order/detail', $requestOrder->id)
            ->with('success', 'Request order berhasil dibuat sebagai draft.');
    }

    public function detail(RawMaterialRequests $requestOrder)
    {
        $requestOrder->load([
            'status',
            'requesterInventory',
            'fulfillmentInventory',
            'requestedBy',
            'approvedBy',
            'items.rawMaterial.baseUnit',
            'items.unit',
        ]);

        return view('layouts.request_order.show', [
            'requestOrder' => $requestOrder,
            'stats' => $this->requestOrderStats($requestOrder),
        ]);
    }

    public function edit(RawMaterialRequests $requestOrder)
    {
        $this->ensureDraft($requestOrder);

        return view('layouts.request_order.form', $this->formData(
            $requestOrder,
            route('warehouse/request-order/update', $requestOrder->id),
            'Edit Request Order'
        ));
    }

    public function update(RequestOrderRequest $request, RawMaterialRequests $requestOrder)
    {
        $this->ensureDraft($requestOrder);

        DB::transaction(function () use ($request, $requestOrder) {
            $validated = $request->validated();
            $items = $validated['items'];
            unset($validated['items']);

            $requestOrder->update(array_merge($validated, [
                'request_number' => $validated['request_number'] ?: $requestOrder->request_number,
            ]));

            $this->syncItems($requestOrder, $items);
        });

        if ($request->expectsJson()) {
            return responseSuccess(true, 'Request order berhasil diperbarui', $this->stats());
        }

        return redirect()
            ->route('warehouse/request-order/detail', $requestOrder->id)
            ->with('success', 'Request order berhasil diperbarui.');
    }

    public function destroy(RawMaterialRequests $requestOrder)
    {
        $this->ensureDraft($requestOrder);

        $requestOrder->delete();

        return responseSuccess(false, 'Request order draft berhasil dihapus', $this->stats());
    }

    public function submit(RawMaterialRequests $requestOrder)
    {
        $this->ensureDraft($requestOrder);

        if ($requestOrder->items()->count() === 0) {
            throw ValidationException::withMessages([
                'items' => 'Request order harus memiliki minimal satu item sebelum disubmit.',
            ]);
        }

        $requestOrder->update([
            'status_id' => $this->statusId('submitted'),
            'requested_by' => auth()->id(),
            'requested_at' => now(),
        ]);

        return responseSuccess(false, 'Request order berhasil disubmit', $this->stats());
    }

    public function approve(Request $request, RawMaterialRequests $requestOrder)
    {
        $this->ensureSubmitted($requestOrder);
        $requestOrder->loadMissing('items.rawMaterial');

        if ($requestOrder->items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Request order tidak memiliki item untuk direview.',
            ]);
        }

        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.qty_base_approved' => ['required', 'numeric', 'min:0'],
        ], [
            'items.required' => 'Qty approval wajib dikirim untuk setiap item.',
            'items.*.qty_base_approved.required' => 'Qty approved wajib diisi.',
            'items.*.qty_base_approved.numeric' => 'Qty approved harus berupa angka.',
            'items.*.qty_base_approved.min' => 'Qty approved tidak boleh kurang dari 0.',
        ]);

        $approvedItems = $validated['items'];
        $totalApproved = 0;

        foreach ($requestOrder->items as $item) {
            if (!array_key_exists($item->id, $approvedItems)) {
                throw ValidationException::withMessages([
                    "items.{$item->id}.qty_base_approved" => 'Qty approved wajib diisi untuk semua item.',
                ]);
            }

            $approvedQty = round((float) $approvedItems[$item->id]['qty_base_approved'], 5);

            if ($approvedQty > (float) $item->qty_base_requested) {
                $rawMaterialName = $item->rawMaterial?->name ?: 'item ini';

                throw ValidationException::withMessages([
                    "items.{$item->id}.qty_base_approved" => "Qty approved {$rawMaterialName} tidak boleh melebihi qty base request.",
                ]);
            }

            $totalApproved += $approvedQty;
        }

        if ($totalApproved <= 0) {
            throw ValidationException::withMessages([
                'items' => 'Minimal ada satu qty approved yang lebih dari 0 untuk menyetujui request order.',
            ]);
        }

        DB::transaction(function () use ($requestOrder, $approvedItems) {
            foreach ($requestOrder->items as $item) {
                $item->update([
                    'qty_base_approved' => round((float) $approvedItems[$item->id]['qty_base_approved'], 5),
                ]);
            }

            $requestOrder->update([
                'status_id' => $this->statusId('approved'),
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
        });

        if ($request->expectsJson()) {
            return responseSuccess(false, 'Request order berhasil disetujui', $this->stats());
        }

        return redirect()
            ->route('warehouse/request-order/detail', $requestOrder->id)
            ->with('success', 'Request order berhasil disetujui.');
    }

    private function formData(RawMaterialRequests $requestOrder, string $action, string $title): array
    {
        $requestOrder->loadMissing(['items.rawMaterial.baseUnit', 'items.unit']);

        $selectedRawMaterialIds = $requestOrder->items->pluck('raw_material_id')->filter()->values();
        $selectedInventoryIds = collect([
            $requestOrder->requester_inventory_id,
            $requestOrder->fulfillment_inventory_id,
        ])->filter()->values();

        $rawMaterials = RawMaterials::with('baseUnit')
            ->where(function ($query) use ($selectedRawMaterialIds) {
                $query->where('is_active', 1)
                    ->orWhereIn('id', $selectedRawMaterialIds);
            })
            ->orderBy('name')
            ->get();
        $inventories = Inventory::where(function ($query) use ($selectedInventoryIds) {
            $query->where('is_active', 1)
                ->orWhereIn('id', $selectedInventoryIds);
        })
            ->orderBy('name')
            ->get();
        $units = Satuan::orderBy('name')->get();
        $conversions = RawMaterialUnitConversions::whereIn('raw_material_id', $rawMaterials->pluck('id'))->get();
        $stockBalances = InventoryRawMaterialStockBalance::with('inventory')
            ->whereIn('raw_material_id', $rawMaterials->pluck('id'))
            ->get();

        return [
            'data' => $requestOrder,
            'action' => $action,
            'title' => $title,
            'inventories' => $inventories,
            'rawMaterials' => $rawMaterials,
            'units' => $units,
            'unitOptionsByRawMaterial' => $this->unitOptionsByRawMaterial($rawMaterials, $units, $conversions),
            'rawMaterialStockInfo' => $this->rawMaterialStockInfo($rawMaterials, $stockBalances),
        ];
    }

    private function syncItems(RawMaterialRequests $requestOrder, array $items): void
    {
        $requestOrder->items()->delete();

        foreach ($items as $index => $item) {
            $requestOrder->items()->create([
                'raw_material_id' => $item['raw_material_id'],
                'qty_requested' => $item['qty_requested'],
                'unit_id' => $item['unit_id'],
                'qty_base_requested' => $this->toBaseQty(
                    (float) $item['qty_requested'],
                    (int) $item['raw_material_id'],
                    (int) $item['unit_id'],
                    $index
                ),
                'qty_base_approved' => null,
                'qty_base_fulfilled' => 0,
                'notes' => $item['notes'] ?? null,
            ]);
        }
    }

    private function toBaseQty(float $qty, int $rawMaterialId, int $unitId, int $index): float
    {
        $rawMaterial = RawMaterials::with('baseUnit')->findOrFail($rawMaterialId);

        if ((int) $rawMaterial->base_unit_id === $unitId) {
            return round($qty, 5);
        }

        $conversion = RawMaterialUnitConversions::where('raw_material_id', $rawMaterialId)
            ->where('from_unit_id', $unitId)
            ->where('to_unit_id', $rawMaterial->base_unit_id)
            ->first();

        if ($conversion) {
            return round($qty * (float) $conversion->multiplier, 5);
        }

        $reverseConversion = RawMaterialUnitConversions::where('raw_material_id', $rawMaterialId)
            ->where('from_unit_id', $rawMaterial->base_unit_id)
            ->where('to_unit_id', $unitId)
            ->first();

        if ($reverseConversion) {
            return round($qty / (float) $reverseConversion->multiplier, 5);
        }

        $unit = Satuan::find($unitId);
        $unitLabel = $unit?->symbol ?: $unit?->name ?: 'Satuan terpilih';
        $baseUnitLabel = $rawMaterial->baseUnit?->symbol ?: $rawMaterial->baseUnit?->name ?: 'satuan dasar';

        throw ValidationException::withMessages([
            "items.{$index}.unit_id" => "{$unitLabel} belum memiliki konversi ke {$baseUnitLabel} untuk {$rawMaterial->name}.",
        ]);
    }

    private function unitOptionsByRawMaterial($rawMaterials, $units, $conversions): array
    {
        return $rawMaterials
            ->mapWithKeys(function (RawMaterials $rawMaterial) use ($units, $conversions) {
                $rawMaterialConversions = $conversions
                    ->where('raw_material_id', $rawMaterial->id)
                    ->filter(fn (RawMaterialUnitConversions $conversion) => (int) $conversion->from_unit_id === (int) $rawMaterial->base_unit_id
                        || (int) $conversion->to_unit_id === (int) $rawMaterial->base_unit_id);

                $unitIds = collect([$rawMaterial->base_unit_id])
                    ->merge($rawMaterialConversions->pluck('from_unit_id'))
                    ->merge($rawMaterialConversions->pluck('to_unit_id'))
                    ->filter()
                    ->unique()
                    ->values();

                return [
                    $rawMaterial->id => $units
                        ->whereIn('id', $unitIds)
                        ->map(fn (Satuan $unit) => [
                            'id' => $unit->id,
                            'name' => $unit->name,
                            'symbol' => $unit->symbol,
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->all();
    }

    private function rawMaterialStockInfo($rawMaterials, $stockBalances): array
    {
        return $rawMaterials
            ->mapWithKeys(function (RawMaterials $rawMaterial) use ($stockBalances) {
                $balances = $stockBalances->where('raw_material_id', $rawMaterial->id);
                $locations = $balances
                    ->map(function (InventoryRawMaterialStockBalance $balance) {
                        $available = (float) $balance->qty_available;
                        $reserved = (float) $balance->qty_reserved;

                        return [
                            'inventory_id' => $balance->inventory_id,
                            'inventory_name' => $balance->inventory?->name ?? 'Inventory #' . $balance->inventory_id,
                            'qty_available' => $available,
                            'qty_reserved' => $reserved,
                            'qty_free' => $available - $reserved,
                        ];
                    })
                    ->sortBy('inventory_name')
                    ->values()
                    ->all();

                return [
                    $rawMaterial->id => [
                        'code' => $rawMaterial->code,
                        'name' => $rawMaterial->name,
                        'base_unit' => $rawMaterial->baseUnit?->symbol ?: $rawMaterial->baseUnit?->name,
                        'total_available' => array_sum(array_column($locations, 'qty_available')),
                        'total_reserved' => array_sum(array_column($locations, 'qty_reserved')),
                        'total_free' => array_sum(array_column($locations, 'qty_free')),
                        'locations' => $locations,
                    ],
                ];
            })
            ->all();
    }

    private function ensureDraft(RawMaterialRequests $requestOrder): void
    {
        $requestOrder->loadMissing('status');

        if ($requestOrder->status?->code !== 'draft') {
            throw ValidationException::withMessages([
                'status' => 'Request order hanya bisa diubah saat masih berstatus draft.',
            ]);
        }
    }

    private function ensureSubmitted(RawMaterialRequests $requestOrder): void
    {
        $requestOrder->loadMissing('status');

        if ($requestOrder->status?->code !== 'submitted') {
            throw ValidationException::withMessages([
                'status' => 'Request order hanya bisa disetujui saat berstatus submitted.',
            ]);
        }
    }

    private function statusId(string $code): int
    {
        $statusId = RawMaterialRequestStatus::where('code', $code)->value('id');

        if (!$statusId) {
            throw ValidationException::withMessages([
                'status' => "Status {$code} belum tersedia di raw_material_request_statuses.",
            ]);
        }

        return (int) $statusId;
    }

    private function nextRequestNumber(): string
    {
        $prefix = 'RO-' . now()->format('Ymd') . '-';
        $latestNumber = RawMaterialRequests::withTrashed()
            ->where('request_number', 'like', $prefix . '%')
            ->orderByDesc('request_number')
            ->value('request_number');

        $nextNumber = $latestNumber ? ((int) substr($latestNumber, -4)) + 1 : 1;

        do {
            $requestNumber = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (RawMaterialRequests::withTrashed()->where('request_number', $requestNumber)->exists());

        return $requestNumber;
    }

    private function stats(): array
    {
        $statusCounts = RawMaterialRequests::query()
            ->join('raw_material_request_statuses', 'raw_material_requests.status_id', '=', 'raw_material_request_statuses.id')
            ->selectRaw('raw_material_request_statuses.code, COUNT(*) as total')
            ->groupBy('raw_material_request_statuses.code')
            ->pluck('total', 'code');

        return [
            'total' => RawMaterialRequests::count(),
            'draft' => (int) ($statusCounts['draft'] ?? 0),
            'submitted' => (int) ($statusCounts['submitted'] ?? 0),
            'pending_review' => (int) ($statusCounts['submitted'] ?? 0),
            'approved' => (int) ($statusCounts['approved'] ?? 0),
            'partially_fulfilled' => (int) ($statusCounts['partially_fulfilled'] ?? 0),
            'fulfilled' => (int) ($statusCounts['fulfilled'] ?? 0),
            'rejected' => (int) ($statusCounts['rejected'] ?? 0),
        ];
    }

    private function requestOrderStats(RawMaterialRequests $requestOrder): array
    {
        $items = $requestOrder->items;

        return [
            'total_items' => $items->count(),
            'total_approved_qty_base' => $items->sum(fn ($item) => (float) ($item->qty_base_approved ?? 0)),
            'total_fulfilled_qty_base' => $items->sum(fn ($item) => (float) ($item->qty_base_fulfilled ?? 0)),
        ];
    }
}
