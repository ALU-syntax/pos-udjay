<?php

namespace App\Http\Controllers\B2b;

use App\DataTables\B2b\ContractDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\B2b\ContractRequest;
use App\Models\B2b\Contract;
use App\Models\B2b\Customer;
use App\Models\Outlets;
use App\Services\B2b\ContractService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ContractController extends Controller
{
    public function __construct(private readonly ContractService $contracts)
    {
        $this->abilities = [];
    }

    public function index(ContractDataTable $dataTable)
    {
        $this->authorize('read b2b/contracts');

        return $dataTable->render('layouts.b2b.contracts.index');
    }

    /** ?customer_id= untuk memilih customer, ?copy_from= untuk menyalin aturan dari kontrak lama (perpanjangan). */
    public function create(Request $request)
    {
        $this->authorize('create b2b/contracts');

        $contract = new Contract([
            'b2b_customer_id' => $request->integer('customer_id') ?: null,
            'start_date' => today(),
            'end_date' => today()->addYear()->subDay(),
        ]);

        if ($request->filled('copy_from')) {
            $source = Contract::with('rules')->findOrFail($request->integer('copy_from'));
            $length = $source->start_date->diffInDays($source->end_date);
            $start = $source->end_date->copy()->addDay()->max(today());
            $contract->fill([
                'b2b_customer_id' => $source->b2b_customer_id,
                'start_date' => $start,
                'end_date' => $start->copy()->addDays($length),
                'notes' => $source->notes,
            ]);
            $contract->setRelation('rules', $source->rules->map(fn ($rule) => $rule->replicate(['b2b_contract_id'])));
        } else {
            $contract->setRelation('rules', collect());
        }

        return $this->form($contract, $request->filled('copy_from') ? Contract::find($request->integer('copy_from')) : null);
    }

    public function store(ContractRequest $request)
    {
        if ($request->boolean('activate')) {
            $this->authorize('activate b2b/contracts');
        }

        $contract = $this->contracts->create($request->contractData(), $request->file('attachment'), $request->boolean('activate'));

        return responseSuccess(false, 'Kontrak '.$contract->contract_number.' berhasil dibuat', [
            'id' => $contract->id,
            'redirect' => route('b2b/contracts/show', $contract->id),
        ]);
    }

    public function show(Contract $contract)
    {
        $this->authorize('read b2b/contracts');
        $contract->load(['customer', 'rules.outlet', 'rules.category', 'rules.product', 'rules.variant.product', 'creator', 'activator', 'terminator']);

        return view('layouts.b2b.contracts.show', [
            'contract' => $contract,
            'orderCount' => $contract->customer->orders()->where('b2b_contract_id', $contract->id)->count(),
        ]);
    }

    public function edit(Contract $contract)
    {
        $this->authorize('update b2b/contracts');
        abort_unless($contract->isDraft(), 403, 'Hanya kontrak draft yang bisa diubah.');

        return $this->form($contract->load('rules'));
    }

    public function update(ContractRequest $request, Contract $contract)
    {
        $this->contracts->update($contract, $request->contractData(), $request->file('attachment'));

        return responseSuccess(true, 'Kontrak berhasil diperbarui', [
            'id' => $contract->id,
            'redirect' => route('b2b/contracts/show', $contract->id),
        ]);
    }

    public function activate(Contract $contract)
    {
        $this->authorize('activate b2b/contracts');
        $this->contracts->activate($contract);

        return responseSuccess(true, 'Kontrak diaktifkan');
    }

    public function terminate(Request $request, Contract $contract)
    {
        $this->authorize('terminate b2b/contracts');
        $validated = $request->validate(['reason' => ['required', 'string', 'max:255']], [], ['reason' => 'alasan']);
        $this->contracts->terminate($contract, $validated['reason']);

        return responseSuccess(true, 'Kontrak dihentikan');
    }

    public function destroy(Contract $contract)
    {
        $this->authorize('delete b2b/contracts');
        $this->contracts->delete($contract);

        return responseSuccess(true, 'Kontrak draft dihapus', ['redirect' => route('b2b/contracts')]);
    }

    public function attachment(Contract $contract)
    {
        $this->authorize('read b2b/contracts');
        abort_unless($contract->attachment_path && Storage::disk(ContractService::ATTACHMENT_DISK)->exists($contract->attachment_path), 404);

        return Storage::disk(ContractService::ATTACHMENT_DISK)->download($contract->attachment_path, $contract->attachment_name);
    }

    private function form(Contract $contract, ?Contract $copiedFrom = null)
    {
        $contract->loadMissing(['rules.category', 'rules.product', 'rules.variant.product', 'rules.outlet']);

        return view('layouts.b2b.contracts.form', [
            'contract' => $contract,
            'copiedFrom' => $copiedFrom,
            // Customer nonaktif tetap muncul kalau sudah terpilih di kontrak ini.
            'customers' => Customer::query()
                ->where(fn ($query) => $query->where('is_active', true)->orWhere('id', $contract->b2b_customer_id))
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
            'outlets' => Outlets::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
