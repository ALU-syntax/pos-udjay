<?php

namespace App\Http\Controllers;

use App\DataTables\PemasukanDataTable;
use App\Http\Requests\PemasukanRequest;
use App\Models\Customer;
use App\Models\KategoriPemasukan;
use App\Models\Outlets;
use App\Models\Pemasukan;
use Illuminate\Support\Facades\File;

class PemasukanController extends Controller
{
    public function index(PemasukanDataTable $datatable){
        return $datatable->render('layouts.pemasukan.index');
    }

    public function create(){
        return view('layouts.pemasukan.pemasukan-modal', [
            'data' => new Pemasukan(),
            'action' => route('accounting/pemasukan/store'),
            'kategoriPemasukans' => KategoriPemasukan::all(),
            'customers' => Customer::all(),
            "outlets" => Outlets::whereIn('id', json_decode(auth()->user()->outlet_id))->get(),
        ]);
    }

    public function store(PemasukanRequest $request){
        // dd($request);
        $validatedData = $request->validated();

        $data = [
            'outlet_id' => $validatedData['outlet_id'],
            'kategori_pemasukan_id' => $validatedData['kategori_pemasukan_id'],
            'customer_id' => isset($validatedData['customer_id']) ? $validatedData['customer_id'] : null,
            'jumlah' => getAmount($validatedData['jumlah']),
            'tanggal' => $validatedData['tanggal'],
            'catatan' => $validatedData['catatan'],
            'user_id' => auth()->user()->id,
        ];

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('pemasukan', 'public');
        }

        Pemasukan::create($data);

        return responseSuccess();
    }

    public function edit(Pemasukan $pemasukan){
        return view('layouts.pemasukan.pemasukan-modal', [
            'data' => $pemasukan,
            'action' => route('accounting/pemasukan/update', $pemasukan->id),
            'kategoriPemasukans' => KategoriPemasukan::all(),
            'customers' => Customer::all(),
            "outlets" => Outlets::whereIn('id', json_decode(auth()->user()->outlet_id))->get(),
        ]);
    }

    public function destroy(Pemasukan $pemasukan){
        $pemasukan->delete();

        return responseSuccessDelete();
    }

    public function update(PemasukanRequest $request, Pemasukan $pemasukan){
        $pemasukan->fill($request->validated());

        $pemasukan->jumlah = getAmount($request->jumlah);
        if (!empty($request->file('photo'))) {
            $filePath = public_path('uploads/pemasukan/' . $pemasukan->photo);
            if (File::exists($filePath)) {
                File::delete($filePath);
            }

            $pemasukan->photo = $request->file('photo')->store('pemasukan', 'public');
        }

        $pemasukan->save();

        return responseSuccess(true);
    }
}
