<?php

namespace App\Http\Controllers\OrderTable;

use App\DataTables\OrderTable\PaymentMethodDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\OrderTable\StorePaymentMethodRequest;
use App\Http\Requests\OrderTable\UpdatePaymentMethodRequest;
use App\Models\OrderTable\PaymentMethod;
use App\Models\Outlets;
use App\Services\OrderTable\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentMethodController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
        $this->abilities = [];
    }

    public function index(PaymentMethodDataTable $dataTable)
    {
        $this->authorize('read order-table/payment-methods');

        return $dataTable->render('layouts.order-table.payment-methods.index', [
            'outlets' => $this->outlets(),
        ]);
    }

    public function create()
    {
        $this->authorize('create order-table/payment-methods');

        return view('layouts.order-table.payment-methods.create', [
            'paymentMethod' => new PaymentMethod,
            'outlets' => $this->outlets(),
        ]);
    }

    public function store(StorePaymentMethodRequest $request)
    {
        $method = DB::transaction(function () use ($request) {
            $method = PaymentMethod::create($this->mappedPayload($request->validated()));
            $this->auditLogger->log('payment-method.created', $method, null, $method->attributesToArray());

            return $method;
        });

        return responseSuccess(false, false, ['id' => $method->id]);
    }

    public function edit(int $paymentMethod)
    {
        $this->authorize('update order-table/payment-methods');

        return view('layouts.order-table.payment-methods.edit', [
            'paymentMethod' => $this->method($paymentMethod),
            'outlets' => $this->outlets(),
        ]);
    }

    public function update(UpdatePaymentMethodRequest $request, int $paymentMethod)
    {
        $method = $this->method($paymentMethod);

        DB::transaction(function () use ($request, $method) {
            $before = $method->attributesToArray();
            $method->fill($this->mappedPayload($request->validated()));
            $method->save();

            $this->auditLogger->log('payment-method.updated', $method, $before, $method->fresh()->attributesToArray());
        });

        return responseSuccess(true);
    }

    public function toggle(Request $request, int $paymentMethod)
    {
        $this->authorize('update order-table/payment-methods');
        $method = $this->method($paymentMethod);

        if (! $method->enabled
            && $method->code === 'pay_at_cashier'
            && ! config('order-table.pay_at_cashier_enabled')) {
            throw ValidationException::withMessages([
                'enabled' => 'Bayar di kasir belum diizinkan untuk diaktifkan pada environment ini.',
            ]);
        }

        DB::transaction(function () use ($method) {
            $before = $method->attributesToArray();
            $method->enabled = ! $method->enabled;
            $method->save();

            $this->auditLogger->log(
                $method->enabled ? 'payment-method.enabled' : 'payment-method.disabled',
                $method,
                $before,
                $method->fresh()->attributesToArray(),
            );
        });

        return responseSuccess(true, $method->enabled ? 'Metode pembayaran diaktifkan' : 'Metode pembayaran dinonaktifkan');
    }

    private function method(int $id): PaymentMethod
    {
        return PaymentMethod::query()
            ->whereKey($id)
            ->whereIn('outlet_id', request()->user()->outletIds())
            ->firstOrFail();
    }

    private function outlets()
    {
        return Outlets::query()
            ->whereIn('id', request()->user()->outletIds())
            ->orderBy('name')
            ->get();
    }

    private function mappedPayload(array $validated): array
    {
        return array_merge($validated, $validated['code'] === 'qris' ? [
            'payment_id' => 2,
            'category_payment_id' => 2,
            'nama_tipe_pembayaran' => 'QRIS',
            'payment_due_minutes' => null,
        ] : [
            'payment_id' => null,
            'category_payment_id' => 1,
            'nama_tipe_pembayaran' => 'Cash',
            'qris_expiry_minutes' => null,
        ]);
    }
}
