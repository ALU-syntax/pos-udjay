<?php

namespace App\Http\Controllers;

use App\DataTables\SatuanDataTable;
use App\Http\Requests\SatuanStoreRequest;
use App\Models\RawMaterials;
use App\Models\RawMaterialUnitConversions;
use App\Models\Satuan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SatuanController extends Controller
{
    public function index(SatuanDataTable $datatable)
    {
        $stats = $this->stats();
        $conversions = RawMaterialUnitConversions::with(['rawMaterial.baseUnit', 'fromUnit', 'toUnit'])
            ->latest()
            ->get();
        $rawMaterials = RawMaterials::with('baseUnit')->orderBy('name')->get();
        $units = Satuan::orderBy('name')->get();

        return $datatable->render('layouts.satuan.index', compact('stats', 'conversions', 'rawMaterials', 'units'));
    }

    public function create()
    {
        return view('layouts.satuan.satuan-modal', [
            'data' => new Satuan(),
            'action' => route('library/satuan/store')
        ]);
    }

    public function store(SatuanStoreRequest $request)
    {
        $satuan = new Satuan($request->validated());
        $satuan->save();

        return responseSuccess(false, false, $this->stats());
    }

    public function edit(Satuan $satuan)
    {
        return view('layouts.satuan.satuan-modal', [
            'data' => $satuan,
            'action' => route('library/satuan/update', $satuan->id)
        ]);
    }

    public function update(SatuanStoreRequest $request, Satuan $satuan)
    {
        $satuan->fill($request->validated());
        $satuan->save();

        return responseSuccess(true, false, $this->stats());
    }

    public function createConversion()
    {
        return view('layouts.satuan.conversion-modal', [
            'conversion' => new RawMaterialUnitConversions(),
            'rawMaterials' => RawMaterials::with('baseUnit')->orderBy('name')->get(),
            'units' => Satuan::orderBy('name')->get(),
            'action' => route('library/satuan/conversions/store'),
        ]);
    }

    public function storeConversion(Request $request)
    {
        $validated = $this->validateConversion($request);

        RawMaterialUnitConversions::create($validated);

        return responseSuccess(false, 'Konversi bahan baku berhasil ditambahkan', $this->stats(true));
    }

    public function editConversion(RawMaterialUnitConversions $conversion)
    {
        return view('layouts.satuan.conversion-modal', [
            'conversion' => $conversion,
            'rawMaterials' => RawMaterials::with('baseUnit')->orderBy('name')->get(),
            'units' => Satuan::orderBy('name')->get(),
            'action' => route('library/satuan/conversions/update', $conversion->id),
            'update' => true,
        ]);
    }

    public function updateConversion(Request $request, RawMaterialUnitConversions $conversion)
    {
        $validated = $this->validateConversion($request, $conversion);

        $conversion->update($validated);

        return responseSuccess(true, 'Konversi bahan baku berhasil diperbarui', $this->stats(true));
    }

    public function destroyConversion(RawMaterialUnitConversions $conversion)
    {
        $conversion->delete();

        return responseSuccessDelete();
    }

    private function validateConversion(Request $request, ?RawMaterialUnitConversions $conversion = null): array
    {
        return $request->validate([
            'raw_material_id' => [
                'required',
                'exists:raw_materials,id',
                Rule::unique('raw_material_unit_conversions', 'raw_material_id')
                    ->ignore($conversion?->id)
                    ->where(fn ($query) => $query
                        ->where('from_unit_id', $request->input('from_unit_id'))
                        ->where('to_unit_id', $request->input('to_unit_id'))),
            ],
            'from_unit_id' => ['required', 'exists:satuans,id', 'different:to_unit_id'],
            'to_unit_id' => ['required', 'exists:satuans,id', 'different:from_unit_id'],
            'multiplier' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string'],
        ], [
            'raw_material_id.unique' => 'Konversi untuk bahan baku dan pasangan satuan ini sudah ada.',
            'from_unit_id.different' => 'Satuan asal dan satuan tujuan harus berbeda.',
            'to_unit_id.different' => 'Satuan tujuan dan satuan asal harus berbeda.',
        ]);
    }

    private function stats(bool $refreshConversions = false): array
    {
        return [
            'total' => Satuan::count(),
            'active' => Satuan::where('is_active', 1)->count(),
            'inactive' => Satuan::where('is_active', 0)->count(),
            'symbolized' => Satuan::whereNotNull('symbol')->where('symbol', '!=', '')->count(),
            'conversion_total' => RawMaterialUnitConversions::count(),
            'converted_materials' => RawMaterialUnitConversions::distinct('raw_material_id')->count('raw_material_id'),
            'raw_material_total' => RawMaterials::count(),
            'refresh_conversions' => $refreshConversions,
        ];
    }
}
