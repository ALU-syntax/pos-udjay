<?php

namespace App\DataTables;

use App\Models\BirthdayRewardClaims;
use App\Models\Customer;
use App\Models\ExpRewardClaims;
use App\Models\ProductBirthdayReward;
use App\Models\ProductExpReward;
use App\Models\RewardConfirmation;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Yajra\DataTables\CollectionDataTable;
use Yajra\DataTables\Facades\DataTables;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class CustomerRewardDataTable extends DataTable
{
    public function dataTable(): CollectionDataTable
    {
        return DataTables::of($this->rewardRows())
            ->editColumn('reward_name', function ($row) {
                return '<div class="reward-name-cell">'
                    . '<span class="reward-name">' . e($row['reward_name']) . '</span>'
                    . '<span class="reward-description">' . e($row['reward_description']) . '</span>'
                    . '</div>';
            })
            ->editColumn('type', function ($row) {
                return '<span class="reward-type-badge ' . e($row['type_class']) . '">' . e($row['type']) . '</span>';
            })
            ->editColumn('redeemed_at', function ($row) {
                if (!$row['redeemed_at']) {
                    return '<div class="reward-redeemed-cell">'
                        . '<span>Belum pernah diambil</span>'
                        . '<small>-</small>'
                        . '</div>';
                }

                return '<div class="reward-redeemed-cell">'
                    . '<span>' . Carbon::parse($row['redeemed_at'])->format('d M Y') . '</span>'
                    . '<small>' . e($row['redeemed_outlet']) . '</small>'
                    . '</div>';
            })
            ->editColumn('status', function ($row) {
                $statusClass = $row['is_redeemed'] ? 'is-claimed' : 'is-open';

                return '<span class="reward-status-badge ' . $statusClass . '">'
                    . e($row['status'])
                    . '</span>';
            })
            ->rawColumns(['reward_name', 'type', 'redeemed_at', 'status']);
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('customerreward-table')
            ->columns($this->getColumns())
            ->minifiedAjax(route('membership/customer/detailRewards', $this->customerId))
            ->orderBy(2, 'desc')
            ->selectStyleSingle()
            ->parameters([
                'autoWidth' => false,
            ])
            ->buttons([
                Button::make('excel'),
                Button::make('csv'),
                Button::make('pdf'),
                Button::make('print'),
                Button::make('reset'),
                Button::make('reload'),
            ]);
    }

    public function getColumns(): array
    {
        return [
            Column::make('reward_name')->title('Reward'),
            Column::make('type')->title('Type'),
            Column::make('redeemed_at')->title('Redeemed'),
            Column::make('status')->title('Status'),
            Column::make('reward_description')->visible(false),
            Column::make('redeemed_outlet')->visible(false),
        ];
    }

    protected function filename(): string
    {
        return 'CustomerReward_' . date('YmdHis');
    }

    private function rewardRows(): Collection
    {
        $customer = Customer::with(['levelMembership.rewards'])->findOrFail($this->customerId);
        $rows = collect();
        $claimedLevelRewardIds = collect();

        RewardConfirmation::with(['outlet', 'rewardMembership'])
            ->where('customer_id', $customer->id)
            ->orderByDesc('created_at')
            ->get()
            ->each(function ($claim) use ($rows, $claimedLevelRewardIds) {
                $snapshot = json_decode((string) $claim->snapshot, true) ?: [];
                $reward = $claim->rewardMembership;
                $claimedLevelRewardIds->push((int) $claim->reward_memberships_id);

                $rows->push($this->makeRewardRow(
                    'level-claim-' . $claim->id,
                    $reward?->name ?? $snapshot['product_name'] ?? 'Level Reward',
                    $reward?->description,
                    'Level Reward',
                    'type-level',
                    $claim->created_at,
                    $claim->outlet?->name
                ));
            });

        foreach ($customer->levelMembership?->rewards ?? collect() as $reward) {
            if ($claimedLevelRewardIds->contains((int) $reward->id)) {
                continue;
            }

            $rows->push($this->makeRewardRow($reward->id, $reward->name, $reward->description, 'Level Reward', 'type-level', null, null));
        }

        $claimedBirthdayProductIds = collect();

        BirthdayRewardClaims::with(['outlet', 'product'])
            ->where('customer_id', $customer->id)
            ->orderByDesc('created_at')
            ->get()
            ->each(function ($claim) use ($rows, $claimedBirthdayProductIds) {
                $claimedBirthdayProductIds->push((int) $claim->product_id);

                $rows->push($this->makeRewardRow(
                    'birthday-claim-' . $claim->id,
                    $claim->product?->name ?? 'Birthday Reward',
                    $claim->product?->description,
                    'Birthday Reward',
                    'type-birthday',
                    $claim->created_at,
                    $claim->outlet?->name
                ));
            });

        ProductBirthdayReward::with('product')->limit(1)->get()->each(function ($reward) use ($rows, $claimedBirthdayProductIds) {
            if ($claimedBirthdayProductIds->contains((int) $reward->product_id)) {
                return;
            }

            $rows->push($this->makeRewardRow(
                'birthday-' . $reward->id,
                $reward->product?->name ?? $reward->product_name ?? 'Birthday Reward',
                $reward->product?->description,
                'Birthday Reward',
                'type-birthday',
                null,
                null
            ));
        });

        $claimedExpProductIds = collect();

        ExpRewardClaims::with(['outlet', 'product'])
            ->where('customer_id', $customer->id)
            ->orderByDesc('created_at')
            ->get()
            ->each(function ($claim) use ($rows, $claimedExpProductIds) {
                $claimedExpProductIds->push((int) $claim->product_id);

                $rows->push($this->makeRewardRow(
                    'milestone-claim-' . $claim->id,
                    $claim->product?->name ?? 'Milestone Reward',
                    $claim->product?->description,
                    'Milestone Exp',
                    'type-milestone',
                    $claim->created_at,
                    $claim->outlet?->name
                ));
            });

        ProductExpReward::with('product')->limit(1)->get()->each(function ($reward) use ($rows, $claimedExpProductIds) {
            if ($claimedExpProductIds->contains((int) $reward->product_id)) {
                return;
            }

            $rows->push($this->makeRewardRow(
                'milestone-' . $reward->id,
                $reward->product?->name ?? $reward->product_name ?? 'Milestone Reward',
                $reward->product?->description,
                'Milestone Exp',
                'type-milestone',
                null,
                null
            ));
        });

        return $rows->values();
    }

    private function makeRewardRow($id, string $name, ?string $description, string $type, string $typeClass, $redeemedAt, ?string $redeemedOutlet): array
    {
        $isRedeemed = (bool) $redeemedAt;

        return [
            'id' => $id,
            'reward_name' => $name,
            'reward_description' => $description ?: 'Tidak ada deskripsi reward',
            'type' => $type,
            'type_class' => $typeClass,
            'redeemed_at' => $redeemedAt ? Carbon::parse($redeemedAt)->format('Y-m-d H:i:s') : null,
            'redeemed_outlet' => $redeemedOutlet ?: '-',
            'status' => $isRedeemed ? 'Sudah diambil' : 'Belum diambil',
            'is_redeemed' => $isRedeemed,
        ];
    }
}
