<?php

namespace App\DataTables\OrderTable;

use App\Models\OrderTable\TableSession;
use App\Support\OrderTable\Mask;
use App\Support\OrderTable\OrderLabels;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class SessionMonitorDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        // Kolom raw di-escape manual
        return (new EloquentDataTable($query))
            ->editColumn('opened_at', fn (TableSession $session) => $this->dateStack($session->opened_at))
            ->addColumn('outlet_table', fn (TableSession $session) => $this->stack(
                e($session->outlet?->name ?? '-'),
                e($session->table?->name ?? $session->table?->code ?? '-'),
            ))
            ->editColumn('device_id', fn (TableSession $session) => e(Mask::value($session->device_id)))
            ->editColumn('status', fn (TableSession $session) => $this->stack(
                OrderLabels::badge(OrderLabels::SESSION, $session->status),
                e(($session->status === 'open' ? 'Berjalan ' : 'Durasi ').$this->duration($session)),
            ))
            ->editColumn('closed_at', function (TableSession $session) {
                if (! $session->closed_at) {
                    return '<span class="text-muted">—</span>';
                }

                $reason = OrderLabels::label(OrderLabels::SESSION_CLOSE_REASON, $session->close_reason);
                $by = $session->closed_by ? ' · oleh '.OrderLabels::label(OrderLabels::ACTOR, $session->closed_by) : '';

                return $this->stack(
                    e($session->closed_at->format('d M Y, H:i')),
                    e(($session->close_reason ? $reason : 'Tanpa alasan').$by),
                );
            })
            ->addColumn('orders_count', fn (TableSession $session) => (int) ($session->orders_count ?? 0))
            ->addColumn('action', fn (TableSession $session) => view('layouts.order-table.sessions.action', compact('session'))->render())
            ->removeColumn('outlet')
            ->removeColumn('table')
            ->rawColumns(['opened_at', 'outlet_table', 'device_id', 'status', 'closed_at', 'action'])
            ->setRowId('id');
    }

    public function query(TableSession $model): QueryBuilder
    {
        $outletIds = auth()->user()->outletIds();
        $query = $model->newQuery()
            ->with(['outlet', 'table'])
            ->withCount('orders')
            ->whereIn('outlet_id', $outletIds);

        $outletId = request()->integer('outlet_id');
        if ($outletId && in_array($outletId, $outletIds, true)) {
            $query->where('outlet_id', $outletId);
        }
        if (request()->filled('status')) {
            $query->where('status', request('status'));
        }
        if (request()->filled('date_from')) {
            $query->whereDate('opened_at', '>=', request('date_from'));
        }
        if (request()->filled('date_to')) {
            $query->whereDate('opened_at', '<=', request('date_to'));
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('session-monitor-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, [
                'outlet_id' => "$('#outlet-filter').val()",
                'status' => "$('#status-filter').val()",
                'date_from' => "$('#date-from-filter').val()",
                'date_to' => "$('#date-to-filter').val()",
            ])
            // Default Bootstrap 4 dom, but only the table scrolls horizontally so length/search/paging stay put.
            ->dom("<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>><'ob-table-scroll'tr><'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>")
            ->orderBy(0, 'desc');
    }

    public function getColumns(): array
    {
        return [
            Column::make('opened_at')->title('Dibuka')->addClass('ob-col-time'),
            Column::computed('outlet_table')->title('Outlet / Meja')->addClass('ob-col-name'),
            Column::make('device_id')->title('Perangkat')->addClass('ob-col-device'),
            Column::make('status')->title('Status'),
            Column::make('closed_at')->title('Penutupan')->addClass('ob-col-time'),
            Column::computed('orders_count')->title('Order')->orderable(false)->searchable(false)->addClass('text-center'),
            Column::computed('action')->title('')->exportable(false)->printable(false)->orderable(false)->searchable(false)->width(60)->addClass('text-center'),
        ];
    }

    protected function filename(): string
    {
        return 'SessionMonitor_'.date('YmdHis');
    }

    /** Sel dua baris; kedua argumen harus sudah di-escape. */
    private function stack(string $main, string $meta): string
    {
        return '<div class="ob-cell-stack"><span>'.$main.'</span><span class="ob-cell-meta">'.$meta.'</span></div>';
    }

    private function dateStack(?Carbon $date): string
    {
        return $date
            ? $this->stack(e($date->format('d M Y')), e($date->format('H:i')))
            : '<span class="text-muted">—</span>';
    }

    /** Lama sesi sampai ditutup, atau sampai sekarang bila masih aktif. */
    private function duration(TableSession $session): string
    {
        if (! $session->opened_at) {
            return '-';
        }

        $minutes = (int) abs($session->opened_at->diffInMinutes($session->closed_at ?? now()));

        return match (true) {
            $minutes < 60 => $minutes.' mnt',
            $minutes < 1440 => intdiv($minutes, 60).' jam '.($minutes % 60).' mnt',
            default => intdiv($minutes, 1440).' hari '.intdiv($minutes % 1440, 60).' jam',
        };
    }
}
