<?php

namespace App\DataTables\B2b;

use App\Models\B2b\Invoice;
use App\Models\B2b\Order;
use App\Support\B2b\ReminderButton;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class InvoiceDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('invoice_number', fn (Invoice $invoice) => '<div class="b2b-cust-cell">'
                .'<span class="b2b-doc-icon b2b-doc-icon--'.$invoice->statusVariant().'" aria-hidden="true"><i class="fas fa-file-invoice"></i></span>'
                .'<div class="ob-cell-stack">'
                .'<a href="'.route('b2b/invoices/print', $invoice->id).'" target="_blank" class="b2b-cust-cell__name b2b-mono">'.e($invoice->invoice_number).'</a>'
                .'<span class="ob-cell-meta">'.$invoice->issue_date->translatedFormat('d M Y')
                .' · <a href="'.route('b2b/orders/show', $invoice->b2b_order_id).'" class="b2b-mono">'.e($invoice->order?->order_number).'</a></span>'
                .'</div></div>')
            ->addColumn('customer_name', function (Invoice $invoice) {
                $customer = $invoice->customer;
                $name = auth()->user()->can('read b2b/customers')
                    ? '<a href="'.route('b2b/customers/show', $invoice->b2b_customer_id).'" class="b2b-cust-cell__name">'.e($customer?->name).'</a>'
                    : '<span class="b2b-cust-cell__name">'.e($customer?->name).'</span>';

                return '<div class="b2b-cust-cell">'
                    .'<span class="b2b-avatar b2b-avatar--sm b2b-avatar--'.e($customer?->type).'" aria-hidden="true">'.e($customer?->initials()).'</span>'
                    .'<div class="ob-cell-stack">'.$name
                    .'<span class="ob-cell-meta"><i class="fas fa-store me-1" aria-hidden="true"></i>'.e($invoice->order?->outlet?->name).'</span>'
                    .'</div></div>';
            })
            ->editColumn('due_date', fn (Invoice $invoice) => $this->dueCell($invoice))
            ->editColumn('total_to_pay', function (Invoice $invoice) {
                $outstanding = $invoice->outstandingAmount();
                $percent = $invoice->total_to_pay > 0 ? min(100, round($invoice->paid_amount / $invoice->total_to_pay * 100)) : 0;
                $overdue = $invoice->displayStatus() === Invoice::STATUS_OVERDUE;
                if ($invoice->status === Invoice::STATUS_VOID) {
                    return '<div class="b2b-bill"><span class="b2b-bill__amount text-muted"><s>Rp '.number_format($invoice->total_to_pay, 0, ',', '.').'</s></span>'
                        .'<span class="ob-cell-meta">Tidak ditagih</span></div>';
                }

                return '<div class="b2b-bill">'
                    .'<span class="b2b-bill__amount'.($overdue ? ' text-danger' : '').'">Rp '.number_format($outstanding, 0, ',', '.').'</span>'
                    .'<span class="b2b-bill__bar" aria-hidden="true"><span style="width: '.$percent.'%"></span></span>'
                    .'<span class="ob-cell-meta">'.($outstanding === 0 ? 'Lunas' : 'Sisa').' dari Rp '.number_format($invoice->total_to_pay, 0, ',', '.').'</span>'
                    .'</div>';
            })
            ->editColumn('status', fn (Invoice $invoice) => '<span class="badge badge-'.$invoice->statusVariant().'">'.$invoice->statusLabel().'</span>')
            ->addColumn('action', fn (Invoice $invoice) => '<div class="ob-row-actions">'
                .($invoice->canRecordPayment() && auth()->user()->can('pay b2b/invoices') ? $this->payButton($invoice) : '')
                .($invoice->sendableKinds() && auth()->user()->can('send b2b/reminders') ? $this->sendButton($invoice) : '')
                .'<a href="'.route('b2b/invoices/print', $invoice->id).'" target="_blank" class="ob-icon-btn ob-icon-btn--sm" title="Cetak invoice" aria-label="Cetak invoice '.e($invoice->invoice_number).'"><i class="fas fa-print" aria-hidden="true"></i></a>'
                .($invoice->status === Invoice::STATUS_PAID ? '<a href="'.route('b2b/invoices/receipt', $invoice->id).'" target="_blank" class="ob-icon-btn ob-icon-btn--sm" title="Cetak kwitansi" aria-label="Cetak kwitansi '.e($invoice->invoice_number).'"><i class="fas fa-receipt" aria-hidden="true"></i></a>' : '')
                                .'</div>')
            // Kolom Sisa tagihan diurutkan menurut sisa, bukan total.
            ->orderColumn('total_to_pay', '(b2b_invoices.total_to_pay - b2b_invoices.paid_amount) $1')
            ->rawColumns(['invoice_number', 'customer_name', 'due_date', 'total_to_pay', 'status', 'action'])
            ->setRowId('id');
    }

    /** Jatuh tempo + keterangan: bayar langsung, telat N hari, hari ini, N hari lagi. */
    private function dueCell(Invoice $invoice): string
    {
        if ($invoice->order?->payment_type === Order::PAYMENT_IMMEDIATE) {
            return '<div class="ob-cell-stack"><span class="b2b-cell-strong text-nowrap">Bayar langsung</span><span class="ob-cell-meta">Dibayar di kasir</span></div>';
        }
        $hint = '';
        if (in_array($invoice->status, [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL, Invoice::STATUS_OVERDUE], true)) {
            $days = today()->diffInDays($invoice->due_date, false);
            $hint = match (true) {
                $days < 0 => '<span class="b2b-due b2b-due--late">Telat '.abs($days).' hari</span>',
                $days === 0 => '<span class="b2b-due b2b-due--soon">Hari ini</span>',
                $days <= 7 => '<span class="b2b-due b2b-due--soon">'.$days.' hari lagi</span>',
                default => '<span class="ob-cell-meta">'.$days.' hari lagi</span>',
            };
        } elseif ($invoice->paid_at) {
            $hint = '<span class="ob-cell-meta">Lunas '.$invoice->paid_at->translatedFormat('d M Y').'</span>';
        }

        return '<div class="ob-cell-stack"><span class="b2b-cell-strong text-nowrap">'.$invoice->due_date->translatedFormat('d M Y').'</span>'.$hint.'</div>';
    }

    /** Kirim invoice / reminder / kwitansi ke customer sekarang (dialog pilih jenis + Email / WhatsApp). */
    private function sendButton(Invoice $invoice): string
    {
        $label = ReminderButton::label($invoice);

        return '<button type="button" class="ob-icon-btn ob-icon-btn--sm" '.ReminderButton::attributes($invoice, 'b2b-invoice-table')
            .' title="'.$label.'" aria-label="'.$label.' '.e($invoice->invoice_number).'"><i class="fas fa-paper-plane" aria-hidden="true"></i></button>';
    }

    /** Tombol pembuka dialog "Catat Pelunasan" (layouts.b2b.invoices.partials.payment-modal). */
    private function payButton(Invoice $invoice): string
    {
        $data = [
            'pay-invoice' => '',
            'url' => route('b2b/invoices/pay', $invoice->id),
            'number' => $invoice->invoice_number,
            'customer' => $invoice->customer?->name,
            'total' => $invoice->total_to_pay,
            'paid' => $invoice->paid_amount,
            'outstanding' => $invoice->outstandingAmount(),
            'issue' => $invoice->issue_date->toDateString(),
            'outlet' => $invoice->order?->outlet_id,
        ];
        $attributes = collect($data)->map(fn ($value, $key) => 'data-'.$key.'="'.e((string) $value).'"')->implode(' ');

        return '<button type="button" class="ob-icon-btn ob-icon-btn--sm ob-icon-btn--primary" title="Catat pelunasan" aria-label="Catat pelunasan" '.$attributes.'>'
            .'<i class="fas fa-hand-holding-usd" aria-hidden="true"></i></button>';
    }

    public function query(Invoice $model): QueryBuilder
    {
        $today = today()->toDateString();
        $outletIds = auth()->user()->outletIds();
        $open = [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL, Invoice::STATUS_OVERDUE];
        $query = $model->newQuery()
            ->select('b2b_invoices.*')
            ->with(['customer', 'order.outlet'])
            ->whereHas('order', fn ($order) => $order->withTrashed()->whereIn('outlet_id', $outletIds));

        if (request()->filled('outlet_id') && in_array(request()->integer('outlet_id'), $outletIds, true)) {
            $query->whereHas('order', fn ($order) => $order->withTrashed()->where('outlet_id', request()->integer('outlet_id')));
        }
        if ($customerIds = self::customerIds()) {
            $query->whereIn('b2b_invoices.b2b_customer_id', $customerIds);
        }
        if (request()->filled('due_from') && request()->filled('due_to')) {
            $query->whereBetween('b2b_invoices.due_date', [
                Carbon::parse(request('due_from'))->toDateString(),
                Carbon::parse(request('due_to'))->toDateString(),
            ]);
        }

        return match (request('status')) {
            'open' => $query->whereIn('b2b_invoices.status', $open),
            'overdue' => $query->whereIn('b2b_invoices.status', $open)->whereDate('b2b_invoices.due_date', '<', $today),
            'due_soon' => $query->whereIn('b2b_invoices.status', $open)->whereDate('b2b_invoices.due_date', '>=', $today)
                ->whereDate('b2b_invoices.due_date', '<=', today()->addDays(7)->toDateString()),
            'paid' => $query->where('b2b_invoices.status', Invoice::STATUS_PAID),
            'void' => $query->where('b2b_invoices.status', Invoice::STATUS_VOID),
            default => $query,
        };
    }

    /**
     * Customer yang dipilih di filter (boleh lebih dari satu): `customer_ids[]`, atau `customer_id` tunggal untuk tautan lama.
     *
     * @return list<int>
     */
    public static function customerIds(): array
    {
        return collect(request()->input('customer_ids', []))
            ->push(request()->input('customer_id'))
            ->filter(fn ($id) => is_scalar($id) && ctype_digit((string) $id) && (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('b2b-invoice-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, [
                'status' => "$('#status-filter').val()",
                'outlet_id' => "$('#outlet-filter').val()",
                'customer_ids' => "$('#customer-filter').val()",
                'due_from' => "$('#due-from').val()",
                'due_to' => "$('#due-to').val()",
            ])
            ->dom("<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>><'ob-table-scroll'tr><'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>")
            ->orderBy(2, 'asc'); // jatuh tempo terdekat/terlama dulu
    }

    public function getColumns(): array
    {
        return [
            Column::make('invoice_number')->title('Invoice'),
            Column::make('customer_name')->title('Customer')->name('customer.name'),
            Column::make('due_date')->title('Jatuh tempo')->searchable(false),
            Column::make('status')->title('Status')->searchable(false),
            Column::make('total_to_pay')->title('Sisa tagihan')->searchable(false)->addClass('text-end'),
            Column::computed('action')->title('')->exportable(false)->printable(false)->width(120)->addClass('text-end'),
        ];
    }

    protected function filename(): string
    {
        return 'B2B_Invoice_'.date('YmdHis');
    }
}
