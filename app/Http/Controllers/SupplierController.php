<?php

namespace App\Http\Controllers;

use App\DataTables\SupplierDataTable;
use App\Models\RawMaterials;
use App\Models\Satuan;
use App\Models\Supplier;
use App\Models\SupplierRawMaterialPriceHistories;
use App\Models\SupplierRawMaterials;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function index(SupplierDataTable $datatable)
    {
        $stats = [
            'total' => Supplier::count(),
            'active' => Supplier::where('is_active', 1)->count(),
            'inactive' => Supplier::where('is_active', 0)->count(),
            'supplied_materials' => SupplierRawMaterials::count(),
        ];

        return $datatable->render('layouts.supplier.index', compact('stats'));
    }

    public function create()
    {
        return view('layouts.supplier.supplier-modal', [
            'data' => new Supplier(),
            'action' => route('warehouse/supplier/store'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:50', Rule::unique('suppliers', 'code')],
            'name' => ['required', 'string', 'max:255', Rule::unique('suppliers', 'name')],
            'procurement_mode' => ['required', Rule::in(['online', 'offline', 'both'])],
            'is_active' => ['required', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        Supplier::create($validated);

        $stats = [
            'total' => Supplier::count(),
            'active' => Supplier::where('is_active', 1)->count(),
            'inactive' => Supplier::where('is_active', 0)->count(),
            'supplied_materials' => SupplierRawMaterials::count(),
        ];

        return responseSuccess(false, 'Supplier berhasil ditambahkan', $stats);
    }

    public function edit(Supplier $supplier)
    {
        return view('layouts.supplier.supplier-modal', [
            'data' => $supplier,
            'action' => route('warehouse/supplier/update', $supplier->id),
        ]);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:50', Rule::unique('suppliers', 'code')->ignore($supplier->id)],
            'name' => ['required', 'string', 'max:255', Rule::unique('suppliers', 'name')->ignore($supplier->id)],
            'procurement_mode' => ['required', Rule::in(['online', 'offline', 'both'])],
            'is_active' => ['required', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $supplier->update($validated);

        $stats = [
            'total' => Supplier::count(),
            'active' => Supplier::where('is_active', 1)->count(),
            'inactive' => Supplier::where('is_active', 0)->count(),
            'supplied_materials' => SupplierRawMaterials::count(),
        ];

        return responseSuccess(true, 'Supplier berhasil diperbarui', $stats);
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return responseSuccessDelete();
    }

    public function show(Supplier $supplier)
    {
        $supplier->load(['contacts', 'orderChannels', 'operationalHours', 'rawMaterials.rawMaterial', 'rawMaterials.purchaseUnit', 'rawMaterials.priceHistories']);

        $rawMaterials = RawMaterials::where('is_active', true)->orderBy('name')->get();
        $units = Satuan::orderBy('name')->get();

        return view('layouts.supplier.show', compact('supplier', 'rawMaterials', 'units'));
    }

    public function createRawMaterial(Supplier $supplier)
    {
        $rawMaterials = RawMaterials::where('is_active', true)->orderBy('name')->get();
        $units = Satuan::orderBy('name')->get();

        return view('layouts.supplier.raw-material-modal', [
            'supplier' => $supplier,
            'rawMaterials' => $rawMaterials,
            'units' => $units,
            'action' => route('warehouse/supplier/raw-materials/store', $supplier->id),
            'item' => new SupplierRawMaterials(),
        ]);
    }

    public function editRawMaterial(Supplier $supplier, SupplierRawMaterials $supplierRawMaterial)
    {
        $rawMaterials = RawMaterials::where('is_active', true)->orderBy('name')->get();
        $units = Satuan::orderBy('name')->get();

        return view('layouts.supplier.raw-material-modal', [
            'supplier' => $supplier,
            'rawMaterials' => $rawMaterials,
            'units' => $units,
            'action' => route('warehouse/supplier/raw-materials/update', [$supplier->id, $supplierRawMaterial->id]),
            'item' => $supplierRawMaterial,
            'update' => true,
        ]);
    }

    public function storeRawMaterial(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'raw_material_id' => ['required', 'exists:raw_materials,id'],
            'supplier_material_name' => ['nullable', 'string', 'max:255'],
            'supplier_sku' => ['nullable', 'string', 'max:100'],
            'purchase_unit_id' => ['required', 'exists:satuans,id'],
            'minimum_order_qty' => ['nullable', 'numeric', 'min:0'],
            'lead_time_days' => ['required', 'integer', 'min:0'],
            'current_price' => ['nullable', 'numeric', 'min:0'],
            'is_preferred' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $uniqueRule = Rule::unique('supplier_raw_materials')
            ->where(fn ($query) => $query->where('supplier_id', $supplier->id)
                ->where('raw_material_id', $validated['raw_material_id'])
                ->where('purchase_unit_id', $validated['purchase_unit_id']));

        $request->validate([
            'raw_material_id' => [$uniqueRule],
        ]);

        $supplierRawMaterial = SupplierRawMaterials::create(array_merge($validated, [
            'supplier_id' => $supplier->id,
            'current_price' => $validated['current_price'] ?? null,
            'price_updated_at' => $validated['current_price'] !== null ? now() : null,
        ]));

        if (!empty($validated['current_price'])) {
            SupplierRawMaterialPriceHistories::create([
                'supplier_raw_material_id' => $supplierRawMaterial->id,
                'price' => $validated['current_price'],
                'effective_from' => now()->toDateString(),
                'tax_type' => 'non_tax',
                'notes' => 'Initial price set',
            ]);
        }

        return responseSuccess(false, 'Bahan baku supplier berhasil ditambahkan');
    }

    public function updateRawMaterial(Request $request, Supplier $supplier, SupplierRawMaterials $supplierRawMaterial)
    {
        $validated = $request->validate([
            'raw_material_id' => ['required', 'exists:raw_materials,id'],
            'supplier_material_name' => ['nullable', 'string', 'max:255'],
            'supplier_sku' => ['nullable', 'string', 'max:100'],
            'purchase_unit_id' => ['required', 'exists:satuans,id'],
            'minimum_order_qty' => ['nullable', 'numeric', 'min:0'],
            'lead_time_days' => ['required', 'integer', 'min:0'],
            'current_price' => ['nullable', 'numeric', 'min:0'],
            'is_preferred' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $uniqueRule = Rule::unique('supplier_raw_materials')
            ->ignore($supplierRawMaterial->id)
            ->where(fn ($query) => $query->where('supplier_id', $supplier->id)
                ->where('raw_material_id', $validated['raw_material_id'])
                ->where('purchase_unit_id', $validated['purchase_unit_id']));

        $request->validate([
            'raw_material_id' => [$uniqueRule],
        ]);

        $oldPrice = $supplierRawMaterial->current_price;
        $supplierRawMaterial->update(array_merge($validated, [
            'current_price' => $validated['current_price'] ?? null,
            'price_updated_at' => $validated['current_price'] !== null ? now() : null,
        ]));

        if (!empty($validated['current_price']) && $validated['current_price'] != $oldPrice) {
            SupplierRawMaterialPriceHistories::create([
                'supplier_raw_material_id' => $supplierRawMaterial->id,
                'price' => $validated['current_price'],
                'effective_from' => now()->toDateString(),
                'tax_type' => 'non_tax',
                'notes' => 'Price updated from ' . ($oldPrice ?? 'N/A'),
            ]);
        }

        return responseSuccess(true, 'Bahan baku supplier berhasil diperbarui');
    }

    public function destroyRawMaterial(SupplierRawMaterials $supplierRawMaterial)
    {
        $supplierRawMaterial->delete();

        return responseSuccessDelete();
    }
}
