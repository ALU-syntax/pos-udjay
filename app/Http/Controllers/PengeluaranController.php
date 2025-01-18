<?php

namespace App\Http\Controllers;

use App\DataTables\PengeluaranDataTable;
use App\Http\Requests\PengeluaranRequest;
use App\Models\Customer;
use App\Models\KategoriPengeluaran;
use App\Models\Outlets;
use App\Models\Pengeluaran;
use Illuminate\Support\Facades\File;

class PengeluaranController extends Controller
{
    public function index(PengeluaranDataTable $datatable){
        
        return $datatable->render('layouts.pengeluaran.index');
    }

    public function create(){
        return view('layouts.pengeluaran.pengeluaran-modal', [
            'data' => new Pengeluaran(),
            'action' => route('accounting/pengeluaran/store'),
            'kategoriPengeluarans' => KategoriPengeluaran::all(),
            'customers' => Customer::all(),
            "outlets" => Outlets::whereIn('id', json_decode(auth()->user()->outlet_id))->get(),
        ]);
    }

    public function store(PengeluaranRequest $request){
        // dd($request);
        $validatedData = $request->validated();

        $data = [
            'outlet_id' => $validatedData['outlet_id'],
            'kategori_pengeluaran_id' => $validatedData['kategori_pengeluaran_id'],
            'customer_id' => isset($validatedData['customer_id']) ? $validatedData['customer_id'] : null,
            'jumlah' => getAmount($validatedData['jumlah']),
            'tanggal' => $validatedData['tanggal'],
            'catatan' => $validatedData['catatan'],
            'user_id' => auth()->user()->id,
        ];

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('pengeluaran', 'public');
        }

        Pengeluaran::create($data);

        return responseSuccess();
    }

    public function edit(Pengeluaran $pengeluaran){
        return view('layouts.pengeluaran.pengeluaran-modal', [
            'data' => $pengeluaran,
            'action' => route('accounting/pengeluaran/update', $pengeluaran->id),
            'kategoriPengeluarans' => KategoriPengeluaran::all(),
            'customers' => Customer::all(),
            "outlets" => Outlets::whereIn('id', json_decode(auth()->user()->outlet_id))->get(),
        ]);
    }

    public function destroy(Pengeluaran $pengeluaran){
        $pengeluaran->delete();

        return responseSuccessDelete();
    }

    public function update(PengeluaranRequest $request, Pengeluaran $pengeluaran){
        $pengeluaran->fill($request->validated());

        $pengeluaran->jumlah = getAmount($request->jumlah);
        if (!empty($request->file('photo'))) {
            $filePath = public_path('uploads/pengeluaran/' . $pengeluaran->photo);
            if (File::exists($filePath)) {
                File::delete($filePath);
            }

            $pengeluaran->photo = $request->file('photo')->store('pengeluaran', 'public');
        }

        $pengeluaran->save();

        return responseSuccess(true);
    }

}
