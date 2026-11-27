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
                $meta = 'Dibuat ' . ($procurementPlan->planned_at?->translatedFormat('d M Y, H:i') ?? '-');
                if ($procurementPlan->plannedBy) {
                    $meta .= ' · ' . $procurementPlan->plannedBy->name;
                }

                return '<div class="ob-cell-stack">'
                    . '<span class="ob-item-cell__title pp-nowrap">' . e($procurementPlan->plan_number) . '</span>'
                    . '<span class="ob-cell-meta">' . e($meta) . '</span>'
                    . '</div>';
            })
            ->addColumn('planning_location_name', fn (ProcurementPlans $procurementPlan) => $procurementPlan->planningLocation
                ? '<span class="pp-strong">' . e($procurementPlan->planningLocation->name) . '</span>'
                : '<span class="ob-cell-meta">-</span>')
            ->addColumn('items_count_label', fn (ProcurementPlans $procurementPlan) => '<span class="pp-strong">'
                . number_format((int) $procurementPlan->items_count, 0, ',', '.') . ' bahan</span>')
            ->addColumn('purchase_orders_label', fn (ProcurementPlans $procurementPlan) => (int) $procurementPlan->purchase_orders_count > 0
                ? '<span class="pp-strong">' . number_format((int) $procurementPlan->purchase_orders_count, 0, ',', '.') . ' PO</span>'
                : '<span class="ob-cell-meta pp-nowrap">Belum ada PO</span>')
            // Total qty tidak ditampilkan: tiap bahan punya satuan dasar berbeda, jadi jumlahnya tidak bermakna
            ->addColumn('estimated_total', function (ProcurementPlans $procurementPlan) {
                if ($procurementPlan->items_sum_estimated_subtotal === null) {
                    return '<span class="ob-cell-meta pp-nowrap">Belum ada harga</span>';
                }

                $total = (float) $procurementPlan->items_sum_estimated_subtotal;

                return '<span class="pp-num">Rp ' . number_format($total, fmod($total, 1) ? 2 : 0, ',', '.') . '</span>';
            })
            ->addColumn('status_name', fn (ProcurementPlans $procurementPlan) => $procurementPlan->status
                ? '<span class="badge ' . $procurementPlan->status->badge_class . '">' . e($procurementPlan->status->label) . '</span>'
                : '<span class="ob-cell-meta">-</span>')
            ->editColumn('updated_at', fn (ProcurementPlans $procurementPlan) => $procurementPlan->updated_at
                ? '<span class="ob-cell-meta pp-nowrap">' . e($procurementPlan->updated_at->translatedFormat('d M Y, H:i')) . '</span>'
                : '<span class="ob-cell-meta">-</span>')
            ->filterColumn('plan_number', fn (QueryBuilder $query, string $keyword) => $query
                ->where('procurement_plans.plan_number', 'like', "%{$keyword}%"))
            ->rawColumns(['action', 'plan_number', 'planning_location_name', 'items_count_label', 'purchase_orders_label', 'estimated_total', 'status_name', 'updated_at'])
            ->setRowId('id');
    }

    public function query(ProcurementPlans $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->with(['status', 'planningLocation', 'plannedBy'])
            ->withCount(['items', 'purchaseOrders'])
            ->withSum('items', 'estimated_subtotal');

        // Filter dikirim lewat parameter ajax (bukan pencarian kolom) agar bisa dikombinasikan
        $status = $this->request()->get('status');
        if (ctype_digit((string) $status)) {
            $query->where('status_id', (int) $status);
        }

        $location = $this->request()->get('location');
        if (ctype_digit((string) $location)) {
            $query->where('planning_location_id', (int) $location);
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('procurement-plan-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, [
                'status' => "$('#filter-status').val()",
                'location' => "$('#filter-location').val()",
            ])
            // Hanya tabel yang scroll horizontal, length/search/paging tetap di tempat.
            ->dom("<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>><'ob-table-scroll'tr><'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>")
            // Terbaru di atas
            ->orderBy(6, 'desc');
    }

    public function getColumns(): array
    {
        return [
            Column::make('plan_number')->title('Plan'),
            Column::make('planning_location_name')->title('Lokasi Planning')->orderable(false)->searchable(false),
            Column::make('items_count_label')->title('Item')->orderable(false)->searchable(false),
            Column::make('purchase_orders_label')->title('PO')->orderable(false)->searchable(false),
            Column::make('estimated_total')->title('Estimasi')->orderable(false)->searchable(false)->addClass('text-end'),
            Column::make('status_name')->title('Status')->orderable(false)->searchable(false),
            Column::make('updated_at')->title('Diperbarui')->searchable(false),
            Column::computed('action')
                ->title('')
                ->exportable(false)
                ->printable(false)
                ->width(120)
                ->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'Procurement_Plan_' . date('YmdHis');
    }
}
