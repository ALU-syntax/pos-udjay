<?php

namespace App\DataTables\B2b;

use App\Models\B2b\ReminderLog;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

/** Aktivitas reminder: satu baris per kiriman (email gabungan / pesan WhatsApp). */
class ReminderLogDataTable extends DataTable
{
    /** Jumlah invoice yang ditampilkan per baris sebelum diringkas "+N lainnya". */
    private const INVOICE_PREVIEW = 3;

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('created_at', fn (ReminderLog $log) => '<div class="ob-cell-stack">'
                .'<span class="b2b-cell-strong text-nowrap">'.$log->reminder_date->translatedFormat('d M Y').'</span>'
                .'<span class="ob-cell-meta text-nowrap">'.($log->sent_at ? 'Terkirim '.$log->sent_at->format('H:i') : 'Dibuat '.$log->created_at->format('H:i')).'</span>'
                .'</div>')
            ->addColumn('customer_name', function (ReminderLog $log) {
                $customer = $log->customer;
                $name = auth()->user()->can('read b2b/customers')
                    ? '<a href="'.route('b2b/customers/show', $log->b2b_customer_id).'" class="b2b-cust-cell__name">'.e($customer?->name).'</a>'
                    : '<span class="b2b-cust-cell__name">'.e($customer?->name).'</span>';

                return '<div class="b2b-cust-cell">'
                    .'<span class="b2b-avatar b2b-avatar--sm b2b-avatar--'.e($customer?->type).'" aria-hidden="true">'.e($customer?->initials()).'</span>'
                    .'<div class="ob-cell-stack">'.$name
                    .'<span class="ob-cell-meta"><i class="'.$log->channelIcon().' me-1" aria-hidden="true"></i>'.e($log->recipient ?: 'Tujuan kosong').'</span>'
                    .'</div></div>';
            })
            ->addColumn('invoice_list', function (ReminderLog $log) {
                $invoices = $log->invoices->sortBy('due_date')->values();
                $chips = $invoices->take(self::INVOICE_PREVIEW)->map(fn ($invoice) => '<span class="text-nowrap">'
                    .'<span class="b2b-mono">'.e($invoice->invoice_number).'</span> '
                    .'<span class="b2b-due '.((int) $invoice->pivot->offset_days > 0 ? 'b2b-due--late' : 'b2b-due--soon').'">'.ReminderLog::offsetLabel((int) $invoice->pivot->offset_days).'</span>'
                    .'</span>')->implode('<br>');
                $more = $invoices->count() - self::INVOICE_PREVIEW;

                return '<div class="ob-cell-stack">'.$chips.($more > 0 ? '<span class="ob-cell-meta">+'.$more.' invoice lainnya</span>' : '').'</div>';
            })
            ->editColumn('trigger', fn (ReminderLog $log) => '<div class="ob-cell-stack">'
                .'<span class="text-nowrap">'.(ReminderLog::TRIGGER_LABELS[$log->trigger] ?? $log->trigger).'</span>'
                .($log->trigger === ReminderLog::TRIGGER_MANUAL && $log->sender ? '<span class="ob-cell-meta">'.e($log->sender->name).'</span>' : '')
                .'</div>')
            ->editColumn('status', fn (ReminderLog $log) => '<div class="ob-cell-stack">'
                .'<span><span class="badge badge-'.$log->statusVariant().'">'.$log->statusLabel().'</span></span>'
                .($log->error ? '<span class="ob-cell-meta b2b-reminder-error" title="'.e($log->error).'">'.e(Str::limit($log->error, 70)).'</span>' : '')
                .'</div>')
            ->addColumn('action', function (ReminderLog $log) {
                $buttons = '<button type="button" class="ob-icon-btn ob-icon-btn--sm" data-reminder-detail="'.route('b2b/reminders/show', $log->id).'" title="Lihat detail" aria-label="Lihat detail kiriman"><i class="fas fa-eye" aria-hidden="true"></i></button>';
                if ($log->canResend() && auth()->user()->can('send b2b/reminders')) {
                    $buttons = '<a href="'.route('b2b/reminders/resend', $log->id).'" class="ob-icon-btn ob-icon-btn--sm ob-icon-btn--primary" data-b2b-confirm data-icon="question"'
                        .' data-title="Kirim ulang reminder?" data-text="Dikirim sebagai kiriman manual ke tujuan terbaru di data customer. Invoice yang sudah lunas tidak ikut."'
                        .' data-confirm-text="Ya, kirim ulang" data-table="b2b-reminder-table" title="Kirim ulang" aria-label="Kirim ulang"><i class="fas fa-redo" aria-hidden="true"></i></a>'.$buttons;
                }

                return '<div class="ob-row-actions">'.$buttons.'</div>';
            })
            ->rawColumns(['created_at', 'customer_name', 'invoice_list', 'trigger', 'status', 'action'])
            ->setRowId('id');
    }

    public function query(ReminderLog $model): QueryBuilder
    {
        $query = $model->newQuery()
            ->select('b2b_reminder_logs.*')
            ->with(['customer', 'invoices', 'sender']);

        foreach (['status' => ReminderLog::STATUS_LABELS, 'channel' => ReminderLog::CHANNEL_LABELS, 'trigger' => ReminderLog::TRIGGER_LABELS] as $field => $options) {
            if (array_key_exists((string) request($field), $options)) {
                $query->where("b2b_reminder_logs.{$field}", request($field));
            }
        }
        if (request()->filled('customer_id')) {
            $query->where('b2b_reminder_logs.b2b_customer_id', request()->integer('customer_id'));
        }
        if (request()->filled('date_from') && request()->filled('date_to')) {
            $query->whereBetween('b2b_reminder_logs.reminder_date', [
                Carbon::parse(request('date_from'))->toDateString(),
                Carbon::parse(request('date_to'))->toDateString(),
            ]);
        }

        return $query;
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('b2b-reminder-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', null, [
                'status' => "$('#status-filter').val()",
                'channel' => "$('#channel-filter').val()",
                'trigger' => "$('#trigger-filter').val()",
                'customer_id' => "$('#customer-filter').val()",
                'date_from' => "$('#date-from').val()",
                'date_to' => "$('#date-to').val()",
            ])
            ->dom("<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>><'ob-table-scroll'tr><'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>")
            ->orderBy(0, 'desc');
    }

    public function getColumns(): array
    {
        return [
            Column::make('created_at')->title('Waktu')->searchable(false),
            Column::make('customer_name')->title('Customer & tujuan')->name('customer.name'),
            Column::computed('invoice_list')->title('Invoice'),
            Column::make('trigger')->title('Pemicu')->searchable(false),
            Column::make('status')->title('Status')->searchable(false),
            Column::computed('action')->title('')->exportable(false)->printable(false)->width(80)->addClass('text-end'),
        ];
    }

    protected function filename(): string
    {
        return 'B2B_Reminder_'.date('YmdHis');
    }
}
