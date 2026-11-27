<?php

namespace App\Http\Controllers;

use App\DataTables\SupplierDataTable;
use App\Enums\ProcurementMode;
use App\Models\RawMaterials;
use App\Models\Satuan;
use App\Models\Supplier;
use App\Models\SupplierRawMaterialPriceHistories;
use App\Models\SupplierRawMaterials;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
            'procurement_mode' => ['required', 'integer', Rule::in(ProcurementMode::values())],
            'is_active' => ['required', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['procurement_mode'] = (int) $validated['procurement_mode'];

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
            'procurement_mode' => ['required', 'integer', Rule::in(ProcurementMode::values())],
            'is_active' => ['required', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['procurement_mode'] = (int) $validated['procurement_mode'];

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

        $stats = [
            'total' => Supplier::count(),
            'active' => Supplier::where('is_active', 1)->count(),
            'inactive' => Supplier::where('is_active', 0)->count(),
            'supplied_materials' => SupplierRawMaterials::count(),
        ];

        return responseSuccess(false, 'Supplier berhasil dihapus', $stats);
    }

    public function show(Supplier $supplier)
    {
        $supplier->load([
            'contacts' => fn ($contacts) => $contacts->orderByDesc('is_primary')->orderBy('name'),
            'orderChannels' => fn ($channels) => $channels->with('channelType')->orderByDesc('is_primary')->orderBy('id'),
            'operationalHours' => fn ($hours) => $hours->orderBy('day_of_week')->orderBy('sequence'),
            'rawMaterials' => fn ($materials) => $materials->with(['rawMaterial', 'purchaseUnit'])
                ->orderByDesc('is_preferred')
                ->orderBy('id'),
        ]);

        $priceHistories = $this->priceHistories($supplier);
        $materials = $supplier->rawMaterials;

        $stats = [
            'materials' => $materials->count(),
            'active_materials' => $materials->where('is_active', true)->count(),
            'preferred' => $materials->where('is_preferred', true)->count(),
            'avg_lead_time' => $materials->isEmpty() ? null : round((float) $materials->avg('lead_time_days'), 1),
            'price_changes' => $priceHistories->count(),
            'last_price_change' => $priceHistories->first()?->effective_from,
        ];

        return view('layouts.supplier.show', compact('supplier', 'priceHistories', 'stats'));
    }

    public function createRawMaterial(Supplier $supplier)
    {
        return view('layouts.supplier.raw-material-modal', [
            'supplier' => $supplier,
            'rawMaterials' => $this->rawMaterialOptions(),
            'units' => Satuan::orderBy('name')->get(),
            'action' => route('warehouse/supplier/raw-materials/store', $supplier->id),
            'item' => new SupplierRawMaterials(),
        ]);
    }

    public function editRawMaterial(Supplier $supplier, SupplierRawMaterials $supplierRawMaterial)
    {
        $this->ensureBelongsToSupplier($supplier, $supplierRawMaterial);

        return view('layouts.supplier.raw-material-modal', [
            'supplier' => $supplier,
            'rawMaterials' => $this->rawMaterialOptions($supplierRawMaterial->raw_material_id),
            'units' => Satuan::orderBy('name')->get(),
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

        $this->ensurePurchaseUnitConvertible($validated);

        $currentPrice = $validated['current_price'] ?? null;

        $supplierRawMaterial = SupplierRawMaterials::create(array_merge($validated, [
            'supplier_id' => $supplier->id,
            'current_price' => $currentPrice,
            'price_updated_at' => $currentPrice !== null ? now() : null,
        ]));

        if ($currentPrice !== null) {
            SupplierRawMaterialPriceHistories::create([
                'supplier_raw_material_id' => $supplierRawMaterial->id,
                'price' => $currentPrice,
                'effective_from' => now()->toDateString(),
                'updated_by' => $request->user()->id,
                'tax_type' => 'non_tax',
                'notes' => 'Harga awal',
            ]);
        }

        return responseSuccess(false, 'Bahan baku supplier berhasil ditambahkan');
    }

    public function updateRawMaterial(Request $request, Supplier $supplier, SupplierRawMaterials $supplierRawMaterial)
    {
        $this->ensureBelongsToSupplier($supplier, $supplierRawMaterial);

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

        $this->ensurePurchaseUnitConvertible($validated);

        $oldPrice = $supplierRawMaterial->current_price;
        $currentPrice = $validated['current_price'] ?? null;

        $supplierRawMaterial->update(array_merge($validated, [
            'current_price' => $currentPrice,
            'price_updated_at' => $currentPrice !== null ? now() : null,
        ]));

        if ($currentPrice !== null && $currentPrice != $oldPrice) {
            SupplierRawMaterialPriceHistories::create([
                'supplier_raw_material_id' => $supplierRawMaterial->id,
                'price' => $currentPrice,
                'effective_from' => now()->toDateString(),
                'updated_by' => $request->user()->id,
                'tax_type' => 'non_tax',
                'notes' => $oldPrice !== null
                    ? 'Harga sebelumnya Rp ' . number_format((float) $oldPrice, 2, ',', '.')
                    : 'Harga pertama diisi',
            ]);
        }

        return responseSuccess(true, 'Bahan baku supplier berhasil diperbarui');
    }

    public function destroyRawMaterial(Supplier $supplier, SupplierRawMaterials $supplierRawMaterial)
    {
        $this->ensureBelongsToSupplier($supplier, $supplierRawMaterial);

        $supplierRawMaterial->delete();

        return responseSuccess(false, 'Bahan baku dihapus dari supplier');
    }

    /**
     * Histori harga terbaru di atas, lengkap dengan harga sebelumnya per bahan untuk menghitung selisih.
     */
    private function priceHistories(Supplier $supplier): Collection
    {
        $lastPrice = [];

        return SupplierRawMaterialPriceHistories::with(['supplierRawMaterial.rawMaterial', 'supplierRawMaterial.purchaseUnit', 'updatedBy'])
            ->whereIn('supplier_raw_material_id', $supplier->rawMaterials->pluck('id'))
            ->orderBy('effective_from')
            ->orderBy('id')
            ->get()
            ->each(function (SupplierRawMaterialPriceHistories $history) use (&$lastPrice) {
                $history->previous_price = $lastPrice[$history->supplier_raw_material_id] ?? null;
                $lastPrice[$history->supplier_raw_material_id] = (float) $history->price;
            })
            ->reverse()
            ->values();
    }

    /**
     * Bahan baku aktif, ditambah bahan yang sedang dipakai item ini walau sudah nonaktif agar pilihannya tidak hilang saat edit.
     */
    private function rawMaterialOptions(?int $currentRawMaterialId = null): Collection
    {
        return RawMaterials::with('baseUnit')
            ->where(fn ($query) => $query->where('is_active', true)
                ->when($currentRawMaterialId, fn ($query) => $query->orWhere('id', $currentRawMaterialId)))
            ->orderBy('name')
            ->get();
    }

    /**
     * Harga disimpan per satuan beli; qty kebutuhan dalam satuan dasar. Tanpa konversi
     * satuan beli → satuan dasar, qty beli dan nilai PO tidak bisa dihitung.
     */
    private function ensurePurchaseUnitConvertible(array $validated): void
    {
        $rawMaterial = RawMaterials::with('baseUnit')->findOrFail($validated['raw_material_id']);

        if ($rawMaterial->conversionToBase((int) $validated['purchase_unit_id']) !== null) {
            return;
        }

        $purchaseUnit = Satuan::find($validated['purchase_unit_id']);

        throw ValidationException::withMessages([
            'purchase_unit_id' => sprintf(
                '%s belum punya konversi ke %s untuk %s. Tambahkan dulu di Library → Satuan, tab Konversi per Bahan Baku.',
                $purchaseUnit?->name ?? 'Satuan ini',
                $rawMaterial->baseUnit?->name ?? 'satuan dasar',
                $rawMaterial->name
            ),
        ]);
    }

    private function ensureBelongsToSupplier(Supplier $supplier, SupplierRawMaterials $supplierRawMaterial): void
    {
        abort_unless((int) $supplierRawMaterial->supplier_id === (int) $supplier->id, 404);
    }
}
