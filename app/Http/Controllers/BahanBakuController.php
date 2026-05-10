<?php

namespace App\Http\Controllers;

use App\DataTables\RawMaterialsDataTable;
use App\Http\Requests\RawMaterialRequest;
use App\Models\RawMaterialCategories;
use App\Models\RawMaterials;
use App\Models\RawStorageType;
use App\Models\Satuan;

class BahanBakuController extends Controller
{
    public function index(RawMaterialsDataTable $datatable)
    {
        return $datatable->render('layouts.bahan_baku.index', [
            'stats' => $this->stats(),
            'categories' => RawMaterialCategories::orderBy('name')->get(),
            'storageTypes' => RawStorageType::orderBy('name')->get(),
            'latestMaterial' => RawMaterials::latest('updated_at')->first(),
        ]);
    }

    public function create()
    {
        return view('layouts.bahan_baku.modal', $this->formData(new RawMaterials(), route('library/bahan-baku/store')));
    }

    public function store(RawMaterialRequest $request)
    {
        $validated = $request->validated();
        $validated['code'] = $validated['code'] ?: $this->nextCode();

        RawMaterials::create($validated);

        return responseSuccess(false, 'Bahan baku berhasil ditambahkan', $this->stats());
    }

    public function detail(RawMaterials $rawMaterial)
    {
        $rawMaterial->load(['category', 'baseUnit', 'storageType']);

        return view('layouts.bahan_baku.detail-modal', compact('rawMaterial'));
    }

    public function edit(RawMaterials $rawMaterial)
    {
        return view('layouts.bahan_baku.modal', $this->formData($rawMaterial, route('library/bahan-baku/update', $rawMaterial->id)));
    }

    public function update(RawMaterialRequest $request, RawMaterials $rawMaterial)
    {
        $validated = $request->validated();
        $validated['code'] = $validated['code'] ?: $rawMaterial->code ?: $this->nextCode();

        $rawMaterial->update($validated);

        return responseSuccess(true, 'Bahan baku berhasil diperbarui', $this->stats());
    }

    public function destroy(RawMaterials $rawMaterial)
    {
        $rawMaterial->delete();

        return responseSuccess(false, 'Bahan baku berhasil diarsipkan', $this->stats());
    }

    private function formData(RawMaterials $rawMaterial, string $action): array
    {
        return [
            'data' => $rawMaterial,
            'action' => $action,
            'categories' => RawMaterialCategories::orderBy('name')->get(),
            'units' => Satuan::orderBy('name')->get(),
            'storageTypes' => RawStorageType::orderBy('name')->get(),
        ];
    }

    private function stats(): array
    {
        return [
            'total' => RawMaterials::count(),
            'active' => RawMaterials::where('is_active', 1)->count(),
            'inactive' => RawMaterials::where('is_active', 0)->count(),
            'categorized' => RawMaterials::whereNotNull('raw_material_category_id')->count(),
            'uncategorized' => RawMaterials::whereNull('raw_material_category_id')->count(),
        ];
    }

    private function nextCode(): string
    {
        $nextNumber = RawMaterials::withTrashed()->count() + 1;

        do {
            $code = 'RM-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (RawMaterials::withTrashed()->where('code', $code)->exists());

        return $code;
    }
}
