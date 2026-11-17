<?php

namespace App\Http\Controllers\B2b;

use App\Http\Controllers\Controller;
use App\Http\Requests\B2b\CashierOrderRequest;
use App\Models\B2b\Customer;
use App\Models\B2b\Invoice;
use App\Models\Outlets;
use App\Models\VariantProduct;
use App\Services\B2b\B2bSettings;
use App\Services\B2b\OrderService;
use App\Services\B2b\PricingService;
use Illuminate\Http\Request;

class CashierController extends Controller
{
    public function __construct(
        private readonly PricingService $pricing,
        private readonly OrderService $orders,
        private readonly B2bSettings $settings,
    ) {
        $this->abilities = [];
    }

    public function index(Request $request)
    {
        $this->authorize('read b2b/cashier');

        return view('layouts.b2b.cashier.index', [
            'outlets' => Outlets::query()->whereIn('id', $request->user()->outletIds())->orderBy('name')->get(['id', 'name']),
            'defaultTermDays' => $this->settings->defaultPaymentTermDays(),
            'maxDueDays' => B2bSettings::MAX_DUE_DAYS,
        ]);
    }

    /** Pencarian customer aktif untuk select2. */
    public function customers(Request $request)
    {
        $this->authorize('read b2b/cashier');
        $q = addcslashes(trim((string) $request->query('q')), '%_\\');

        $data = Customer::query()
            ->active()
            ->when($q !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('name', 'like', "%{$q}%")
                ->orWhere('code', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%")))
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'code', 'name'])
            ->map(fn ($customer) => ['id' => $customer->id, 'text' => $customer->name.' · '.$customer->code]);

        return responseSuccess(false, false, $data->all());
    }

    /** Ringkasan customer untuk panel kasir: kontrak berlaku, alamat, dan piutang berjalan. */
    public function customer(Customer $customer)
    {
        $this->authorize('read b2b/cashier');
        abort_unless($customer->is_active, 404);

        $contract = $customer->effectiveContract();
        $openInvoices = $customer->invoices()->whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL, Invoice::STATUS_OVERDUE]);

        return responseSuccess(false, false, [
            'id' => $customer->id,
            'code' => $customer->code,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'contract' => $contract ? [
                'number' => $contract->contract_number,
                'end_date' => $contract->end_date->translatedFormat('d M Y'),
                'rules_count' => $contract->rules()->count(),
                'url' => route('b2b/contracts/show', $contract->id),
            ] : null,
            'address' => $customer->hasAddress() ? $customer->fullAddress() : null,
            'receivables' => [
                'open_invoices' => (clone $openInvoices)->count(),
                'outstanding' => (int) (clone $openInvoices)->selectRaw('COALESCE(SUM(total_to_pay - paid_amount), 0) as total')->value('total'),
                'overdue_invoices' => (clone $openInvoices)->whereDate('due_date', '<', today())->count(),
            ],
        ]);
    }

    /** Semua varian produk aktif di outlet; disaring di sisi browser supaya responsif di tablet. */
    public function products(Request $request)
    {
        $this->authorize('read b2b/cashier');
        $validated = $request->validate(['outlet_id' => ['required', 'integer']]);
        abort_unless(in_array((int) $validated['outlet_id'], $request->user()->outletIds(), true), 403);

        $data = VariantProduct::query()
            ->select('variant_products.*')
            ->join('products', 'products.id', '=', 'variant_products.product_id')
            ->whereNull('products.deleted_at')
            ->where('products.outlet_id', $validated['outlet_id'])
            ->where('products.status', true)
            ->with('product.category:id,name')
            ->orderBy('products.name')
            ->orderBy('variant_products.name')
            ->limit(2000)
            ->get()
            ->map(fn (VariantProduct $variant) => [
                'id' => $variant->id,
                'product_id' => $variant->product_id,
                'name' => CatalogSelectorController::variantLabel($variant),
                'category' => $variant->product->category?->name,
                'price' => (int) $variant->harga,
                'stock' => (int) $variant->stok,
            ]);

        return responseSuccess(false, false, $data->values()->all());
    }

    /** Preview harga dari server (aturan kontrak). Layar kasir tidak pernah menghitung diskon sendiri. */
    public function quote(CashierOrderRequest $request)
    {
        $customer = Customer::query()->active()->findOrFail($request->integer('b2b_customer_id'));
        $quote = $this->pricing->quote($customer, $request->integer('outlet_id'), $request->validated('items'));

        return responseSuccess(false, false, [
            'contract' => $quote['contract'] ? ['id' => $quote['contract']->id, 'number' => $quote['contract']->contract_number] : null,
            'items' => array_map(fn ($item) => [
                'variant_id' => $item['variant_id'],
                'qty' => $item['qty'],
                'base_price' => $item['base_price'],
                'discount_label' => $item['discount_label'],
                'discount_per_unit' => $item['discount_per_unit'],
                'final_price' => $item['final_price'],
                'line_total' => $item['line_total'],
                'stock' => $item['stock'],
                'stock_insufficient' => $item['stock_insufficient'],
            ], $quote['items']),
            'subtotal' => $quote['subtotal'],
            'discount_total' => $quote['discount_total'],
            'grand_total' => $quote['grand_total'],
            'has_insufficient_stock' => $quote['has_insufficient_stock'],
        ]);
    }

    public function store(CashierOrderRequest $request)
    {
        $order = $this->orders->place($request->user(), $request->validated());

        return responseSuccess(false, 'Pesanan '.$order->order_number.' tersimpan. Invoice '.$order->invoice->invoice_number.' dibuat.', [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'redirect' => route('b2b/orders/show', $order->id),
            'invoice_url' => route('b2b/invoices/print', $order->invoice->id),
        ]);
    }
}
