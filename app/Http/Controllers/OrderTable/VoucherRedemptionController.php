<?php

namespace App\Http\Controllers\OrderTable;

use App\DataTables\OrderTable\VoucherRedemptionDataTable;
use App\Http\Controllers\Controller;
use App\Models\OrderTable\Voucher;
use App\Models\Outlets;
use App\Services\OrderTable\CatalogSelectorService;
use Illuminate\Database\Eloquent\Builder;

class VoucherRedemptionController extends Controller
{
    public function __construct(private readonly CatalogSelectorService $catalogSelector)
    {
        $this->abilities = [];
    }

    public function index(VoucherRedemptionDataTable $dataTable)
    {
        $this->authorize('read order-table/voucher-redemptions');
        $user = request()->user();

        return $dataTable->render('layouts.order-table.voucher-redemptions.index', [
            'outlets' => Outlets::query()
                ->whereIn('id', $user->outletIds())
                ->orderBy('name')
                ->get(),
            'vouchers' => Voucher::query()
                ->where(function (Builder $query) use ($user) {
                    $query->whereIn('outlet_id', $user->outletIds());
                    if ($this->catalogSelector->canUseGlobal($user)) {
                        $query->orWhereNull('outlet_id');
                    }
                })
                ->orderBy('code')
                ->get(),
        ]);
    }
}
