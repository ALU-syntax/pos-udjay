<?php

namespace App\DataTables\OrderTable;

use App\Models\OrderTable\OrderPayment;
use App\Support\OrderTable\Mask;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class PaymentMonitorDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('created_at', fn (OrderPayment $payment) => $payment->created_at?->format('d M Y H:i') ?? '-')
            ->editColumn('expires_at', fn (OrderPayment $payment) => $payment->expires_at?->format('d M Y H:i') ?? '-')
            ->editColumn('paid_at', fn (OrderPayment $payment) => $payment->paid_at?->format('d M Y H:i') ?? '-')
            ->addColumn('order_no', fn (OrderPayment $payment) => $payment->order?->order_no ?? '-')
            ->addColumn('outlet_name', fn (OrderPayment $payment) => $payment->order?->outlet?->name ?? '-')
            ->editColumn('gateway_ref', fn (OrderPayment $payment) => Mask::gatewayRef($payment->gateway_ref))
            ->editColumn('amount', fn (OrderPayment $payment) => 'Rp '.number_format((int) $payment->amount, 0, ',', '.'))
            ->editColumn('status', fn (OrderPayment $payment) => '<span class="badge bg-'.self::statusColor($payment->status).'">'.e($payment->status).'</span>')
            ->removeColumn('order')
            ->rawColumns(['status'])
            ->setRowId('id');
    }

    public function query(OrderPayment $model): QueryBuilder
    {
        $outletIds = auth()->user()->outletIds();
        $query = $model->newQuery()
            ->with(['order.outlet'])
            ->whereHas('order', fn (QueryBuilder $order) => $order->whereIn('outlet_id', $outletIds));

        if (request()->filled('outlet_id')) {
            $outletId = request()->integer('outlet_id');
            if (in_array($outletId, $outletIds, true)) {
                $query->whereHas('order', fn (QueryBuilder $order) => $order->where('outlet_id', $outletId));
            }
        }
        if (request()->filled('method')) {
            $query->where('method', request('method'));
        }
        if (request()->filled('status')) {
            $query->where('status', request('status'));
        }
        if (request()->filled('order_no')) {
            $term = request('order_no');
            $query->whereHas('order', fn (QueryBuilder $order) => $order->where('order_no', 'like', '%'.$term.'%'));
        }
        if (request()->filled('date_from')) {
            $query->whereDate('created_at', '>=', request('date_from'));
        }
        if (request()->filled('date_to')) {
            $query->whereDate('created_at', '<=', request('date_to'));
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('payment-monitor-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, [
                'outlet_id' => "$('#outlet-filter').val()",
                'method' => "$('#method-filter').val()",
                'status' => "$('#status-filter').val()",
                'order_no' => "$('#order-filter').val()",
                'date_from' => "$('#date-from-filter').val()",
                'date_to' => "$('#date-to-filter').val()",
            ])
            ->responsive(true)
            ->orderBy(0, 'desc');
    }

    public function getColumns(): array
    {
        return [
            Column::make('created_at')->title('Dibuat'),
            Column::computed('order_no')->title('Order'),
            Column::computed('outlet_name')->title('Outlet')->name('order.outlet.name'),
            Column::make('method')->title('Metode'),
            Column::make('gateway_ref')->title('Referensi Gateway'),
            Column::make('amount')->title('Nominal'),
            Column::make('status')->title('Status'),
            Column::make('expires_at')->title('Kedaluwarsa'),
            Column::make('paid_at')->title('Dibayar'),
        ];
    }

    protected function filename(): string
    {
        return 'PaymentMonitor_'.date('YmdHis');
    }

    private static function statusColor(string $status): string
    {
        return match ($status) {
            'paid' => 'success',
            'pending' => 'warning',
            'failed', 'expired' => 'danger',
            default => 'secondary',
        };
    }
}
