<?php

namespace App\DataTables\B2b;

use App\Models\B2b\Contract;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ContractDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('contract_number', fn (Contract $contract) => '<div class="b2b-cust-cell">'
                .'<span class="b2b-doc-icon b2b-doc-icon--'.$contract->statusVariant().'" aria-hidden="true"><i class="fas fa-file-signature"></i></span>'
                .'<div class="ob-cell-stack">'
                .'<a href="'.route('b2b/contracts/show', $contract->id).'" class="b2b-cust-cell__name b2b-mono">'.e($contract->contract_number).'</a>'
                .'<span class="ob-cell-meta">Dibuat '.$contract->created_at?->translatedFormat('d M Y').'</span>'
                .'</div></div>')
            ->addColumn('customer_name', function (Contract $contract) {
                $customer = $contract->customer;

                return '<div class="b2b-cust-cell">'
                    .'<span class="b2b-avatar b2b-avatar--sm b2b-avatar--'.e($customer?->type).'" aria-hidden="true">'.e($customer?->initials()).'</span>'
                    .'<div class="ob-cell-stack">'
                    .'<a href="'.route('b2b/customers/show', $contract->b2b_customer_id).'" class="b2b-cust-cell__name">'.e($customer?->name).'</a>'
                    .'<span class="ob-cell-meta">'.e($customer?->code).'</span>'
                    .'</div></div>';
            })
            ->addColumn('period', fn (Contract $contract) => view('layouts.b2b.contracts.partials.period', compact('contract'))->render())
            ->editColumn('items_count', fn (Contract $contract) => '<div class="ob-cell-stack">'
                .'<span class="b2b-cell-strong">'.number_format($contract->products_count, 0, ',', '.').' produk</span>'
                .($contract->items_count > $contract->products_count
                    ? '<span class="ob-cell-meta">'.number_format($contract->items_count, 0, ',', '.').' harga termasuk tingkat qty</span>'
                    : '')
                .'</div>')
            ->editColumn('status', fn (Contract $contract) => '<span class="badge badge-'.$contract->statusVariant().'">'.$contract->statusLabel().'</span>')
            ->addColumn('action', fn (Contract $contract) => view('layouts.b2b.contracts.action', compact('contract'))->render())
            ->rawColumns(['contract_number', 'customer_name', 'period', 'items_count', 'status', 'action'])
            ->setRowId('id');
    }

    public function query(Contract $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->select('b2b_contracts.*')
            ->with('customer')
            ->withCount(['items', 'items as products_count' => fn ($items) => $items->select(DB::raw('count(distinct variant_id)'))]);

        // Status tampilan dihitung dari status + tanggal (lihat Contract::statusLabel()).
        $today = today()->toDateString();
        match (request('status')) {
            'scheduled' => $query->where('status', Contract::STATUS_ACTIVE)->whereDate('start_date', '>', $today),
            'running' => $query->where('status', Contract::STATUS_ACTIVE)->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today),
            'ending' => $query->where('status', Contract::STATUS_ACTIVE)->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)
                ->whereDate('end_date', '<=', today()->addDays(30)->toDateString()),
            'ended' => $query->where('status', Contract::STATUS_ACTIVE)->whereDate('end_date', '<', $today),
            'terminated' => $query->where('status', Contract::STATUS_TERMINATED),
            default => null,
        };
        if (request()->filled('customer_id')) {
            $query->where('b2b_customer_id', request()->integer('customer_id'));
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('b2b-contract-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, ['status' => "$('#status-filter').val()"])
            ->dom("<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>><'ob-table-scroll'tr><'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>")
            ->orderBy(2, 'desc');
    }

    public function getColumns(): array
    {
        return [
            Column::make('contract_number')->title('No. Kontrak'),
            Column::make('customer_name')->title('Customer')->name('customer.name'),
            Column::computed('period')->title('Periode')->orderable(true)->name('start_date'),
            Column::make('items_count')->title('Isi kontrak')->searchable(false),
            Column::make('status')->title('Status')->searchable(false),
            Column::computed('action')->title('')->exportable(false)->printable(false)->width(120)->addClass('text-end'),
        ];
    }

    protected function filename(): string
    {
        return 'B2B_Kontrak_'.date('YmdHis');
    }
}
