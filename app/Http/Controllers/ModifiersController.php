<?php

namespace App\Http\Controllers;

use App\DataTables\ModifiersDatatables;
use Illuminate\Support\Facades\DB;
use App\DataTables\PilihProductDataTable;
use App\Models\ModifierGroup;
use App\Models\Modifiers;
use App\Models\Outlets;
use App\Models\Product;
use Illuminate\Http\Request;

class ModifiersController extends Controller
{
    public function index(ModifiersDatatables $datatables)
    {
        return $datatables->render('layouts.modifiers.index', [
            "outlets" => Outlets::whereIn('id', json_decode(auth()->user()->outlet_id))->get(),
        ]);
    }

    // public function index(Request $request)
    // {
    //     $query = ModifierGroup::with(['modifier', 'outlet']);

    //     if ($request->has('outlet') && $request->get('outlet') != '') {
    //         if ($request->get('outlet') == 'all') {
    //             $query->whereIn('outlet_id', json_decode(auth()->user()->outlet_id));
    //         } else {
    //             $query->where('outlet_id', $request->get('outlet'));
    //         }
    //     } elseif ($request->has('outlet') && $request->get('outlet') == '') {
    //         $query->whereIn('outlet_id', json_decode(auth()->user()->outlet_id));
    //     }

    //     if ($request->ajax()) {
    //         return DataTables::of($query)
    //             ->addColumn('option_name', function ($row) {
    //                 $modifiers = $row->modifier;
    //                 $tag = '';
    //                 foreach ($modifiers as $modifier) {
    //                     $tag .= "<span>{$modifier->name}, </span>";
    //                 }
    //                 $result = substr($tag, 0, -9);
    //                 return $result;
    //             })
    //             ->addColumn('outlet', function ($row) {
    //                 return "<span class='badge badge-primary'>{$row->outlet->name}</span>";
    //             })
    //             ->addColumn('action', function ($row) {
    //                 return view('layouts.modifiers.action', [
    //                     'edit' => route('library/modifiers/edit', $row->id),
    //                     'aturProduk' => route('library/modifiers/getProduct', $row->id),
    //                     'routeDelete' => route('library/modifiers/destroy', $row->id)
    //                 ]);
    //             })
    //             ->rawColumns(['option_name', 'outlet', 'action'])
    //             ->setRowId('id')
    //             ->make(true);
    //     }

    //     return view('layouts.modifiers.index', [
    //         "outlets" => Outlets::whereIn('id', json_decode(auth()->user()->outlet_id))->get(),
    //     ]);
    // }

    // $(document).ready(function() {
    //     $('#modifiers-table').DataTable({
    //         processing: true,
    //         serverSide: true,
    //         ajax: '{{ route('modifiers.index') }}',
    //         columns: [
    //             { data: 'name', name: 'name' },
    //             { data: 'option_name', name: 'option_name' },
    //             { data: 'outlet', name: 'outlet' },
    //             { data: 'action', name: 'action', orderable: false, searchable: false }
    //         ],
    //         dom: 'Bfrtip',
    //         buttons: [
    //             'excel',
    //             'csv',
    //             'pdf',
    //             'print',
    //             'reset',
    //             'reload'
    //         ]
    //     });
    // });

    public function create()
    {
        return view('layouts.modifiers.modifiers-modal', [
            "data" => new ModifierGroup(),
            "action" => route("library/modifiers/store"),
            "outlets" => Outlets::whereIn('id', json_decode(auth()->user()->outlet_id))->get(),
            "update" => false
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required',
            'option_name' => 'required|array',
            'price' => 'required|array',
            'stok' => 'nullable|array',
            'outlet_id' => 'required|array',
            'is_required' => 'nullable',
            'show_in_order_table' => ['required', 'boolean'],
            'option_show_in_order_table' => 'nullable|array',
            'option_show_in_order_table.*' => 'boolean',
        ]);

        DB::transaction(function () use ($validatedData) {
            foreach ($validatedData['outlet_id'] as $outlet) {
                $dataModifierGroup = [
                    "name" => $validatedData['name'],
                    "outlet_id" => $outlet,
                    "is_required" => $validatedData['is_required'] ?? false,
                    "show_in_order_table" => $validatedData['show_in_order_table'],
                ];

                // Simpan ModifierGroup
                $modifierGroup = ModifierGroup::create($dataModifierGroup);

                // Buat data Modifiers
                $dataModifier = [];
                for ($x = 0; $x < count($validatedData['option_name']); $x++) {
                    $dataModifier[] = [
                        'name' => $validatedData['option_name'][$x],
                        'harga' => getAmount($validatedData['price'][$x]),
                        'stok' => $validatedData['stok'][$x],
                        // Form lama tanpa kolom ini: opsi dianggap tampil.
                        'show_in_order_table' => (bool) ($validatedData['option_show_in_order_table'][$x] ?? true),
                        'modifiers_group_id' => $modifierGroup->id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }

                // Simpan semua Modifiers secara bulk
                Modifiers::insert($dataModifier);
            }
        });

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
            'outlets' => json_encode([$dataOutlet]),
            'update' => true
        ]);
    }


    public function update(Request $request, ModifierGroup $modifier)
    {
        $modifier->load('modifier');

        $validateData = $request->validate([
            'name' => 'required',
            'option_name' => 'required|array',
            'id_modifier' => 'required',
            'price' => 'required|array',
            'stok' => 'nullable|array',
            'outlet_id' => 'required',
            'is_required' => 'nullable',
            'show_in_order_table' => ['required', 'boolean'],
            'option_show_in_order_table' => 'nullable|array',
            'option_show_in_order_table.*' => 'boolean',
        ]);

        $dataModifierGroupUpdate = [
            'name' => $validateData['name'],
            "is_required" => $validateData['is_required'] ?? false,
            "show_in_order_table" => $validateData['show_in_order_table'],
        ];

        $modifier->update($dataModifierGroupUpdate);

        $listNameModifier = $validateData['option_name'];
        $listPriceModifier = $validateData['price'];
        $listStokModifier = $validateData['stok'];
        $listIdModifier = $validateData['id_modifier'];
        // Sejajar dengan option_name[] karena tiap baris form selalu mengirim nilai 0/1.
        $listShowModifier = $validateData['option_show_in_order_table'] ?? [];

        // $listModifierExist = $modifier->with('modifier')->get();

        $idModifierExist = array_column($modifier->modifier->toArray(), 'id');

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
                    'show_in_order_table' => (bool) ($listShowModifier[$key] ?? true),
                ];

                $modifierItem->update($dataModifier);
            } else {
                $dataModifier = [
                    'name' => $value,
                    'harga' => getAmount($listPriceModifier[$key]),
                    'stok' => $listStokModifier[$key],
                    'show_in_order_table' => (bool) ($listShowModifier[$key] ?? true),
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
        $dataProduct = Product::select('id')->where('outlet_id', $modifierGroup->outlet_id)->get()->pluck('id');
        return $datatable->with('modifierGroup', $modifierGroup)->render('layouts.modifiers.modal-tambah-product', [
            "data" => $modifierGroup,
            "dataProduct" => $dataProduct,
            "action" => route("library/modifiers/update-product", $modifierGroup->id),
        ]);
    }

    public function updateProductModifier(Request $request, ModifierGroup $modifier)
    {
        // dd($request);
        $data = [
            'product_id' => json_encode($request->products)
        ];

        $modifier->update($data);

        return responseSuccess(true, "Modifiers berhasil ditambahkan kedalam product");
    }

    /**
     * Switch "Order Table" di tabel modifier: tampilkan/sembunyikan seluruh grup dari menu pelanggan.
     * Opsi per baris diatur lewat form edit.
     */
    public function toggleOrderTable(Request $request, ModifierGroup $modifier)
    {
        $this->authorize('update library/modifiers');
        abort_unless(in_array((int) $modifier->outlet_id, $request->user()->outletIds(), true), 403);

        $validated = $request->validate(['show' => ['required', 'boolean']]);
        $modifier->show_in_order_table = $validated['show'];
        $modifier->save();

        return responseSuccess(true, $modifier->show_in_order_table
            ? "{$modifier->name} ditampilkan di Order Table"
            : "{$modifier->name} disembunyikan dari Order Table", [
            'show_in_order_table' => $modifier->show_in_order_table,
        ]);
    }

    public function destroy(ModifierGroup $modifier)
    {
        $modifier->modifier()->delete();
        $modifier->delete();

        return responseSuccessDelete();
    }

    public function getModifierByOutlet($idOutlet)
    {
        $convertIdOutlet = json_decode($idOutlet);

        $modifiers = ModifierGroup::with(['modifier'])->where('outlet_id', $convertIdOutlet[0])->orderBy('name', 'asc')->get();
        return response()->json($modifiers);
    }
}
