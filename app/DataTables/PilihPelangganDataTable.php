<?php

namespace App\DataTables;

use App\Models\BirthdayRewardClaims;
use App\Models\Customer;
use App\Models\ExpRewardClaims;
use App\Models\PilihPelanggan;
use App\Models\ProductBirthdayReward;
use App\Models\ProductExpReward;
use App\Models\RewardConfirmation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class PilihPelangganDataTable extends DataTable
{
    protected $today;
    protected $outletUser;

    public function __construct()
    {
        $this->today = Carbon::today();
        $userData = auth()->user();
        $dataOutletUser = json_decode($userData->outlet_id);
        $this->outletUser = $dataOutletUser[0];
    }
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('created_at', function($row){
                return Carbon::parse($row->created_at)->diffForHumans();
            })
            ->addColumn('action', function ($row) {
                // Ganti $row->id dan $row->name dengan nama kolom yang sesuai
                // dd($row);
                $newData = $row;
                $displayTanggalLahir = date('d M', strtotime($newData->tanggal_lahir));
                $newData->display_tanggal_lahir = $displayTanggalLahir;

                $newData->can_claim_birthday_reward = false;
                $newData->note_claim_birthday_reward = "";

                $endDate = Carbon::parse($newData->tanggal_lahir);
                $endDateDisplay = $endDate->copy()->addMonths(1);
                $nowAge = $endDate->age;

                $periodClaim = $displayTanggalLahir . " - " .  date('d M', strtotime($endDateDisplay));
                $newData->period_claim = $periodClaim;

                if ($newData->created_at->diffInDays(now()) >= 30){ //check apakah umur akun sudah lebih dari 30 hari
                    if ($this->isBirthdayInRange($newData->tanggal_lahir)) {
                        $newData->can_claim_birthday_reward = true;
                    }
                }

                $rewardBirthday = ProductBirthdayReward::with('product')->where('outlet_id', $this->outletUser)->first();

                $checkClaimBirthdayReward = BirthdayRewardClaims::select('created_at', 'outlet_id')
                    ->with('outlet:id,name')
                    ->where('customer_id', $row->id)
                    ->where('age', $nowAge)
                    ->first()
                    ?->only(['created_at', 'outlet']);

                if($checkClaimBirthdayReward){
                    $checkClaimBirthdayReward['created_at'] = $checkClaimBirthdayReward['created_at']->format('d M Y H:i');
                }



                $rewardExp = ProductExpReward::with('product')->where('outlet_id', $this->outletUser)->first();

                $checkClaimExpReward = null;
                $exp = $newData->exp;

                // kelipatan 5000 terbesar
                $claimableExp = intdiv($exp, 5000) * 5000;

                if($exp >= 5000){
                    $checkClaimExpReward = ExpRewardClaims::select('created_at', 'outlet_id')
                        ->with('outlet:id,name')
                        ->where('customer_id', $newData->id)
                        ->where('exp', $claimableExp)
                        ->where('level_batch', $newData->level_batch)
                        ->first()
                        ?->only(['created_at', 'outlet']);

                    if($checkClaimExpReward){
                        $checkClaimExpReward['created_at'] = $checkClaimExpReward['created_at']->format('d M Y H:i');
                    }
                }


                // $listRewardAccept = RewardConfirmation::where('customer_id', $newData->id)
                // ->where('level_membership_id', $newData->levelMembership->id)
                // ->where('level_batch', $newData->level_batch)
                // ->get();

                $rewardLevel = [];
                $listRewardLevel = $newData->levelMembership->rewards->toArray();


                foreach($listRewardLevel as $index => $dataRewardLevel){
                    $tmpDataReward = $dataRewardLevel;

                    // array_push($tmpDataReward, $dataRewardLevel);
                    foreach($newData->rewardConfirmations->toArray() as $dataRewardConfirmation){
                        if($dataRewardConfirmation['reward_memberships_id'] == $dataRewardLevel['id']
                         && $dataRewardConfirmation['level_batch'] == $newData['level_batch']
                         && $dataRewardConfirmation['customer_id'] == $newData['id']){
                            $formatCreatedAt = Carbon::parse($dataRewardConfirmation['created_at'])->format('d M Y H:i');

                            $dataRewardConfirmation['created_at'] = $formatCreatedAt;
                            $tmpDataReward['reward_confirmation'] = $dataRewardConfirmation;
                        }
                    }
                    array_push($rewardLevel, $tmpDataReward);
                }

                // if($newData->id == 27){
                //     dd($exp, $claimableExp, $checkClaimExpReward);
                //     dd($newData->rewardConfirmations);
                // }

                return view('layouts.kasir.button-pilih-pelanggan', [
                    'data' => $newData,
                    'reward_birthday' => $rewardBirthday,
                    'check_claim_reward_birthday' => $checkClaimBirthdayReward,
                    'reward_level' => $rewardLevel,
                    'reward_exp' => $rewardExp,
                    'check_claim_reward_exp' => $checkClaimExpReward,
                ]);
            })
            // ->rawColumns(['created_at'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Customer $model): QueryBuilder
    {
        return $model->with(['levelMembership' => function($levelMembership){
            $levelMembership->with([
                'rewards' => function($rewards) {
                $rewards->with(['rewardProduct' => function($rewardProduct){
                    $rewardProduct->where('outlet_id', $this->outletUser)->select('id', 'reward_membership_id','product_id');
                }])->select('id', 'level_membership_id', 'name', 'description', 'icon');
            }]);
        }, 'rewardConfirmations' => function($rewardConfirmation){
            $rewardConfirmation->with(['outlet' => function($outlet){
                $outlet->select('id', 'name');
            }])->select('id', 'level_membership_id', 'reward_memberships_id', 'level_batch', 'customer_id', 'outlet_id', 'created_at');
        }, 'community'])->newQuery()->orderBy('name', 'asc');
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('pilihpelanggan-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            //->dom('Bfrtip')
            ->orderBy(1)
            ->selectStyleSingle()
            ->buttons([
                Button::make('excel'),
                Button::make('csv'),
                Button::make('pdf'),
                Button::make('print'),
                Button::make('reset'),
                Button::make('reload')
            ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('name')->orderable(false),
            Column::make('telfon')->orderable(false),
            Column::make('point')->orderable(false),
            Column::make('exp')->orderable(false),
            Column::make('created_at')->orderable(false),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width(60)
                ->addClass('text-center'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'PilihPelanggan_' . date('YmdHis');
    }

    function isBirthdayInRange(string $tanggalLahir): bool
    {
        $birth = Carbon::parse($tanggalLahir);
        // ulang tahun di tahun ini
        $birthdayThisYear = $birth->copy()->year($this->today->year);
        $endDateThisYear = $birthdayThisYear->copy()->addMonths();
        $endDateThisYear->endOfDay();

        $birthdayLastYear = $birthdayThisYear->copy()->subYear();
        $endDateLastYear = $birthdayLastYear->copy()->addMonth();
        $endDateLastYear->endOfDay();

        $canClaim =
                $this->today->between($birthdayThisYear, $endDateThisYear) ||
                $this->today->between($birthdayLastYear, $endDateLastYear);

        return $canClaim;
    }
}
