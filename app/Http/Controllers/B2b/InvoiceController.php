<?php

namespace App\Http\Controllers\B2b;

use App\DataTables\B2b\InvoiceDataTable;
use App\Http\Controllers\Controller;
use App\Models\B2b\Invoice;

class InvoiceController extends Controller
{
    public function __construct()
    {
        $this->abilities = [];
    }

    public function index(InvoiceDataTable $dataTable)
    {
        $this->authorize('read b2b/invoices');

        return $dataTable->render('layouts.b2b.invoices.index');
    }

    /** Halaman cetak invoice. "Simpan sebagai PDF" lewat dialog cetak browser. */
    public function print(int $invoice)
    {
        abort_unless(request()->user()->can('read b2b/invoices') || request()->user()->can('read b2b/orders'), 403);

        $invoice = Invoice::query()
            ->whereKey($invoice)
            ->whereHas('order', fn ($order) => $order->withTrashed()->whereIn('outlet_id', request()->user()->outletIds()))
            ->with(['order.items', 'order.outlet', 'order.contract', 'customer', 'schedules', 'allocations.payment'])
            ->firstOrFail();

        return view('layouts.b2b.invoices.print', ['invoice' => $invoice]);
    }
}
