<?php

namespace App\DataTables;

use App\Models\ProcurementPlans;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class ProcurementPlanDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function (ProcurementPlans $procurementPlan) {
                return view('layouts.procurement_plan.action', compact('procurementPlan'))->render();
            })
            ->editColumn('plan_number', function (ProcurementPlans $procurementPlan) {
                return '<span class="badge bg-light text-dark border">' . e($procurementPlan->plan_number) . '</span>';
            })
            ->addColumn('status_name', function (ProcurementPlans $procurementPlan) {
                $status = $procurementPlan->status;
                $code = $status?->code ?? 'unknown';
                $class = match ($code) {
                    'draft' => 'bg-secondary',
                    'reviewed' => 'bg-primary',
                    'approved' => 'bg-success',
                    'converted_to_po' => 'bg-info text-dark',
                    'cancelled' => 'bg-dark',
                    default => 'bg-light text-dark border',
                };

                return '<span class="badge ' . $class . '">' . e($status?->name ?? '-') . '</span>'
                    . '<span class="d-none">' . e($code) . '</span>';
            })
            ->addColumn('planning_location_name', function (ProcurementPlans $procurementPlan) {
                return $procurementPlan->planningLocation
                    ? e($procurementPlan->planningLocation->name)
                    : '<span class="text-muted">-</span>';
            })
            ->addColumn('items_count_label', function (ProcurementPlans $procurementPlan) {
                return '<strong>' . (int) ($procurementPlan->items_count ?? 0) . '</strong> item';
            })
            ->editColumn('items_sum_qty_required_base', function (ProcurementPlans $procurementPlan) {
                return number_format((float) ($procurementPlan->items_sum_qty_required_base ?? 0), 5, ',', '.');
            })
            ->editColumn('items_sum_qty_to_purchase_base', function (ProcurementPlans $procurementPlan) {
                return number_format((float) ($procurementPlan->items_sum_qty_to_purchase_base ?? 0), 5, ',', '.');
            })
            ->addColumn('planned_by_name', function (ProcurementPlans $procurementPlan) {
                return $procurementPlan->plannedBy
                    ? e($procurementPlan->plannedBy->name)
                    : '<span class="text-muted">-</span>';
            })
            ->editColumn('planned_at', function (ProcurementPlans $procurementPlan) {
                return $procurementPlan->planned_at ? $procurementPlan->planned_at->format('d M Y H:i') : '<span class="text-muted">-</span>';
            })
            ->editColumn('updated_at', function (ProcurementPlans $procurementPlan) {
                return $procurementPlan->updated_at ? $procurementPlan->updated_at->format('d M Y H:i') : '-';
            })
            ->filterColumn('status_name', function (QueryBuilder $query, $keyword) {
                $query->whereHas('status', fn ($status) => $status
                    ->where('code', 'like', "%{$keyword}%")
                    ->orWhere('name', 'like', "%{$keyword}%"));
            })
            ->filterColumn('planning_location_name', function (QueryBuilder $query, $keyword) {
                $query->whereHas('planningLocation', fn ($inventory) => $inventory->where('name', 'like', "%{$keyword}%"));
            })
            ->filterColumn('planned_by_name', function (QueryBuilder $query, $keyword) {
                $query->whereHas('plannedBy', fn ($user) => $user->where('name', 'like', "%{$keyword}%"));
            })
            ->addIndexColumn()
            ->rawColumns([
                'action',
                'plan_number',
                'status_name',
                'planning_location_name',
                'items_count_label',
                'planned_by_name',
                'planned_at',
            ]);
    }

    public function query(ProcurementPlans $model): QueryBuilder
    {
        return $model->newQuery()
            ->with(['status', 'planningLocation', 'plannedBy'])
            ->withCount('items')
            ->withSum('items', 'qty_required_base')
            ->withSum('items', 'qty_to_purchase_base')
            ->latest('updated_at');
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('procurement-plan-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->responsive(true)
            ->orderBy(8, 'desc')
            ->parameters([
                'autoWidth' => false,
                'scrollX' => true,
                'scrollCollapse' => true,
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::make('DT_RowIndex')->title('No')->searchable(false)->orderable(false)->width(48),
            Column::make('plan_number')->title('Nomor Plan'),
            Column::make('status_name')->title('Status')->orderable(false),
            Column::make('planning_location_name')->title('Lokasi Planning')->orderable(false),
            Column::make('items_count_label')->title('Item')->searchable(false)->orderable(false),
            Column::make('items_sum_qty_required_base')->title('Total Required')->searchable(false),
            Column::make('items_sum_qty_to_purchase_base')->title('To Purchase')->searchable(false),
            Column::make('planned_by_name')->title('Dibuat Oleh')->orderable(false),
            Column::make('planned_at')->title('Planned At'),
            Column::make('updated_at')->title('Update Terakhir'),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width(90)
                ->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'Procurement_Plan_' . date('YmdHis');
    }
}
