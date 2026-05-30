<?php

namespace App\Http\Controllers;

use App\DataTables\ProcurementPlanDataTable;
use App\Models\Inventory;
use App\Models\InventoryRawMaterialStockBalance;
use App\Models\ProcurementPlanItemSources;
use App\Models\ProcurementPlanStatus;
use App\Models\ProcurementPlans;
use App\Models\RawMaterialRequestItems;
use App\Models\SupplierRawMaterials;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProcurementPlanController extends Controller
{
    public function index(ProcurementPlanDataTable $datatable)
    {
        return $datatable->render('layouts.procurement_plan.index', [
            'stats' => $this->stats(),
            'statuses' => ProcurementPlanStatus::where('is_active', 1)->orderBy('sort_order')->get(),
            'inventories' => Inventory::where('is_active', 1)->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        $sourceItems = $this->availableApprovedRequestItems();
        $rawMaterialIds = $sourceItems->pluck('raw_material_id')->unique()->values();

        return view('layouts.procurement_plan.form', [
            'title' => 'Tambah Procurement Plan',
            'action' => route('warehouse/procurement-plan/store'),
            'planNumber' => $this->nextPlanNumber(),
            'inventories' => Inventory::where('is_active', 1)->orderBy('name')->get(),
            'sourceItems' => $sourceItems,
            'sourceGroups' => $this->sourceGroups($sourceItems),
            'supplierOptionsByRawMaterial' => $this->supplierOptionsByRawMaterial($rawMaterialIds),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'plan_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('procurement_plans', 'plan_number'),
            ],
            'planning_location_id' => ['required', 'exists:inventory,id'],
            'notes' => ['nullable', 'string'],
            'selected_sources' => ['required', 'array', 'min:1'],
            'selected_sources.*' => ['integer'],
            'supplier_raw_materials' => ['nullable', 'array'],
        ], [
            'planning_location_id.required' => 'Lokasi planning wajib dipilih.',
            'planning_location_id.exists' => 'Lokasi planning tidak valid.',
            'selected_sources.required' => 'Pilih minimal satu item request order yang sudah approved.',
            'selected_sources.min' => 'Pilih minimal satu item request order yang sudah approved.',
        ]);

        $selectedSourceIds = collect($validated['selected_sources'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $sourceItems = $this->availableApprovedRequestItems()
            ->whereIn('id', $selectedSourceIds)
            ->keyBy('id');

        $allocations = $selectedSourceIds
            ->mapWithKeys(function ($sourceId) use ($sourceItems) {
                $sourceItem = $sourceItems->get((int) $sourceId);

                if (!$sourceItem) {
                    throw ValidationException::withMessages([
                        'selected_sources' => 'Ada item request order yang tidak tersedia atau sudah dialokasikan.',
                    ]);
                }

                $allocation = round((float) $sourceItem->remaining_qty_base, 5);

                return [(int) $sourceId => $allocation];
            });

        $selectedItems = $sourceItems
            ->filter(fn (RawMaterialRequestItems $item) => $allocations->has($item->id))
            ->groupBy('raw_material_id');
        $supplierRawMaterials = SupplierRawMaterials::with(['supplier', 'purchaseUnit'])
            ->whereIn('id', collect($request->input('supplier_raw_materials', []))->filter()->values())
            ->where('is_active', true)
            ->whereHas('supplier', fn ($supplier) => $supplier->where('is_active', true))
            ->get()
            ->keyBy('id');
        $selectedSupplierRawMaterials = collect();

        foreach ($selectedItems as $rawMaterialId => $items) {
            $supplierRawMaterialId = (int) data_get($request->input('supplier_raw_materials', []), $rawMaterialId);
            $supplierRawMaterial = $supplierRawMaterials->get($supplierRawMaterialId);

            if (!$supplierRawMaterial || (int) $supplierRawMaterial->raw_material_id !== (int) $rawMaterialId) {
                $rawMaterialName = $items->first()->rawMaterial?->name ?: 'bahan baku ini';

                throw ValidationException::withMessages([
                    "supplier_raw_materials.{$rawMaterialId}" => "Pilih supplier yang valid untuk {$rawMaterialName}.",
                ]);
            }

            $selectedSupplierRawMaterials->put((int) $rawMaterialId, $supplierRawMaterial);
        }

        $procurementPlan = DB::transaction(function () use ($validated, $allocations, $selectedItems, $selectedSupplierRawMaterials) {
            $procurementPlan = ProcurementPlans::create([
                'plan_number' => $validated['plan_number'] ?: $this->nextPlanNumber(),
                'planning_location_id' => $validated['planning_location_id'],
                'status_id' => $this->statusId(ProcurementPlanStatus::DRAFT),
                'planned_by' => auth()->id(),
                'approved_by' => null,
                'planned_at' => now(),
                'approved_at' => null,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($selectedItems as $rawMaterialId => $items) {
                $requiredQty = $items->sum(fn (RawMaterialRequestItems $item) => (float) $allocations->get($item->id));
                $availableQty = $this->availableQtyBase((int) $rawMaterialId, (int) $validated['planning_location_id']);
                $shortageQty = max($requiredQty - $availableQty, 0);
                $supplierRawMaterial = $selectedSupplierRawMaterials->get((int) $rawMaterialId);
                $estimatedUnitPrice = $supplierRawMaterial->current_price !== null ? (float) $supplierRawMaterial->current_price : null;
                $estimatedSubtotal = $estimatedUnitPrice !== null ? $shortageQty * $estimatedUnitPrice : null;

                $planItem = $procurementPlan->items()->create([
                    'raw_material_id' => $rawMaterialId,
                    'supplier_id' => $supplierRawMaterial->supplier_id,
                    'supplier_raw_material_id' => $supplierRawMaterial->id,
                    'qty_required_base' => round($requiredQty, 5),
                    'qty_available_base' => round($availableQty, 5),
                    'qty_shortage_base' => round($shortageQty, 5),
                    'qty_to_purchase_base' => round($shortageQty, 5),
                    'unit_id' => $items->first()->rawMaterial->base_unit_id,
                    'estimated_unit_price' => $estimatedUnitPrice,
                    'estimated_subtotal' => $estimatedSubtotal !== null ? round($estimatedSubtotal, 2) : null,
                    'notes' => null,
                ]);

                foreach ($items as $sourceItem) {
                    $planItem->sources()->create([
                        'raw_material_request_item_id' => $sourceItem->id,
                        'qty_base_allocated' => $allocations->get($sourceItem->id),
                    ]);
                }
            }

            return $procurementPlan;
        });

        return redirect()
            ->route('warehouse/procurement-plan/detail', $procurementPlan->id)
            ->with('success', 'Procurement plan berhasil dibuat sebagai draft.');
    }

    public function detail(ProcurementPlans $procurementPlan)
    {
        $procurementPlan->load([
            'status',
            'planningLocation',
            'plannedBy',
            'approvedBy',
            'items.rawMaterial.baseUnit',
            'items.unit',
            'items.supplier',
            'items.supplierRawMaterial.purchaseUnit',
            'items.sources.rawMaterialRequestItem.rawMaterial',
            'items.sources.rawMaterialRequestItem.rawMaterialRequest.requesterInventory',
            'items.sources.rawMaterialRequestItem.rawMaterialRequest.fulfillmentLocation',
        ]);

        return view('layouts.procurement_plan.show', [
            'procurementPlan' => $procurementPlan,
            'stats' => $this->planStats($procurementPlan),
        ]);
    }

    public function destroy(ProcurementPlans $procurementPlan)
    {
        $this->ensureDraft($procurementPlan);

        DB::transaction(function () use ($procurementPlan) {
            $procurementPlan->items()->delete();
            $procurementPlan->delete();
        });

        return responseSuccess(false, 'Procurement plan draft berhasil dihapus', $this->stats());
    }

    private function availableApprovedRequestItems(): Collection
    {
        $allocatedQtyByRequestItem = ProcurementPlanItemSources::query()
            ->join('procurement_plan_items', 'procurement_plan_item_sources.procurement_plan_item_id', '=', 'procurement_plan_items.id')
            ->join('procurement_plans', 'procurement_plan_items.procurement_plan_id', '=', 'procurement_plans.id')
            ->whereNull('procurement_plans.deleted_at')
            ->selectRaw('procurement_plan_item_sources.raw_material_request_item_id, SUM(procurement_plan_item_sources.qty_base_allocated) as total_allocated')
            ->groupBy('procurement_plan_item_sources.raw_material_request_item_id')
            ->pluck('total_allocated', 'raw_material_request_item_id');

        return RawMaterialRequestItems::query()
            ->with([
                'rawMaterial.baseUnit',
                'unit',
                'rawMaterialRequest.status',
                'rawMaterialRequest.requesterInventory',
                'rawMaterialRequest.fulfillmentLocation',
            ])
            ->whereNotNull('qty_base_approved')
            ->where('qty_base_approved', '>', 0)
            ->whereHas('rawMaterialRequest.status', fn ($status) => $status->where('code', 'approved'))
            ->orderBy('raw_material_request_id')
            ->orderBy('raw_material_id')
            ->get()
            ->map(function (RawMaterialRequestItems $item) use ($allocatedQtyByRequestItem) {
                $allocatedQty = (float) ($allocatedQtyByRequestItem[$item->id] ?? 0);
                $approvedQty = (float) $item->qty_base_approved;

                $item->allocated_qty_base = round($allocatedQty, 5);
                $item->remaining_qty_base = round(max($approvedQty - $allocatedQty, 0), 5);

                return $item;
            })
            ->filter(fn (RawMaterialRequestItems $item) => $item->remaining_qty_base > 0)
            ->values();
    }

    private function sourceGroups(Collection $sourceItems): Collection
    {
        return $sourceItems
            ->groupBy('raw_material_id')
            ->map(function (Collection $items) {
                $firstItem = $items->first();

                return (object) [
                    'raw_material_id' => $firstItem->raw_material_id,
                    'raw_material' => $firstItem->rawMaterial,
                    'items' => $items->values(),
                    'total_approved_base' => $items->sum(fn (RawMaterialRequestItems $item) => (float) $item->qty_base_approved),
                    'total_allocated_base' => $items->sum(fn (RawMaterialRequestItems $item) => (float) $item->allocated_qty_base),
                    'total_remaining_base' => $items->sum(fn (RawMaterialRequestItems $item) => (float) $item->remaining_qty_base),
                ];
            })
            ->sortBy(fn ($group) => $group->raw_material?->name)
            ->values();
    }

    private function supplierOptionsByRawMaterial(Collection $rawMaterialIds): array
    {
        return SupplierRawMaterials::with(['supplier', 'purchaseUnit'])
            ->whereIn('raw_material_id', $rawMaterialIds)
            ->where('is_active', true)
            ->whereHas('supplier', fn ($supplier) => $supplier->where('is_active', true))
            ->orderByDesc('is_preferred')
            ->get()
            ->groupBy('raw_material_id')
            ->map(function (Collection $supplierRawMaterials) {
                return $supplierRawMaterials
                    ->sortByDesc('is_preferred')
                    ->map(fn (SupplierRawMaterials $supplierRawMaterial) => [
                        'id' => $supplierRawMaterial->id,
                        'supplier_id' => $supplierRawMaterial->supplier_id,
                        'supplier_name' => $supplierRawMaterial->supplier?->name ?? 'Supplier #' . $supplierRawMaterial->supplier_id,
                        'supplier_material_name' => $supplierRawMaterial->supplier_material_name,
                        'purchase_unit' => $supplierRawMaterial->purchaseUnit?->symbol ?: $supplierRawMaterial->purchaseUnit?->name,
                        'current_price' => $supplierRawMaterial->current_price !== null ? (float) $supplierRawMaterial->current_price : null,
                        'is_preferred' => (bool) $supplierRawMaterial->is_preferred,
                    ])
                    ->values()
                    ->all();
            })
            ->all();
    }

    private function availableQtyBase(int $rawMaterialId, int $inventoryId): float
    {
        $balance = InventoryRawMaterialStockBalance::where('raw_material_id', $rawMaterialId)
            ->where('inventory_id', $inventoryId)
            ->first();

        if (!$balance) {
            return 0;
        }

        return max((float) $balance->qty_available - (float) $balance->qty_reserved, 0);
    }

    private function ensureDraft(ProcurementPlans $procurementPlan): void
    {
        $procurementPlan->loadMissing('status');

        if ($procurementPlan->status?->code !== ProcurementPlanStatus::DRAFT) {
            throw ValidationException::withMessages([
                'status' => 'Procurement plan hanya bisa dihapus saat masih berstatus draft.',
            ]);
        }
    }

    private function statusId(string $code): int
    {
        $statusId = ProcurementPlanStatus::where('code', $code)->value('id');

        if (!$statusId) {
            throw ValidationException::withMessages([
                'status' => "Status {$code} belum tersedia di procurement_plan_statuses.",
            ]);
        }

        return (int) $statusId;
    }

    private function nextPlanNumber(): string
    {
        $prefix = 'PP-' . now()->format('Ymd') . '-';
        $latestNumber = ProcurementPlans::withTrashed()
            ->where('plan_number', 'like', $prefix . '%')
            ->orderByDesc('plan_number')
            ->value('plan_number');

        $nextNumber = $latestNumber ? ((int) substr($latestNumber, -4)) + 1 : 1;

        do {
            $planNumber = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (ProcurementPlans::withTrashed()->where('plan_number', $planNumber)->exists());

        return $planNumber;
    }

    private function stats(): array
    {
        $statusCounts = ProcurementPlans::query()
            ->join('procurement_plan_statuses', 'procurement_plans.status_id', '=', 'procurement_plan_statuses.id')
            ->selectRaw('procurement_plan_statuses.code, COUNT(*) as total')
            ->groupBy('procurement_plan_statuses.code')
            ->pluck('total', 'code');

        return [
            'total' => ProcurementPlans::count(),
            'draft' => (int) ($statusCounts['draft'] ?? 0),
            'reviewed' => (int) ($statusCounts['reviewed'] ?? 0),
            'approved' => (int) ($statusCounts['approved'] ?? 0),
            'converted_to_po' => (int) ($statusCounts['converted_to_po'] ?? 0),
            'cancelled' => (int) ($statusCounts['cancelled'] ?? 0),
            'available_sources' => $this->availableApprovedRequestItems()->count(),
        ];
    }

    private function planStats(ProcurementPlans $procurementPlan): array
    {
        $items = $procurementPlan->items;

        return [
            'total_items' => $items->count(),
            'total_sources' => $items->sum(fn ($item) => $item->sources->count()),
            'qty_required_base' => $items->sum(fn ($item) => (float) $item->qty_required_base),
            'qty_available_base' => $items->sum(fn ($item) => (float) $item->qty_available_base),
            'qty_shortage_base' => $items->sum(fn ($item) => (float) $item->qty_shortage_base),
            'qty_to_purchase_base' => $items->sum(fn ($item) => (float) $item->qty_to_purchase_base),
            'estimated_total' => $items->sum(fn ($item) => (float) ($item->estimated_subtotal ?? 0)),
        ];
    }
}
