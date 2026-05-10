<?php

namespace App\Http\Controllers;

use App\DataTables\RawMaterialCategoriesDataTable;
use App\Http\Requests\RawMaterialCategoryRequest;
use App\Models\RawMaterialCategories;
use App\Models\RawMaterials;

class KategoriBahanBakuController extends Controller
{
    public function index(RawMaterialCategoriesDataTable $datatable)
    {
        return $datatable->render('layouts.kategori_bahan_baku.index', [
            'stats' => $this->stats(),
            'latestCategory' => RawMaterialCategories::latest('updated_at')->first(),
        ]);
    }

    public function create()
    {
        return view('layouts.kategori_bahan_baku.modal', [
            'data' => new RawMaterialCategories(),
            'action' => route('library/category-bahan-baku/store'),
        ]);
    }

    public function store(RawMaterialCategoryRequest $request)
    {
        RawMaterialCategories::create($request->validated());

        return responseSuccess(false, 'Kategori bahan baku berhasil ditambahkan', $this->stats());
    }

    public function edit(RawMaterialCategories $categoryBahanBaku)
    {
        return view('layouts.kategori_bahan_baku.modal', [
            'data' => $categoryBahanBaku,
            'action' => route('library/category-bahan-baku/update', $categoryBahanBaku->id),
        ]);
    }

    public function detail(RawMaterialCategories $categoryBahanBaku)
    {
        $categoryBahanBaku->load(['rawMaterials.baseUnit']);

        return view('layouts.kategori_bahan_baku.detail-modal', [
            'category' => $categoryBahanBaku,
            'materials' => $categoryBahanBaku->rawMaterials->sortBy('name')->values(),
        ]);
    }

    public function update(RawMaterialCategoryRequest $request, RawMaterialCategories $categoryBahanBaku)
    {
        $categoryBahanBaku->update($request->validated());

        return responseSuccess(true, 'Kategori bahan baku berhasil diperbarui', $this->stats());
    }

    private function stats(): array
    {
        $total = RawMaterialCategories::count();
        $used = RawMaterialCategories::has('rawMaterials')->count();

        return [
            'total' => $total,
            'active' => RawMaterialCategories::where('is_active', 1)->count(),
            'inactive' => RawMaterialCategories::where('is_active', 0)->count(),
            'used' => $used,
            'empty' => max($total - $used, 0),
            'raw_material_total' => RawMaterials::count(),
        ];
    }
}
