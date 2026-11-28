<?php

namespace App\Http\Controllers;

use App\DataTables\RawMaterialUnitConversionDataTable;
use App\DataTables\SatuanDataTable;
use App\Http\Requests\SatuanStoreRequest;
use App\Models\RawMaterials;
use App\Models\RawMaterialUnitConversions;
use App\Models\Satuan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SatuanController extends Controller
{
    /**
     * Method di luar nama standar (index, show, create, store, edit, update, destroy)
     * ikut dicek HasPermission: izin "<aksi> <url menu>".
     */
    public function __construct()
    {
        $this->abilities += [
            'conversions' => 'read',
            'createConversion' => 'create',
            'storeConversion' => 'create',
            'editConversion' => 'update',
            'updateConversion' => 'update',
            'destroyConversion' => 'delete',
        ];
    }

    public function index(SatuanDataTable $datatable, RawMaterialUnitConversionDataTable $conversionDataTable)
    {
        $stats = $this->stats();
        $rawMaterials = RawMaterials::orderBy('name')->get(['id', 'name']);
        $unitCount = Satuan::count();
        $conversionTable = $conversionDataTable->html();

        return $datatable->render('layouts.satuan.index', compact('stats', 'rawMaterials', 'unitCount', 'conversionTable'));
    }

    /** Data JSON tabel "Konversi per Bahan Baku" */
    public function conversions(RawMaterialUnitConversionDataTable $datatable)
    {
        return $datatable->ajax();
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
            'units' => $this->conversionUnitOptions(),
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
            'units' => $this->conversionUnitOptions($conversion),
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

        return responseSuccess(false, 'Konversi bahan baku berhasil dihapus', $this->stats(true));
    }

    /** Satuan aktif saja; satuan yang sudah tersimpan di konversi tetap ikut agar edit tidak memaksa ganti. */
    private function conversionUnitOptions(?RawMaterialUnitConversions $conversion = null)
    {
        $currentIds = $this->currentUnitIds($conversion);

        return Satuan::where(fn ($query) => $query->where('is_active', 1)
                ->when($currentIds, fn ($query) => $query->orWhereIn('id', $currentIds)))
            ->orderBy('name')
            ->get();
    }

    private function currentUnitIds(?RawMaterialUnitConversions $conversion): array
    {
        return array_values(array_filter([$conversion?->from_unit_id, $conversion?->to_unit_id]));
    }

    private function validateConversion(Request $request, ?RawMaterialUnitConversions $conversion = null): array
    {
        $currentUnitIds = $this->currentUnitIds($conversion);
        $activeOrCurrentUnit = Rule::exists('satuans', 'id')->where(fn ($query) => $query->where('is_active', 1)
            ->when($currentUnitIds, fn ($query) => $query->orWhereIn('id', $currentUnitIds)));

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
            'from_unit_id' => ['required', $activeOrCurrentUnit, 'different:to_unit_id'],
            'to_unit_id' => ['required', $activeOrCurrentUnit, 'different:from_unit_id'],
            'multiplier' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string'],
        ], [
            'raw_material_id.unique' => 'Konversi untuk bahan baku dan pasangan satuan ini sudah ada.',
            'from_unit_id.different' => 'Satuan asal dan satuan tujuan harus berbeda.',
            'to_unit_id.different' => 'Satuan tujuan dan satuan asal harus berbeda.',
            'from_unit_id.exists' => 'Satuan asal tidak ditemukan atau sudah nonaktif.',
            'to_unit_id.exists' => 'Satuan tujuan tidak ditemukan atau sudah nonaktif.',
        ]);
    }

    private function stats(bool $refreshConversions = false): array
    {
        return [
            'total' => Satuan::count(),
            'active' => Satuan::where('is_active', 1)->count(),
            'inactive' => Satuan::where('is_active', 0)->count(),
            'conversion_total' => RawMaterialUnitConversions::count(),
            'refresh_conversions' => $refreshConversions,
        ];
    }
}
