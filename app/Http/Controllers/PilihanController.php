<?php

namespace App\Http\Controllers;

use App\DataTables\PilihanDataTable;
use App\DataTables\PilihanProductDataTable;
use App\Models\Outlets;
use App\Models\Pilihan;
use App\Models\PilihanGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PilihanController extends Controller
{
    public function index(PilihanDataTable $datatable)
    {
        return $datatable->render('layouts.pilihan.index', [
            "outlets" => Outlets::whereIn('id', json_decode(auth()->user()->outlet_id))->get(),
        ]);
    }

    public function create()
    {
        return view('layouts.pilihan.pilihan-modal', [
            "data" => new PilihanGroup(),
            "action" => route("library/pilihan/store"),
            "outlets" => Outlets::whereIn('id', json_decode(auth()->user()->outlet_id))->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required',
            'option_name' => 'required|array',
            'price' => 'required|array',
            'stok' => 'nullable|array',
            'outlet_id' => 'required|array'
        ]);

        DB::transaction(function () use ($validatedData) {
            foreach ($validatedData['outlet_id'] as $outlet) {
                $dataPilihanGroup = [
                    "name" => $validatedData['name'],
                    'outlet_id' => $outlet
                ];

                // Simpan PilihanGroup
                $pilihanGroup = PilihanGroup::create($dataPilihanGroup); // `create()` lebih ringkas daripada `new` + `save()`

                // Buat data Pilihans
                $dataPilihan = [];
                for ($x = 0; $x < count($validatedData['option_name']); $x++) {
                    $dataPilihan[] = [
                        'name' => $validatedData['option_name'][$x],
                        'harga' => getAmount($validatedData['price'][$x]),
                        'stok' => $validatedData['stok'][$x],
                        'pilihan_group_id' => $pilihanGroup->id, // Gunakan ID langsung dari instance PilihanGroup
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }

                // Simpan semua Piilhans secara bulk
                Pilihan::insert($dataPilihan); // Bulk insert lebih efisien
            }
        });

        return responseSuccess(false);
    }

    public function edit(PilihanGroup $pilihan)
    {
        $pilihanGroup = $pilihan->where('id', $pilihan->id)->with(['pilihans', 'outlet'])->get()[0];
        $outlet = Outlets::find($pilihanGroup->outlet_id);
        $dataOutlet = ['id' => $outlet->id, 'name' => $outlet->name];
        return view('layouts.pilihan.pilihan-modal', [
            'data' => $pilihanGroup,
            'action' => route('library/pilihan/update', $pilihanGroup->id),
            'outlets' => json_encode(value: [$dataOutlet])
        ]);
    }

    public function getProduct(PilihanProductDataTable $datatable, PilihanGroup $pilihanGroup)
    {
        return $datatable->with('pilihanGroup', $pilihanGroup)->render('layouts.pilihan.modal-tambah-product', [
            "data" => $pilihanGroup,
            'action' => route("library/pilihan/update-product", $pilihanGroup)
        ]);
    }

    public function destroy(PilihanGroup $pilihan)
    {
        $pilihan->pilihans()->delete();
        $pilihan->delete();

        return responseSuccessDelete();
    }

    public function update(Request $request, PilihanGroup $pilihan)
    {
        $pilihan->load('pilihans');

        $validateData = $request->validate([
            'name' => 'required',
            'option_name' => 'required|array',
            'id_pilihan' => 'required',
            'price' => 'required|array',
            'stok' => 'nullable|array',
            'outlet_id' => 'required'
        ]);


        $dataPilihanGroupUpdate = [
            'name' => $validateData['name'],
        ];

        $pilihan->update($dataPilihanGroupUpdate);

        $listNamePilihan = $validateData['option_name'];
        $listPricePilihan = $validateData['price'];
        $listStokPilihan = $validateData['stok'];
        $listIdPilihan = $validateData['id_pilihan'];

        // $listModifierExist = $modifier->with('modifier')->get();

        $idPilihanExist = array_column($pilihan->pilihans->toArray(), 'id');

        $pilihanToDelete = array_diff($idPilihanExist, $listIdPilihan);

        foreach ($pilihanToDelete as $deleteItem) {
            Pilihan::find($deleteItem)->delete();
        }

        foreach ($listNamePilihan as $key => $value) {
            if (isset($listIdPilihan[$key])) {
                $pilihanItem = Pilihan::find($listIdPilihan[$key]);
                $dataPilihan = [
                    'name' => $value,
                    'harga' => getAmount($listPricePilihan[$key]),
                    'stok' => $listStokPilihan[$key],
                ];

                $pilihanItem->update($dataPilihan);
            } else {
                $dataPilihan = [
                    'name' => $value,
                    'harga' => getAmount($listPricePilihan[$key]),
                    'stok' => $listStokPilihan[$key],
                    'pilihan_group_id' => $pilihan->id, // Gunakan ID langsung dari instance ModifierGroup
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                Pilihan::create($dataPilihan);
            }
        }

        return responseSuccess(true);
    }

    public function updateProductPilihan(Request $request, PilihanGroup $pilihan){
        $data = [
            'product_id' => json_encode($request->products)
        ];

        $pilihan->update($data);

        return responseSuccess(true, "Pilihan berhasil ditambahkan kedalam product");
    }

    public function getPilihansByOutlet($idOutlet)
    {
        $convertIdOutlet = json_decode($idOutlet);
        $pilihans = PilihanGroup::where('outlet_id', $convertIdOutlet[0])->with('pilihans')->orderBy('name', 'asc')->get();
        return response()->json($pilihans);
    }
}
