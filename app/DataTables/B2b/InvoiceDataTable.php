<?php

namespace App\DataTables\B2b;

use App\Models\B2b\Invoice;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class InvoiceDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('invoice_number', fn (Invoice $invoice) => '<div class="ob-cell-stack">'
                .'<a href="'.route('b2b/invoices/print', $invoice->id).'" target="_blank" class="fw-bold">'.e($invoice->invoice_number).'</a>'
                .'<span class="ob-cell-meta">Pesanan <a href="'.route('b2b/orders/show', $invoice->b2b_order_id).'">'.e($invoice->order?->order_number).'</a></span></div>')
            ->addColumn('customer_name', fn (Invoice $invoice) => e($invoice->customer?->name))
            ->editColumn('issue_date', fn (Invoice $invoice) => $invoice->issue_date->translatedFormat('d M Y'))
            ->editColumn('due_date', function (Invoice $invoice) {
                $overdueDays = $invoice->displayStatus() === Invoice::STATUS_OVERDUE ? $invoice->due_date->diffInDays(today()) : 0;

                return '<div class="ob-cell-stack"><span>'.$invoice->due_date->translatedFormat('d M Y').'</span>'
                    .($overdueDays ? '<span class="ob-cell-meta text-danger">Telat '.$overdueDays.' hari</span>' : '').'</div>';
            })
            ->editColumn('total_to_pay', fn (Invoice $invoice) => '<span class="text-nowrap">Rp '.number_format($invoice->total_to_pay, 0, ',', '.').'</span>')
            ->addColumn('outstanding', fn (Invoice $invoice) => '<span class="text-nowrap">Rp '.number_format($invoice->outstandingAmount(), 0, ',', '.').'</span>')
            ->editColumn('status', fn (Invoice $invoice) => '<span class="badge badge-'.$invoice->statusVariant().'">'.$invoice->statusLabel().'</span>')
            ->addColumn('action', fn (Invoice $invoice) => '<div class="ob-row-actions">'
                .'<a href="'.route('b2b/invoices/print', $invoice->id).'" target="_blank" class="ob-icon-btn ob-icon-btn--sm" title="Cetak invoice" aria-label="Cetak invoice"><i class="fas fa-print" aria-hidden="true"></i></a>'
                .'</div>')
            ->rawColumns(['invoice_number', 'due_date', 'total_to_pay', 'outstanding', 'status', 'action'])
            ->setRowId('id');
    }

    public function query(Invoice $model): QueryBuilder
    {
        $today = today()->toDateString();
        $query = $model->newQuery()
            ->select('b2b_invoices.*')
            ->with(['customer', 'order'])
            ->whereHas('order', fn ($order) => $order->withTrashed()->whereIn('outlet_id', auth()->user()->outletIds()));

        return match (request('status')) {
            'open' => $query->whereIn('b2b_invoices.status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL, Invoice::STATUS_OVERDUE]),
            'overdue' => $query->whereIn('b2b_invoices.status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL, Invoice::STATUS_OVERDUE])
                ->whereDate('b2b_invoices.due_date', '<', $today),
            'paid' => $query->where('b2b_invoices.status', Invoice::STATUS_PAID),
            'void' => $query->where('b2b_invoices.status', Invoice::STATUS_VOID),
            default => $query,
        };
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('b2b-invoice-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, ['status' => "$('#status-filter').val()"])
            ->dom("<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>><'ob-table-scroll'tr><'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>")
            ->orderBy(2, 'desc');
    }

    public function getColumns(): array
    {
        return [
            Column::make('invoice_number')->title('Invoice'),
            Column::make('customer_name')->title('Customer')->name('customer.name'),
            Column::make('issue_date')->title('Tanggal')->searchable(false),
            Column::make('due_date')->title('Jatuh Tempo')->searchable(false),
            Column::make('total_to_pay')->title('Total')->searchable(false),
            Column::computed('outstanding')->title('Sisa Tagihan'),
            Column::make('status')->title('Status')->searchable(false),
            Column::computed('action')->title('')->exportable(false)->printable(false)->width(60)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'B2B_Invoice_'.date('YmdHis');
    }
}
