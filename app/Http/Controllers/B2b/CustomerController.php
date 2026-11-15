<?php

namespace App\Http\Controllers\B2b;

use App\DataTables\B2b\CustomerDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\B2b\CustomerRequest;
use App\Models\B2b\Customer;
use App\Models\B2b\Invoice;
use App\Services\B2b\CustomerService;

class CustomerController extends Controller
{
    public function __construct(private readonly CustomerService $customers)
    {
        // Otorisasi ditulis eksplisit di setiap aksi.
        $this->abilities = [];
    }

    public function index(CustomerDataTable $dataTable)
    {
        $this->authorize('read b2b/customers');

        return $dataTable->render('layouts.b2b.customers.index');
    }

    public function create()
    {
        $this->authorize('create b2b/customers');

        return view('layouts.b2b.customers.form', [
            'customer' => new Customer(['is_active' => true, 'reminder_enabled' => false]),
        ]);
    }

    public function store(CustomerRequest $request)
    {
        $customer = $this->customers->create($request->validated());

        return responseSuccess(false, 'Customer '.$customer->name.' berhasil dibuat', [
            'id' => $customer->id,
            'redirect' => route('b2b/customers/show', $customer->id),
        ]);
    }

    public function show(Customer $customer)
    {
        $this->authorize('read b2b/customers');
        $customer->load(['contacts', 'addresses', 'creator', 'modifier']);

        $contracts = $customer->contracts()->withCount('rules')->latest('start_date')->get();
        $recentOrders = $customer->orders()->with(['outlet', 'invoice'])->latest('order_date')->limit(10)->get();
        $openInvoices = $customer->invoices()->whereIn('status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIAL, Invoice::STATUS_OVERDUE]);

        return view('layouts.b2b.customers.show', [
            'customer' => $customer,
            'contracts' => $contracts,
            'effectiveContract' => $contracts->first(fn ($contract) => $contract->isEffective()),
            'recentOrders' => $recentOrders,
            'stats' => [
                'orders' => $customer->orders()->where('status', '!=', 'cancelled')->count(),
                'open_invoices' => (clone $openInvoices)->count(),
                'outstanding' => (int) (clone $openInvoices)->selectRaw('COALESCE(SUM(total_to_pay - paid_amount), 0) as total')->value('total'),
                'overdue' => (clone $openInvoices)->whereDate('due_date', '<', today())->count(),
            ],
        ]);
    }

    public function edit(Customer $customer)
    {
        $this->authorize('update b2b/customers');

        return view('layouts.b2b.customers.form', ['customer' => $customer->load(['contacts', 'addresses'])]);
    }

    public function update(CustomerRequest $request, Customer $customer)
    {
        $this->customers->update($customer, $request->validated());

        return responseSuccess(true, 'Customer berhasil diperbarui', [
            'id' => $customer->id,
            'redirect' => route('b2b/customers/show', $customer->id),
        ]);
    }

    public function toggle(Customer $customer)
    {
        $this->authorize('update b2b/customers');
        $this->customers->toggle($customer);

        return responseSuccess(true, $customer->is_active ? 'Customer diaktifkan' : 'Customer dinonaktifkan');
    }

    public function destroy(Customer $customer)
    {
        $this->authorize('delete b2b/customers');
        $this->customers->delete($customer);

        return responseSuccess(true, 'Customer dihapus', ['redirect' => route('b2b/customers')]);
    }
}
