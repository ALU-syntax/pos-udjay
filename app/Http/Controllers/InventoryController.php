<?php

namespace App\Http\Controllers;

use App\DataTables\InventoryDataTable;
use App\Http\Requests\InventoryRequest;
use App\Http\Requests\InventoryStockBalanceRequest;
use App\Models\Brand;
use App\Models\Inventory;
use App\Models\InventoryType;
use App\Models\Outlets;
use App\Models\RawMaterials;
use App\Models\InventoryRawMaterialStockBalance;

class InventoryController extends Controller
{
    public function index(InventoryDataTable $datatable)
    {
        return $datatable->render('layouts.inventory.index', [
            'stats' => $this->stats(),
            'types' => InventoryType::orderBy('name')->get(),
            'outlets' => Outlets::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('layouts.inventory.modal', $this->formData(
            new Inventory(),
            route('warehouse/inventory/store')
        ));
    }

    public function store(InventoryRequest $request)
    {
        Inventory::create($request->validated());

        return responseSuccess(false, 'Lokasi inventory berhasil ditambahkan', $this->stats());
    }

    public function detail(Inventory $inventory)
    {
        $inventory->load([
            'type',
            'parent',
            'outlet',
            'brand',
            'children' => fn ($query) => $query->with('type')->orderBy('name'),
            'stockBalances.rawMaterial' => fn ($query) => $query->withTrashed()->with(['category', 'baseUnit', 'storageType']),
        ]);

        return view('layouts.inventory.show', [
            'inventory' => $inventory,
            'stockBalances' => $inventory->stockBalances
                ->sortBy(fn ($balance) => mb_strtolower($balance->rawMaterial?->name ?? ''))
                ->values(),
            'stockStats' => $this->stockStats($inventory),
        ]);
    }

    public function edit(Inventory $inventory)
    {
        return view('layouts.inventory.modal', $this->formData(
            $inventory,
            route('warehouse/inventory/update', $inventory->id)
        ));
    }

    public function update(InventoryRequest $request, Inventory $inventory)
    {
        $inventory->update($request->validated());

        return responseSuccess(true, 'Lokasi inventory berhasil diperbarui', $this->stats());
    }

    public function destroy(Inventory $inventory)
    {
        $inventory->delete();

        return responseSuccess(false, 'Lokasi inventory berhasil diarsipkan', $this->stats());
    }

    public function createStockBalance(Inventory $inventory)
    {
        return view('layouts.inventory.stock-modal', [
            'inventory' => $inventory,
            'item' => new InventoryRawMaterialStockBalance(),
            'rawMaterials' => $this->availableRawMaterials($inventory),
            'action' => route('warehouse/inventory/stock-balances/store', $inventory->id),
        ]);
    }

    public function storeStockBalance(InventoryStockBalanceRequest $request, Inventory $inventory)
    {
        InventoryRawMaterialStockBalance::create(array_merge(
            $request->validated(),
            ['inventory_id' => $inventory->id]
        ));

        return responseSuccess(false, 'Bahan baku inventory berhasil ditambahkan', $this->stockStats($inventory));
    }

    public function editStockBalance(Inventory $inventory, InventoryRawMaterialStockBalance $stockBalance)
    {
        $this->ensureStockBelongsToLocation($inventory, $stockBalance);

        return view('layouts.inventory.stock-modal', [
            'inventory' => $inventory,
            'item' => $stockBalance,
            'rawMaterials' => $this->availableRawMaterials($inventory, $stockBalance),
            'action' => route('warehouse/inventory/stock-balances/update', [$inventory->id, $stockBalance->id]),
            'update' => true,
        ]);
    }

    public function updateStockBalance(
        InventoryStockBalanceRequest $request,
        Inventory $inventory,
        InventoryRawMaterialStockBalance $stockBalance
    ) {
        $this->ensureStockBelongsToLocation($inventory, $stockBalance);

        $stockBalance->update($request->validated());

        return responseSuccess(true, 'Stok bahan baku berhasil diperbarui', $this->stockStats($inventory));
    }

    public function destroyStockBalance(Inventory $inventory, InventoryRawMaterialStockBalance $stockBalance)
    {
        $this->ensureStockBelongsToLocation($inventory, $stockBalance);

        $stockBalance->delete();

        return responseSuccess(false, 'Stok bahan baku berhasil dihapus', $this->stockStats($inventory));
    }

    private function formData(Inventory $inventory, string $action): array
    {
        $parents = Inventory::query()
            ->when($inventory->exists, fn ($query) => $query->where('id', '!=', $inventory->id))
            ->orderBy('name')
            ->get();

        $types = InventoryType::query()
            ->when(
                $inventory->inventory_type_id,
                fn ($query) => $query->where('id', $inventory->inventory_type_id)->orWhere('is_active', 1),
                fn ($query) => $query->where('is_active', 1)
            )
            ->orderBy('name')
            ->get();

        return [
            'data' => $inventory,
            'action' => $action,
            'parents' => $parents,
            'types' => $types,
            'outlets' => Outlets::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
        ];
    }

    /**
     * Bahan aktif yang belum ada di lokasi ini. Saat edit, bahan milik baris itu
     * tetap ikut walau sudah nonaktif agar pilihannya tidak hilang.
     */
    private function availableRawMaterials(Inventory $inventory, ?InventoryRawMaterialStockBalance $current = null)
    {
        $usedIds = InventoryRawMaterialStockBalance::query()
            ->where('inventory_id', $inventory->id)
            ->when($current, fn ($query) => $query->where('id', '!=', $current->id))
            ->pluck('raw_material_id');

        return RawMaterials::query()
            ->with('baseUnit')
            ->whereNotIn('id', $usedIds)
            ->where(fn ($query) => $query
                ->where('is_active', 1)
                ->when($current, fn ($query) => $query->orWhere('id', $current->raw_material_id)))
            ->orderBy('name')
            ->get();
    }

    private function stats(): array
    {
        return [
            'total' => Inventory::count(),
            'active' => Inventory::where('is_active', 1)->count(),
            'inactive' => Inventory::where('is_active', 0)->count(),
            // Lokasi yang diarsipkan tidak ikut dihitung
            'stocked_locations' => Inventory::has('stockBalances')->count(),
        ];
    }

    /** Hitungan bahan, bukan jumlah qty: tiap bahan punya satuan berbeda sehingga qty tidak bisa dijumlahkan. */
    private function stockStats(Inventory $inventory): array
    {
        $summary = InventoryRawMaterialStockBalance::query()
            ->where('inventory_id', $inventory->id)
            ->selectRaw('COUNT(*) as total_materials')
            ->selectRaw('COALESCE(SUM(CASE WHEN qty_available <= 0 THEN 1 ELSE 0 END), 0) as empty_materials')
            ->selectRaw('COALESCE(SUM(CASE WHEN qty_reserved > 0 THEN 1 ELSE 0 END), 0) as reserved_materials')
            ->first();

        return [
            'total_materials' => (int) ($summary->total_materials ?? 0),
            'empty_materials' => (int) ($summary->empty_materials ?? 0),
            'reserved_materials' => (int) ($summary->reserved_materials ?? 0),
        ];
    }

    private function ensureStockBelongsToLocation(Inventory $inventory, InventoryRawMaterialStockBalance $stockBalance): void
    {
        if ((int) $stockBalance->inventory_id !== (int) $inventory->id) {
            abort(404);
        }
    }
}
