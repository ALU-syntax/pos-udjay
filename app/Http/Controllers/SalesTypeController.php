<?php

namespace App\Http\Controllers;

use App\DataTables\SalesTypeDataTable;
use App\Http\Requests\SalesTypeRequest;
use App\Models\Outlets;
use App\Models\SalesType;
use Illuminate\Http\Request;

class SalesTypeController extends Controller
{
    public function index(SalesTypeDataTable $datatable)
    {
        return $datatable->render('layouts.salestype.index', [
            "outlets" => Outlets::whereIn('id', json_decode(auth()->user()->outlet_id))->get(),
        ]);
    }

    public function create()
    {
        return view('layouts.salestype.salestype-modal', [
            'action' => route('library/salestype/store'),
            'data' => new SalesType(),
            "outlets" => Outlets::whereIn('id', json_decode(auth()->user()->outlet_id))->get(),
        ]);
    }

    public function store(SalesTypeRequest $request)
    {
        $data = $request->validated();

        foreach ($data['outlet_id'] as $outlet) {
            $dataSalesType = [
                'name' => $data['name'],
                'outlet_id' => $outlet,
                'status' => $data['status']
            ];

            SalesType::create($dataSalesType); // `create()` lebih ringkas daripada `new` + `save()`
        }

        return responseSuccess(false);
    }

    public function edit(SalesType $salesType)
    {
        return view('layouts.salestype.salestype-modal', [
            'action' => route('library/salestype/update', $salesType->id),
            'data' => $salesType
        ]);
    }

    public function update(Request $request, SalesType $salesType)
    {
        $salesType->fill($request->validated());
        $salesType->save();

        return responseSuccess(true);
    }

    public function destroy(SalesType $salesType)
    {
        $salesType->delete();

        return responseSuccessDelete();
    }

    public function updateStatus(Request $request, $id)
    {
        $salesType = SalesType::findOrFail($id);
        $salesType->status = $request->status;
        $salesType->save();

        return response()->json(['success' => true, 'data' => $salesType]);
    }

    public function getSalesTypeByOutlet(Request $request){
        $idOutlet = $request->input('idOutlet');
        if(count($idOutlet) > 1){
            $salesType = SalesType::whereIn('outlet_id', $idOutlet)
                ->where('status', true)
                ->select('name', \DB::raw('COUNT(name) as name_count'))
                ->groupBy('name')
                ->having('name_count', '>', 1)
                ->get();
        }else{
            $salesType = SalesType::where('outlet_id', $idOutlet[0])->where('status', true)->get();
        }

        return response()->json($salesType);
    }

    public function apiGetSalesTypeByOutlet($idOutlet)
    {
        $convertIdOutlet = json_decode($idOutlet);
        $salesType = SalesType::where('outlet_id', $convertIdOutlet[0])->where('status', true)->get();
        return response()->json($salesType);
    }
}
