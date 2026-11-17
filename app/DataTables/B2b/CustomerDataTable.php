<?php

namespace App\DataTables\B2b;

use App\Models\B2b\Contract;
use App\Models\B2b\Customer;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class CustomerDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('name', fn (Customer $customer) => '<div class="b2b-cust-cell">'
                .'<span class="b2b-avatar b2b-avatar--'.e($customer->type).($customer->is_active ? '' : ' is-inactive').'" aria-hidden="true">'.e($customer->initials()).'</span>'
                .'<div class="ob-cell-stack">'
                .'<a href="'.route('b2b/customers/show', $customer->id).'" class="b2b-cust-cell__name">'.e($customer->name).'</a>'
                .'<span class="ob-cell-meta">'.e($customer->typeLabel()).' · '.e($customer->code).'</span>'
                .'</div></div>')
            ->addColumn('contact', function (Customer $customer) {
                $details = array_values(array_filter([$customer->phone, $customer->email]));
                if (blank($customer->contact_name) && ! $details) {
                    return '<span class="ob-cell-meta">Belum ada kontak</span>';
                }

                // Baris atas: nama PIC; tanpa PIC, telepon/email naik ke atas.
                $title = filled($customer->contact_name) ? $customer->contact_name : array_shift($details);

                return '<div class="ob-cell-stack"><span class="b2b-cell-strong">'.e($title).'</span>'
                    .($details ? '<span class="ob-cell-meta">'.e(implode(' · ', $details)).'</span>' : '').'</div>';
            })
            ->addColumn('contract', function (Customer $customer) {
                $contract = $customer->contracts->first();
                if (! $contract) {
                    return '<span class="ob-cell-meta">Harga normal</span>';
                }

                return '<div class="ob-cell-stack"><span class="badge badge-success">'.e($contract->contract_number).'</span>'
                    .'<span class="ob-cell-meta">s.d. '.$contract->end_date->translatedFormat('d M Y').'</span></div>';
            })
            ->editColumn('is_active', fn (Customer $customer) => $customer->is_active
                ? '<span class="badge badge-success">Aktif</span>'
                : '<span class="badge badge-muted">Nonaktif</span>')
            ->addColumn('action', fn (Customer $customer) => view('layouts.b2b.customers.action', compact('customer'))->render())
            ->rawColumns(['name', 'contact', 'contract', 'is_active', 'action'])
            ->setRowId('id');
    }

    public function query(Customer $model): QueryBuilder
    {
        $today = today()->toDateString();

        $query = $model->newQuery()
            ->with([
                'contracts' => fn ($contracts) => $contracts->effectiveOn(today()),
            ]);

        if (array_key_exists((string) request('type'), Customer::TYPES)) {
            $query->where('type', request('type'));
        }

        return match (request('status')) {
            'active' => $query->where('is_active', true),
            'inactive' => $query->where('is_active', false),
            'contract' => $query->whereHas('contracts', fn ($contracts) => $contracts->effectiveOn(today())),
            'no_contract' => $query->whereDoesntHave('contracts', fn ($contracts) => $contracts
                ->where('status', Contract::STATUS_ACTIVE)
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)),
            default => $query,
        };
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('b2b-customer-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, ['status' => "$('#status-filter').val()", 'type' => "$('#type-filter').val()"])
            ->dom("<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>><'ob-table-scroll'tr><'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>")
            ->orderBy(0, 'asc');
    }

    public function getColumns(): array
    {
        return [
            Column::make('name')->title('Customer'),
            Column::computed('contact')->title('Kontak'),
            Column::computed('contract')->title('Kontrak Berlaku'),
            Column::make('is_active')->title('Status')->searchable(false),
            Column::make('code')->visible(false),
            Column::make('phone')->visible(false),
            Column::make('email')->visible(false),
            Column::computed('action')->title('')->exportable(false)->printable(false)->width(110)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'B2B_Customer_'.date('YmdHis');
    }
}
