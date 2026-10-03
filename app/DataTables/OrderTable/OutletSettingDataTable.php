<?php

namespace App\DataTables\OrderTable;

use App\Models\OrderTable\OutletSetting;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class OutletSettingDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('outlet_name', fn (OutletSetting $setting) => e($setting->outlet?->name ?? '-'))
            ->addColumn('effective_state', function (OutletSetting $setting) {
                $now = now()->format('H:i:s');
                $outsideHours = $setting->open_time && $setting->close_time
                    && ($setting->open_time <= $setting->close_time
                        ? ($now < $setting->open_time || $now > $setting->close_time)
                        : ($now > $setting->close_time && $now < $setting->open_time));

                if (! $setting->order_enabled) {
                    return '<span class="badge badge-secondary">Nonaktif</span>';
                }
                if ($setting->forced_close) {
                    return '<span class="badge badge-danger">Dipause</span>';
                }
                if ($outsideHours) {
                    return '<span class="badge badge-warning">Di luar jam</span>';
                }

                return '<span class="badge badge-success">Menerima order</span>';
            })
            ->editColumn('stock_mode', function (OutletSetting $setting) {
                $labels = ['off' => 'Tanpa cek stok', 'status_only' => 'Status saja', 'strict' => 'Ketat'];

                return e($labels[$setting->stock_mode] ?? $setting->stock_mode);
            })
            ->addColumn('operating_hours', fn (OutletSetting $setting) => e(
                $setting->open_time && $setting->close_time
                    ? substr($setting->open_time, 0, 5).' - '.substr($setting->close_time, 0, 5)
                    : '24 jam / belum diatur'
            ))
            ->addColumn('geofence', function (OutletSetting $setting) {
                if ($setting->outlet?->latitude === null || $setting->outlet?->longitude === null) {
                    return '<span class="text-muted">Koordinat belum diatur</span>';
                }

                return '<div>'.e($setting->outlet->latitude.', '.$setting->outlet->longitude).'</div>'
                    .'<small class="text-muted">Radius '.e(number_format((int) $setting->outlet->geofence_radius_m)).' m</small>';
            })
            ->addColumn('action', fn (OutletSetting $setting) => view(
                'layouts.order-table.outlet-settings.action',
                compact('setting')
            )->render())
            ->rawColumns(['effective_state', 'geofence', 'action'])
            ->setRowId('id');
    }

    public function query(OutletSetting $model): QueryBuilder
    {
        $outletIds = auth()->user()->outletIds();
        $query = $model->newQuery()
            ->with('outlet')
            ->whereIn('outlet_id', $outletIds);

        $outletId = request()->integer('outlet_id');
        if ($outletId && in_array($outletId, $outletIds, true)) {
            $query->where('outlet_id', $outletId);
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('outlet-setting-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, ['outlet_id' => "$('#outlet-filter').val()"])
            ->responsive(true)
            ->orderBy(0);
    }

    public function getColumns(): array
    {
        return [
            Column::make('outlet_name')->title('Outlet')->name('outlet.name'),
            Column::computed('effective_state')->title('Status Efektif'),
            Column::make('stock_mode')->title('Mode Stok'),
            Column::computed('operating_hours')->title('Jam Operasional'),
            Column::computed('geofence')->title('Koordinat / Radius'),
            Column::computed('action')->title('Aksi')->exportable(false)->printable(false)->orderable(false)->searchable(false)->width(130)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'OutletSetting_'.date('YmdHis');
    }
}
