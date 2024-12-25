<?php

namespace App\Http\Controllers;

use App\DataTables\ModifiersDatatables;
use App\DataTables\PilihProductDataTable;
use App\Models\ModifierGroup;
use App\Models\Modifiers;
use App\Models\Outlets;
use Illuminate\Http\Request;

class ModifiersController extends Controller
{
    public function index(ModifiersDatatables $datatables)
    {
        return $datatables->render('layouts.modifiers.index', [
            "outlets" => Outlets::whereIn('id', json_decode(auth()->user()->outlet_id))->get(),
        ]);
    }

    public function create()
    {
        return view('layouts.modifiers.modifiers-modal', [
            "data" => new ModifierGroup(),
            "action" => route("library/modifiers/store"),
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
            'required' => 'nullable',
            'max' => 'nullable|integer',
            'min' => 'nullable|integer',
            'outlet_id' => 'required|array'
        ]);

        $required = $validatedData['required'] === "true";

        foreach ($validatedData['outlet_id'] as $outlet) {
            $dataModifierGroup = [
                "name" => $validatedData['name'],
                'required' => $required,
                'max_selected' => $validatedData['max'],
                'min_selected' => $required ? $validatedData['min'] : null,
                'outlet_id' => $outlet
            ];

            // Simpan ModifierGroup
            $modifierGroup = ModifierGroup::create($dataModifierGroup); // `create()` lebih ringkas daripada `new` + `save()`

            // Buat data Modifiers
            $dataModifier = [];
            for ($x = 0; $x < count($validatedData['option_name']); $x++) {
                $dataModifier[] = [
                    'name' => $validatedData['option_name'][$x],
                    'harga' => getAmount($validatedData['price'][$x]),
                    'stok' => $validatedData['stok'][$x],
                    'modifiers_group_id' => $modifierGroup->id, // Gunakan ID langsung dari instance ModifierGroup
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }

            // Simpan semua Modifiers secara bulk
            Modifiers::insert($dataModifier); // Bulk insert lebih efisien
        }

        return responseSuccess(false);
    }

    public function edit(ModifierGroup $modifier)
    {
        $modifierGroup = $modifier->where('id', $modifier->id)->with(['modifier', 'outlet'])->get()[0];
        $outlet = Outlets::find($modifierGroup->outlet_id);
        $dataOutlet = ['id' => $outlet->id, 'name' => $outlet->name];
        return view('layouts.modifiers.modifiers-modal', [
            'data' => $modifierGroup,
            'action' => route('library/modifiers/update', $modifierGroup->id),
            'outlets' => json_encode([$dataOutlet])
        ]);
    }


    public function update(Request $request, ModifierGroup $modifier)
    {
        $validateData = $request->validate([
            'name' => 'required',
            'option_name' => 'required|array',
            'id_modifier' => 'required',
            'price' => 'required|array',
            'stok' => 'nullable|array',
            'required' => 'nullable',
            'max' => 'nullable|integer',
            'min' => 'nullable|integer',
            'outlet_id' => 'required'
        ]);

        $required = $validateData['required'] === "true";

        $dataModifierGroupUpdate = [
            'name' => $validateData['name'],
            'required' => $required,
            'max_selected' => $validateData['max'],
            'min_selected' => $validateData['min'],
        ];

        $modifier->update($dataModifierGroupUpdate);

        $listNameModifier = $validateData['option_name'];
        $listPriceModifier = $validateData['price'];
        $listStokModifier = $validateData['stok'];
        $listIdModifier = $validateData['id_modifier'];

        $listModifierExist = $modifier->with('modifier')->get();

        $idModifierExist = array_column($listModifierExist[0]->modifier->toArray(), 'id');

        $modifierToDelete = array_diff($idModifierExist, $listIdModifier);

        foreach ($modifierToDelete as $deleteItem) {
            Modifiers::find($deleteItem)->delete();
        }

        foreach ($listNameModifier as $key => $value) {
            if (isset($listIdModifier[$key])) {
                $modifierItem = Modifiers::find($listIdModifier[$key]);
                $dataModifier = [
                    'name' => $value,
                    'harga' => getAmount($listPriceModifier[$key]),
                    'stok' => $listStokModifier[$key],
                ];

                $modifierItem->update($dataModifier);
            } else {
                $dataModifier = [
                    'name' => $value,
                    'harga' => getAmount($listPriceModifier[$key]),
                    'stok' => $listStokModifier[$key],
                    'modifiers_group_id' => $modifier->id, // Gunakan ID langsung dari instance ModifierGroup
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                Modifiers::create($dataModifier);
            }
        }

        return responseSuccess(true);
    }

    public function getProduct(PilihProductDataTable $datatable, ModifierGroup $modifierGroup)
    {
        return $datatable->with('modifierGroup', $modifierGroup)->render('layouts.modifiers.modal-tambah-product', [
            "data" => $modifierGroup,
            "action" => route("library/modifiers/update-product", $modifierGroup->id),
        ]);
    }

    public function updateProductModifier(Request $request, ModifierGroup $modifier)
    {
        $data = [
            'product_id' => json_encode($request->products)
        ];

        $modifier->update($data);

        return responseSuccess(true, "Modifiers berhasil ditambahkan kedalam product");
    }

    public function destroy(ModifierGroup $modifier)
    {
        $modifier->modifier()->delete();
        $modifier->delete();
        // dd($modifier);
        return responseSuccessDelete();
    }
}
