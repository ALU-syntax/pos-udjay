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
            ->editColumn('name', fn (Customer $customer) => '<div class="ob-cell-stack">'
                .'<a href="'.route('b2b/customers/show', $customer->id).'" class="fw-bold">'.e($customer->name).'</a>'
                .'<span><span class="badge badge-muted">'.e($customer->typeLabel()).'</span></span>'
                .'<span class="ob-cell-meta">'.e($customer->code).($customer->npwp ? ' · NPWP '.e($customer->npwp) : '').'</span></div>')
            ->addColumn('contact', function (Customer $customer) {
                $contact = $customer->contacts->first();
                $lines = array_filter([
                    $contact ? e($contact->name).($contact->position ? ' <span class="ob-cell-meta">('.e($contact->position).')</span>' : '') : null,
                    e($customer->phone ?: $contact?->phone),
                    e($customer->email ?: $contact?->email),
                ]);

                return $lines ? '<div class="ob-cell-stack">'.implode('<br>', $lines).'</div>' : '<span class="text-muted">-</span>';
            })
            ->addColumn('contract', function (Customer $customer) {
                $contract = $customer->contracts->first();
                if (! $contract) {
                    return '<span class="badge badge-muted">Harga normal</span>';
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
                'contacts' => fn ($contacts) => $contacts->where('is_primary', true),
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
