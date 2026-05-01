<?php

namespace App\Http\Controllers;

use App\DataTables\SatuanDataTable;
use App\Http\Requests\SatuanStoreRequest;
use App\Models\Satuan;

class SatuanController extends Controller
{
    public function index(SatuanDataTable $datatable)
    {
        $stats = [
            'total' => Satuan::count(),
            'active' => Satuan::where('is_active', 1)->count(),
            'inactive' => Satuan::where('is_active', 0)->count(),
        ];

        return $datatable->render('layouts.satuan.index', compact('stats'));
    }

    public function create()
    {
        return view('layouts.satuan.satuan-modal', [
            'data' => new Satuan(),
            'action' => route('warehouse/satuan/store')
        ]);
    }

    public function store(SatuanStoreRequest $request)
    {
        $satuan = new Satuan($request->validated());
        $satuan->save();

        $stats = [
            'total' => Satuan::count(),
            'active' => Satuan::where('is_active', 1)->count(),
            'inactive' => Satuan::where('is_active', 0)->count(),
        ];

        return responseSuccess(false, false, $stats);
    }

    public function edit(Satuan $satuan)
    {
        return view('layouts.satuan.satuan-modal', [
            'data' => $satuan,
            'action' => route('warehouse/satuan/update', $satuan->id)
        ]);
    }

    public function update(SatuanStoreRequest $request, Satuan $satuan)
    {
        $satuan->fill($request->validated());
        $satuan->save();

        $stats = [
            'total' => Satuan::count(),
            'active' => Satuan::where('is_active', 1)->count(),
            'inactive' => Satuan::where('is_active', 0)->count(),
        ];

        return responseSuccess(true, false, $stats);
    }
}
