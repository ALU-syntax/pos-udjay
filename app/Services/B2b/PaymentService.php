<?php

namespace App\Services\B2b;

use App\Models\B2b\Invoice;
use App\Models\B2b\InvoiceSchedule;
use App\Models\B2b\Payment;
use App\Models\B2b\PaymentAllocation;
use App\Models\B2b\Refund;
use App\Models\CategoryPayment;
use App\Models\Payment as PaymentMethod;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pencatatan pembayaran B2B. Metode diambil dari master pembayaran POS (`category_payments` → `payments`).
 * Setiap pembayaran/reverse ikut memperbarui ringkasan di invoice dan jadwal tagihan (`paid_amount`, `status`, `paid_at`).
 */
class PaymentService
{
    public function __construct(
        private readonly SequenceService $sequences,
        private readonly ActivityLogger $logger,
    ) {}

    /**
     * Pilihan metode untuk layar kasir dan form refund: kategori aktif + sub metode aktifnya.
     * `is_cash` hanya untuk tampilan (kolom uang diterima & kembalian), tidak disimpan.
     *
     * @return array<int, array{id:int, name:string, is_cash:bool, methods:array<int, array{id:int, name:string}>}>
     */
    public function methodOptions(): array
    {
        return CategoryPayment::query()
            ->where('status', true)
            ->with(['payment' => fn ($query) => $query->where('status', true)->orderBy('name')])
            ->orderBy('id')
            ->get()
            ->map(fn (CategoryPayment $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'is_cash' => (bool) preg_match('/cash|tunai/i', $category->name),
                'methods' => $category->payment->map(fn ($method) => ['id' => $method->id, 'name' => $method->name])->values()->all(),
            ])
            ->all();
    }

    /**
     * Validasi metode dari master POS. Sub metode wajib dipilih bila kategorinya punya sub metode aktif (mis. EDC → QRIS).
     *
     * @return array{category: CategoryPayment, method: PaymentMethod|null, name: string}
     */
    public function resolveMethod(mixed $categoryId, mixed $paymentId, string $field = 'payment'): array
    {
        $category = filled($categoryId) ? CategoryPayment::query()->where('status', true)->find($categoryId) : null;
        if (! $category) {
            throw ValidationException::withMessages([$field.'.category_payment_id' => 'Pilih metode pembayaran.']);
        }

        $methods = PaymentMethod::query()->where('category_payment_id', $category->id)->where('status', true);
        $method = null;
        if (filled($paymentId)) {
            $method = (clone $methods)->find($paymentId);
            if (! $method) {
                throw ValidationException::withMessages([$field.'.payment_id' => 'Jenis pembayaran tidak sesuai dengan metode '.$category->name.'.']);
            }
        } elseif ($methods->exists()) {
            throw ValidationException::withMessages([$field.'.payment_id' => 'Pilih jenis pembayaran '.$category->name.'.']);
        }

        return [
            'category' => $category,
            'method' => $method,
            'name' => $method ? $category->name.' · '.$method->name : $category->name,
        ];
    }

    /**
     * Bayar langsung: lunasi seluruh sisa invoice dengan satu pembayaran. Dipanggil di dalam transaksi pembuatan pesanan.
     *
     * @param  array{category_payment_id?:int|string|null, payment_id?:int|string|null, reference?:string|null, notes?:string|null}  $data
     */
    public function payInFull(User $user, Invoice $invoice, ?int $outletId, array $data): Payment
    {
        return DB::transaction(function () use ($user, $invoice, $outletId, $data) {
            $invoice = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $amount = $invoice->outstandingAmount();
            if ($amount <= 0) {
                throw ValidationException::withMessages(['payment' => 'Invoice ini tidak punya sisa tagihan.']);
            }

            $resolved = $this->resolveMethod($data['category_payment_id'] ?? null, $data['payment_id'] ?? null);
            $paidAt = now();

            $payment = new Payment([
                'b2b_customer_id' => $invoice->b2b_customer_id,
                'outlet_id' => $outletId,
                'category_payment_id' => $resolved['category']->id,
                'payment_id' => $resolved['method']?->id,
                'method_name' => $resolved['name'],
                'amount' => $amount,
                'paid_at' => $paidAt,
                'reference' => filled($data['reference'] ?? null) ? trim($data['reference']) : null,
                'received_by' => $user->id,
                'notes' => $data['notes'] ?? null,
                'status' => Payment::STATUS_POSTED,
            ]);
            $payment->payment_number = $this->sequences->paymentNumber($paidAt);
            $payment->save();

            // Bagi ke jadwal tagihan sesuai urutan; sekarang selalu satu jadwal.
            $remaining = $amount;
            $schedules = $invoice->schedules()->lockForUpdate()->get();
            foreach ($schedules as $schedule) {
                $portion = min($remaining, max(0, $schedule->amount_to_pay - $schedule->paid_amount));
                if ($portion <= 0) {
                    continue;
                }
                $payment->allocations()->create([
                    'b2b_invoice_id' => $invoice->id,
                    'b2b_invoice_schedule_id' => $schedule->id,
                    'amount' => $portion,
                ]);
                $this->applyToSchedule($schedule, $portion, $paidAt);
                $remaining -= $portion;
            }
            if ($remaining > 0) {
                $payment->allocations()->create(['b2b_invoice_id' => $invoice->id, 'amount' => $remaining]);
            }

            $this->applyToInvoice($invoice, $amount, $paidAt);

            $this->logger->log('payment.received', $payment, [
                'invoice_number' => $invoice->invoice_number,
                'amount' => $amount,
                'method' => $resolved['name'],
                'reference' => $payment->reference,
            ], $user->id);

            return $payment->load('allocations');
        });
    }

    /**
     * Batalkan semua pembayaran sebuah invoice dan catat pengembalian dananya.
     * Dipanggil sebelum pesanan yang sudah dibayar dibatalkan; invoice kembali ke sisa tagihan penuh.
     *
     * @param  array{category_payment_id?:int|string|null, payment_id?:int|string|null, reference?:string|null}  $refund
     * @return Collection<int, Refund>
     */
    public function reverseForInvoice(User $user, Invoice $invoice, ?int $outletId, array $refund, string $reason): Collection
    {
        return DB::transaction(function () use ($user, $invoice, $outletId, $refund, $reason) {
            $invoice = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $resolved = $this->resolveMethod($refund['category_payment_id'] ?? null, $refund['payment_id'] ?? null, 'refund');

            $payments = Payment::query()
                ->where('status', Payment::STATUS_POSTED)
                ->whereHas('allocations', fn ($q) => $q->where('b2b_invoice_id', $invoice->id))
                ->with('allocations')
                ->lockForUpdate()
                ->get();

            $recorded = (int) $payments->sum(fn (Payment $p) => $p->allocations->where('b2b_invoice_id', $invoice->id)->sum('amount'));
            if ($payments->isEmpty() || $recorded !== $invoice->paid_amount) {
                throw ValidationException::withMessages([
                    'refund' => 'Pembayaran invoice ini tidak tercatat lengkap di sistem, jadi refund tidak bisa dibuat otomatis.',
                ]);
            }

            $now = now();
            $refunds = collect();
            foreach ($payments as $payment) {
                if ($payment->allocations->contains(fn (PaymentAllocation $a) => $a->b2b_invoice_id !== $invoice->id)) {
                    throw ValidationException::withMessages([
                        'refund' => 'Pembayaran '.$payment->payment_number.' juga dipakai untuk invoice lain, jadi tidak bisa dibatalkan dari sini.',
                    ]);
                }

                foreach ($payment->allocations as $allocation) {
                    if ($allocation->b2b_invoice_schedule_id) {
                        $schedule = InvoiceSchedule::query()->whereKey($allocation->b2b_invoice_schedule_id)->lockForUpdate()->first();
                        if ($schedule) {
                            $this->applyToSchedule($schedule, -$allocation->amount, $now);
                        }
                    }
                }

                $payment->forceFill([
                    'status' => Payment::STATUS_REVERSED,
                    'reversed_by' => $user->id,
                    'reversed_at' => $now,
                    'reverse_reason' => $reason,
                ])->save();

                $refunds->push(Refund::create([
                    'b2b_payment_id' => $payment->id,
                    'b2b_invoice_id' => $invoice->id,
                    'outlet_id' => $outletId,
                    'category_payment_id' => $resolved['category']->id,
                    'payment_id' => $resolved['method']?->id,
                    'method_name' => $resolved['name'],
                    'amount' => $payment->amount,
                    'reference' => filled($refund['reference'] ?? null) ? trim($refund['reference']) : null,
                    'reason' => $reason,
                    'refunded_by' => $user->id,
                    'refunded_at' => $now,
                ]));

                $this->logger->log('payment.reversed', $payment, [
                    'invoice_number' => $invoice->invoice_number,
                    'amount' => $payment->amount,
                    'refund_method' => $resolved['name'],
                    'reason' => $reason,
                ], $user->id);
            }

            $this->applyToInvoice($invoice, -$recorded, $now);

            return $refunds;
        });
    }

    private function applyToInvoice(Invoice $invoice, int $delta, $at): void
    {
        $paid = max(0, $invoice->paid_amount + $delta);
        $invoice->forceFill([
            'paid_amount' => $paid,
            'status' => $this->statusFor($paid, $invoice->total_to_pay),
            'paid_at' => $paid >= $invoice->total_to_pay && $paid > 0 ? $at : null,
        ])->save();
    }

    private function applyToSchedule(InvoiceSchedule $schedule, int $delta, $at): void
    {
        $paid = max(0, $schedule->paid_amount + $delta);
        $schedule->forceFill([
            'paid_amount' => $paid,
            'status' => $this->statusFor($paid, $schedule->amount_to_pay),
            'paid_at' => $paid >= $schedule->amount_to_pay && $paid > 0 ? $at : null,
        ])->save();
    }

    private function statusFor(int $paid, int $total): string
    {
        return match (true) {
            $paid <= 0 => Invoice::STATUS_UNPAID,
            $paid >= $total => Invoice::STATUS_PAID,
            default => Invoice::STATUS_PARTIAL,
        };
    }
}
