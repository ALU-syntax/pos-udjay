<?php

namespace App\Http\Controllers;

use App\DataTables\PiutangDataTable;
use App\Models\Piutang;
use Illuminate\Http\Request;

class PiutangController extends Controller
{
    public function index(PiutangDataTable $datatable){
        return $datatable->render('layouts.piutang.index');
    }

    public function create(){
        return view('layouts.piutang.piutang-modal', [
            'data' => new Piutang(),
            'action' => route('accounting/piutang/store'),
        ]);
    }

    public function store(){
        
    }
}
