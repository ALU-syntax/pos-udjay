<?php

namespace App\Http\Controllers;

use App\DataTables\CommunityDataTable;
use App\Http\Requests\CommunityStore;
use App\Models\Community;
use Illuminate\Http\Request;

class CommunityController extends Controller
{
    public function index(CommunityDataTable $datatable){
        return $datatable->render('layouts.community.index');
    }

    public function create(){
        return view('layouts.community.community-modal', [
            'data' => new Community(),
            'action' => route('membership/community/store')
        ]);
    }

    public function store(CommunityStore $request){
        $category = new Community($request->validated());
        $category->save();

        return responseSuccess(false);   
    }

    public function edit(Community $community){
        return view('layouts.community.community-modal', [
            'data' => $community,
            'action' => route('membership/community/update', $community->id)
        ]);
    }

    public function destroy(Community $community){
        $community->delete();

        return responseSuccessDelete();
    }

    public function update(Community $community, CommunityStore $request){
        $community->fill($request->validated());
        $community->save();

        return responseSuccess(true);
    }

}
