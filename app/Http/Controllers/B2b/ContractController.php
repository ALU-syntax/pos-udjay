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

        $today = today()->toDateString();
        $active = fn () => Contract::query()->where('status', Contract::STATUS_ACTIVE);

        return $dataTable->render('layouts.b2b.contracts.index', [
            'stats' => [
                'running' => $active()->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->count(),
                'ending_soon' => $active()->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)
                    ->whereDate('end_date', '<=', today()->addDays(30)->toDateString())->count(),
                'scheduled' => $active()->whereDate('start_date', '>', $today)->count(),
                'customers' => $active()->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->distinct()->count('b2b_customer_id'),
            ],
        ]);
    }

    /** ?customer_id= untuk memilih customer, ?copy_from= untuk menyalin harga dari kontrak lama (perpanjangan). */
    public function create(Request $request)
    {
        $this->authorize('create b2b/contracts');

        $contract = new Contract([
            'start_date' => today(),
            'end_date' => today()->addYear()->subDay(),
        ]);
        $contract->b2b_customer_id = $request->integer('customer_id') ?: null;
        $contract->setRelation('items', collect());
        $copiedFrom = null;

        if ($request->filled('copy_from')) {
            $copiedFrom = Contract::with('items')->findOrFail($request->integer('copy_from'));
            $length = $copiedFrom->start_date->diffInDays($copiedFrom->end_date);
            $start = $copiedFrom->end_date->copy()->addDay()->max(today());
            $contract->fill([
                'start_date' => $start,
                'end_date' => $start->copy()->addDays($length),
                'notes' => $copiedFrom->notes,
            ]);
            $contract->b2b_customer_id = $copiedFrom->b2b_customer_id;
            $contract->setRelation('items', $copiedFrom->items->map(fn ($item) => $item->replicate(['b2b_contract_id'])));
        }

        return $this->form($contract, $copiedFrom);
    }

    public function store(ContractRequest $request)
    {
        $contract = $this->contracts->create($request->contractData(), $request->file('attachment'));

        return responseSuccess(false, 'Kontrak '.$contract->contract_number.' berhasil dibuat', [
            'id' => $contract->id,
            'redirect' => route('b2b/contracts/show', $contract->id),
        ]);
    }

    public function show(Contract $contract)
    {
        $this->authorize('read b2b/contracts');
        $contract->load(['customer', 'items.variant.product.outlet', 'creator', 'modifier', 'terminator']);

        return view('layouts.b2b.contracts.show', [
            'contract' => $contract,
            'orderCount' => $contract->customer->orders()->where('b2b_contract_id', $contract->id)->count(),
        ]);
    }

    public function edit(Contract $contract)
    {
        $this->authorize('update b2b/contracts');
        abort_if($contract->isTerminated(), 403, 'Kontrak yang sudah dihentikan tidak bisa diubah.');

        return $this->form($contract);
    }

    public function update(ContractRequest $request, Contract $contract)
    {
        $this->contracts->update($contract, $request->contractData(), $request->file('attachment'), $request->boolean('remove_attachment'));

        return responseSuccess(true, 'Kontrak berhasil diperbarui', [
            'id' => $contract->id,
            'redirect' => route('b2b/contracts/show', $contract->id),
        ]);
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

        return responseSuccess(true, 'Kontrak dihapus', ['redirect' => route('b2b/contracts')]);
    }

    public function attachment(Contract $contract)
    {
        $this->authorize('read b2b/contracts');
        abort_unless($contract->attachment_path && Storage::disk(ContractService::ATTACHMENT_DISK)->exists($contract->attachment_path), 404);

        return Storage::disk(ContractService::ATTACHMENT_DISK)->download($contract->attachment_path, $contract->attachment_name);
    }

    private function form(Contract $contract, ?Contract $copiedFrom = null)
    {
        $contract->loadMissing('items.variant.product.category');

        // Data awal tabel pilih produk: satu entri per varian, tingkat qty di dalamnya.
        $initialItems = $contract->items->groupBy('variant_id')->map(function ($tiers) {
            $variant = $tiers->first()->variant;
            $product = $variant?->product;

            return [
                'id' => (int) $tiers->first()->variant_id,
                'outlet_id' => $product?->outlet_id,
                'product' => $product?->name ?? 'Varian #'.$tiers->first()->variant_id,
                'variant' => $variant && $product && $variant->name !== $product->name ? $variant->name : null,
                'category' => $product?->category?->name,
                'price' => $variant ? (int) $variant->harga : null,
                'active' => (bool) ($product?->status ?? false),
                'deleted' => ! $variant || $variant->trashed() || ! $product,
                'tiers' => $tiers->sortBy('min_qty')->map(fn ($tier) => [
                    'id' => $tier->id, 'min_qty' => $tier->min_qty, 'price' => $tier->price,
                ])->values()->all(),
            ];
        })->values();

        return view('layouts.b2b.contracts.form', [
            'contract' => $contract,
            'copiedFrom' => $copiedFrom,
            'initialItems' => $initialItems,
            // Customer nonaktif tetap muncul kalau sudah terpilih di kontrak ini.
            'customers' => Customer::query()
                ->where(fn ($query) => $query->where('is_active', true)->orWhere('id', $contract->b2b_customer_id))
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
            'outlets' => Outlets::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
