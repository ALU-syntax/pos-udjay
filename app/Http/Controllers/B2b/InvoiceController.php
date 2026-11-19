<?php

namespace App\Http\Controllers\B2b;

use App\DataTables\B2b\InvoiceDataTable;
use App\Http\Controllers\Controller;
use App\Models\B2b\Invoice;
use App\Models\B2b\Payment;
use App\Models\B2b\PaymentAllocation;
use App\Models\Outlets;
use App\Services\B2b\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InvoiceController extends Controller
{
    public function __construct(private readonly PaymentService $payments)
    {
        $this->abilities = [];
    }

    public function index(InvoiceDataTable $dataTable, Request $request)
    {
        $this->authorize('read b2b/invoices');

        $outletIds = $request->user()->outletIds();
        $today = today()->toDateString();
        // Invoice belum lunas; status overdue dihitung dari tanggal (lihat Invoice::displayStatus()).
        $open = fn () => Invoice::query()
            ->whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL, Invoice::STATUS_OVERDUE])
            ->whereHas('order', fn ($order) => $order->whereIn('outlet_id', $outletIds));
        $summary = fn ($query) => $query->selectRaw('count(*) as total, coalesce(sum(total_to_pay - paid_amount), 0) as amount')->first();
        $unpaid = $summary($open());
        $overdue = $summary($open()->whereDate('due_date', '<', $today));
        $dueSoon = $summary($open()->whereDate('due_date', '>=', $today)->whereDate('due_date', '<=', today()->addDays(7)->toDateString()));
        // Dana masuk bulan ini (bayar langsung + pelunasan tempo), dari alokasi ke invoice di outlet user.
        $received = PaymentAllocation::query()
            ->whereHas('payment', fn ($payment) => $payment->where('status', Payment::STATUS_POSTED)
                ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()]))
            ->whereHas('invoice.order', fn ($order) => $order->withTrashed()->whereIn('outlet_id', $outletIds))
            ->selectRaw('count(distinct b2b_payment_id) as total, coalesce(sum(amount), 0) as amount')
            ->first();

        return $dataTable->render('layouts.b2b.invoices.index', [
            'paymentMethods' => $this->payments->methodOptions(),
            'outlets' => Outlets::query()->whereIn('id', $outletIds)->orderBy('name')->get(['id', 'name']),
            'stats' => [
                'unpaid_count' => (int) $unpaid->total,
                'unpaid_amount' => (int) $unpaid->amount,
                'overdue_count' => (int) $overdue->total,
                'overdue_amount' => (int) $overdue->amount,
                'due_soon_count' => (int) $dueSoon->total,
                'due_soon_amount' => (int) $dueSoon->amount,
                'received_count' => (int) $received->total,
                'received_amount' => (int) $received->amount,
            ],
        ]);
    }

    /** Halaman cetak invoice. "Simpan sebagai PDF" lewat dialog cetak browser. */
    public function print(int $invoice)
    {
        abort_unless(request()->user()->can('read b2b/invoices') || request()->user()->can('read b2b/orders'), 403);

        $invoice = $this->invoiceQuery()
            ->whereKey($invoice)
            ->with(['order.items', 'order.outlet', 'order.contract', 'customer', 'schedules', 'allocations.payment'])
            ->firstOrFail();

        return view('layouts.b2b.invoices.print', ['invoice' => $invoice]);
    }

    /** Catat pelunasan invoice tempo (boleh sebagian). */
    public function pay(Request $request, int $invoice)
    {
        $this->authorize('pay b2b/invoices');
        $invoice = $this->invoiceQuery()->whereKey($invoice)->firstOrFail();

        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'paid_date' => ['required', 'date_format:Y-m-d'],
            'category_payment_id' => ['required', 'integer'],
            'payment_id' => ['nullable', 'integer'],
            'outlet_id' => ['nullable', 'integer'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
            'attachment' => ['nullable', 'file', 'max:5120', 'mimes:pdf,jpg,jpeg,png'],
        ], [
            'category_payment_id.required' => 'Pilih metode pembayaran.',
        ], [
            'amount' => 'nominal',
            'paid_date' => 'tanggal bayar',
            'category_payment_id' => 'metode pembayaran',
            'payment_id' => 'jenis pembayaran',
            'outlet_id' => 'outlet penerima',
            'reference' => 'no. referensi',
            'notes' => 'catatan',
            'attachment' => 'bukti bayar',
        ]);

        $payment = $this->payments->record($request->user(), $invoice, collect($validated)->except('attachment')->all(), $request->file('attachment'));
        $invoice->refresh();

        return responseSuccess(false, $invoice->status === Invoice::STATUS_PAID
            ? 'Pelunasan Rp '.number_format($payment->amount, 0, ',', '.').' tercatat. Invoice '.$invoice->invoice_number.' lunas.'
            : 'Pembayaran Rp '.number_format($payment->amount, 0, ',', '.').' tercatat. Sisa tagihan Rp '.number_format($invoice->outstandingAmount(), 0, ',', '.').'.', [
                'payment_number' => $payment->payment_number,
                'status' => $invoice->status,
                'outstanding' => $invoice->outstandingAmount(),
            ]);
    }

    /** Batalkan pelunasan yang salah catat (tanpa refund). */
    public function reversePayment(Request $request, int $payment)
    {
        $this->authorize('pay b2b/invoices');
        $payment = $this->paymentQuery()->whereKey($payment)->firstOrFail();
        $validated = $request->validate(['reason' => ['required', 'string', 'max:255']], [], ['reason' => 'alasan']);

        $this->payments->reverse($request->user(), $payment, $validated['reason']);

        return responseSuccess(true, 'Pembayaran '.$payment->payment_number.' dibatalkan. Sisa tagihan invoice kembali bertambah.');
    }

    public function paymentAttachment(int $payment)
    {
        abort_unless(request()->user()->can('read b2b/invoices') || request()->user()->can('read b2b/orders'), 403);
        $payment = $this->paymentQuery()->whereKey($payment)->firstOrFail();
        abort_unless($payment->attachment_path && Storage::disk(PaymentService::ATTACHMENT_DISK)->exists($payment->attachment_path), 404);

        return Storage::disk(PaymentService::ATTACHMENT_DISK)->download($payment->attachment_path, $payment->attachment_name);
    }

    /** Invoice hanya bisa diakses user yang punya akses ke outlet pesanannya. */
    private function invoiceQuery()
    {
        return Invoice::query()->whereHas('order', fn ($order) => $order->withTrashed()->whereIn('outlet_id', request()->user()->outletIds()));
    }

    private function paymentQuery()
    {
        return Payment::query()->whereHas('allocations.invoice.order', fn ($order) => $order->withTrashed()->whereIn('outlet_id', request()->user()->outletIds()));
    }
}
