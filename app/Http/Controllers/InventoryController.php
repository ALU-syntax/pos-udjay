<?php

namespace App\Http\Controllers;

use App\DataTables\InventoryLocationsDataTable;
use App\Http\Requests\InventoryLocationRequest;
use App\Http\Requests\InventoryStockBalanceRequest;
use App\Models\Brand;
use App\Models\InventoryLocation;
use App\Models\InventoryLocationType;
use App\Models\Outlets;
use App\Models\RawMaterials;
use App\Models\InventoryRawMaterialStockBalance;

class InventoryController extends Controller
{
    public function index(InventoryLocationsDataTable $datatable)
    {
        return $datatable->render('layouts.inventory.index', [
            'stats' => $this->stats(),
            'types' => InventoryLocationType::orderBy('name')->get(),
            'parents' => InventoryLocation::orderBy('name')->get(),
            'outlets' => Outlets::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'latestInventory' => InventoryLocation::latest('updated_at')->first(),
        ]);
    }

    public function create()
    {
        return view('layouts.inventory.modal', $this->formData(
            new InventoryLocation(),
            route('warehouse/inventory/store')
        ));
    }

    public function store(InventoryLocationRequest $request)
    {
        InventoryLocation::create($request->validated());

        return responseSuccess(false, 'Lokasi inventory berhasil ditambahkan', $this->stats());
    }

    public function detail(InventoryLocation $inventoryLocation)
    {
        $inventoryLocation->load([
            'type',
            'parent',
            'outlet',
            'brand',
            'stockBalances' => fn ($query) => $query
                ->with(['rawMaterial.category', 'rawMaterial.baseUnit', 'rawMaterial.storageType'])
                ->orderBy('raw_material_id'),
        ]);

        return view('layouts.inventory.show', [
            'inventoryLocation' => $inventoryLocation,
            'stockBalances' => $inventoryLocation->stockBalances,
            'stockStats' => $this->stockStats($inventoryLocation),
        ]);
    }

    public function edit(InventoryLocation $inventoryLocation)
    {
        return view('layouts.inventory.modal', $this->formData(
            $inventoryLocation,
            route('warehouse/inventory/update', $inventoryLocation->id)
        ));
    }

    public function update(InventoryLocationRequest $request, InventoryLocation $inventoryLocation)
    {
        $inventoryLocation->update($request->validated());

        return responseSuccess(true, 'Lokasi inventory berhasil diperbarui', $this->stats());
    }

    public function destroy(InventoryLocation $inventoryLocation)
    {
        $inventoryLocation->delete();

        return responseSuccess(false, 'Lokasi inventory berhasil diarsipkan', $this->stats());
    }

    public function createStockBalance(InventoryLocation $inventoryLocation)
    {
        return view('layouts.inventory.stock-modal', [
            'inventoryLocation' => $inventoryLocation,
            'item' => new InventoryRawMaterialStockBalance(),
            'rawMaterials' => $this->availableRawMaterials(),
            'action' => route('warehouse/inventory/stock-balances/store', $inventoryLocation->id),
        ]);
    }

    public function storeStockBalance(InventoryStockBalanceRequest $request, InventoryLocation $inventoryLocation)
    {
        InventoryRawMaterialStockBalance::create(array_merge(
            $request->validated(),
            ['inventory_location_id' => $inventoryLocation->id]
        ));

        return responseSuccess(false, 'Bahan baku inventory berhasil ditambahkan', $this->stockStats($inventoryLocation));
    }

    public function editStockBalance(InventoryLocation $inventoryLocation, InventoryRawMaterialStockBalance $stockBalance)
    {
        $this->ensureStockBelongsToLocation($inventoryLocation, $stockBalance);

        return view('layouts.inventory.stock-modal', [
            'inventoryLocation' => $inventoryLocation,
            'item' => $stockBalance,
            'rawMaterials' => $this->availableRawMaterials(),
            'action' => route('warehouse/inventory/stock-balances/update', [$inventoryLocation->id, $stockBalance->id]),
            'update' => true,
        ]);
    }

    public function updateStockBalance(
        InventoryStockBalanceRequest $request,
        InventoryLocation $inventoryLocation,
        InventoryRawMaterialStockBalance $stockBalance
    ) {
        $this->ensureStockBelongsToLocation($inventoryLocation, $stockBalance);

        $stockBalance->update($request->validated());

        return responseSuccess(true, 'Stok bahan baku berhasil diperbarui', $this->stockStats($inventoryLocation));
    }

    public function destroyStockBalance(InventoryLocation $inventoryLocation, InventoryRawMaterialStockBalance $stockBalance)
    {
        $this->ensureStockBelongsToLocation($inventoryLocation, $stockBalance);

        $stockBalance->delete();

        return responseSuccess(false, 'Stok bahan baku berhasil dihapus', $this->stockStats($inventoryLocation));
    }

    private function formData(InventoryLocation $inventoryLocation, string $action): array
    {
        $parents = InventoryLocation::query()
            ->when($inventoryLocation->exists, fn ($query) => $query->where('id', '!=', $inventoryLocation->id))
            ->orderBy('name')
            ->get();

        $types = InventoryLocationType::query()
            ->when(
                $inventoryLocation->inventory_location_type_id,
                fn ($query) => $query->where('id', $inventoryLocation->inventory_location_type_id)->orWhere('is_active', 1),
                fn ($query) => $query->where('is_active', 1)
            )
            ->orderBy('name')
            ->get();

        return [
            'data' => $inventoryLocation,
            'action' => $action,
            'parents' => $parents,
            'types' => $types,
            'outlets' => Outlets::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
        ];
    }

    private function availableRawMaterials()
    {
        return RawMaterials::query()
            ->with('baseUnit')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get();
    }

    private function stats(): array
    {
        return [
            'total' => InventoryLocation::count(),
            'active' => InventoryLocation::where('is_active', 1)->count(),
            'inactive' => InventoryLocation::where('is_active', 0)->count(),
            'assigned_outlet' => InventoryLocation::whereNotNull('outlet_id')->count(),
            'assigned_brand' => InventoryLocation::whereNotNull('brand_id')->count(),
            'stocked_locations' => InventoryRawMaterialStockBalance::distinct('inventory_location_id')->count('inventory_location_id'),
        ];
    }

    private function stockStats(InventoryLocation $inventoryLocation): array
    {
        $summary = InventoryRawMaterialStockBalance::query()
            ->where('inventory_location_id', $inventoryLocation->id)
            ->selectRaw('COUNT(*) as total_materials')
            ->selectRaw('COALESCE(SUM(qty_available), 0) as total_available')
            ->selectRaw('COALESCE(SUM(qty_reserved), 0) as total_reserved')
            ->first();

        $totalAvailable = (float) ($summary->total_available ?? 0);
        $totalReserved = (float) ($summary->total_reserved ?? 0);

        return [
            'total_materials' => (int) ($summary->total_materials ?? 0),
            'total_available' => $totalAvailable,
            'total_reserved' => $totalReserved,
            'total_free' => $totalAvailable - $totalReserved,
        ];
    }

    private function ensureStockBelongsToLocation(InventoryLocation $inventoryLocation, InventoryRawMaterialStockBalance $stockBalance): void
    {
        if ((int) $stockBalance->inventory_location_id !== (int) $inventoryLocation->id) {
            abort(404);
        }
    }
}
