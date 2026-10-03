<?php

namespace App\Http\Controllers\OrderTable;

use App\DataTables\OrderTable\PaymentMonitorDataTable;
use App\Http\Controllers\Controller;
use App\Models\Outlets;

class PaymentMonitorController extends Controller
{
    public function __construct()
    {
        $this->abilities = [];
    }

    public function index(PaymentMonitorDataTable $dataTable)
    {
        $this->authorize('read order-table/payments');

        return $dataTable->render('layouts.order-table.payments.index', [
            'outlets' => Outlets::query()
                ->whereIn('id', request()->user()->outletIds())
                ->orderBy('name')
                ->get(),
        ]);
    }
}
