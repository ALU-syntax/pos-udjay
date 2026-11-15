<?php

namespace App\DataTables\B2b;

use App\Models\B2b\Contract;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ContractDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('contract_number', fn (Contract $contract) => '<a href="'.route('b2b/contracts/show', $contract->id).'" class="fw-bold">'.e($contract->contract_number).'</a>')
            ->addColumn('customer_name', fn (Contract $contract) => '<div class="ob-cell-stack">'
                .'<a href="'.route('b2b/customers/show', $contract->b2b_customer_id).'">'.e($contract->customer?->name).'</a>'
                .'<span class="ob-cell-meta">'.e($contract->customer?->code).'</span></div>')
            ->addColumn('period', function (Contract $contract) {
                $remaining = $contract->isEffective() ? today()->diffInDays($contract->end_date, false) : null;
                $hint = $remaining !== null && $remaining <= 30
                    ? '<span class="ob-cell-meta text-danger">Berakhir '.($remaining === 0 ? 'hari ini' : 'dalam '.$remaining.' hari').'</span>'
                    : '';

                return '<div class="ob-cell-stack"><span>'.$contract->start_date->translatedFormat('d M Y').' – '.$contract->end_date->translatedFormat('d M Y').'</span>'.$hint.'</div>';
            })
            ->editColumn('rules_count', fn (Contract $contract) => $contract->rules_count.' aturan')
            ->editColumn('status', fn (Contract $contract) => '<span class="badge badge-'.$contract->statusVariant().'">'.$contract->statusLabel().'</span>')
            ->addColumn('action', fn (Contract $contract) => view('layouts.b2b.contracts.action', compact('contract'))->render())
            ->rawColumns(['contract_number', 'customer_name', 'period', 'status', 'action'])
            ->setRowId('id');
    }

    public function query(Contract $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->select('b2b_contracts.*')
            ->with('customer')
            ->withCount('rules');

        if (request()->filled('status')) {
            $query->where('status', request('status'));
        }
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
            Column::make('rules_count')->title('Aturan')->searchable(false),
            Column::make('status')->title('Status')->searchable(false),
            Column::computed('action')->title('')->exportable(false)->printable(false)->width(110)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'B2B_Kontrak_'.date('YmdHis');
    }
}
