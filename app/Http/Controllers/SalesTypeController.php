<?php

namespace App\Http\Controllers;

use App\DataTables\SalesTypeDataTable;
use App\Http\Requests\SalesTypeRequest;
use App\Models\SalesType;
use Illuminate\Http\Request;

class SalesTypeController extends Controller
{
    public function index(SalesTypeDataTable $datatable){
        return $datatable->render('layouts.salestype.index');
    }

    public function create(){
        return view('layouts.salestype.salestype-modal', [
            'action' => route('library/salestype/store'),
            'data' => new SalesType()
        ]);
    }

    public function store(SalesTypeRequest $request){
        $salesType = new SalesType($request->validated());
        
        $salesType->save();

        return responseSuccess(false);
    }

    public function edit(SalesType $salesType){
        return view('layouts.salestype.salestype-modal', [
            'action' => route('library/salestype/update', $salesType->id),
            'data' => $salesType
        ]);
    }

    public function update(SalesTypeRequest $request, SalesType $salesType){
        $salesType->fill($request->validated());
        $salesType->save();

        return responseSuccess(true);
    }

    public function destroy(SalesType $salesType){
        $salesType->delete();

        return responseSuccessDelete();
    }

}
