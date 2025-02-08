<?php

namespace App\Http\Controllers;

use App\DataTables\LevelMembershipDataTable;
use App\Http\Requests\LevelMembershipRequest;
use App\Models\LevelMembership;
use App\Models\RewardMembership;
use Illuminate\Http\Request;

class LevelMembershipController extends Controller
{
    public function index(LevelMembershipDataTable $datatable)
    {
        return $datatable->render("layouts.level_membership.index");
    }

    public function create()
    {
        return view("layouts.level_membership.level-membership-modal", [
            'data' => new LevelMembership(),
            'action' => route('membership/level-membership/store')
        ]);
    }

    public function store(LevelMembershipRequest $request)
    {
        $data = $request->validated();

        $dataLevelMembership = [
            'name' =>  $data['name'],
            'benchmark' =>  $data['benchmark'],
            'color' => $data['color']
        ];
        $levelMembership = new LevelMembership($dataLevelMembership);
        $levelMembership->save();

        $rewardMembership = [];

        foreach ($data['reward_memberships'] as $reward) {
            $rewardMembership[] = [
                'name' => $reward,
                'level_membership_id' => $levelMembership->id,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        RewardMembership::insert($rewardMembership);

        return responseSuccess(false);
    }

    public function edit(LevelMembership $level)
    {
        $level->load(['rewards']);
        return view('layouts.level_membership.level-membership-modal', [
            'data' => $level,
            'action' => route('membership/level-membership/update', $level->id)
        ]);
    }

    public function update(LevelMembershipRequest $request, LevelMembership $level){
        $level->load(['rewards']);

        $data = $request->validated();

        $dataLevelMembership = [
            'name' =>  $data['name'],
            'benchmark' =>  $data['benchmark'],
            'color' => $data['color']
        ];
        
        $listIdReward = $data['id_reward_memberships'];
        $listNameReward = $data['reward_memberships'];

        $level->update($dataLevelMembership);

        $idRewardExist = array_column($level->rewards->toArray(), 'id');

        $rewardToDelete = array_diff($idRewardExist, $data['id_reward_memberships']);

        foreach ($rewardToDelete as $deleteItem) {
            RewardMembership::find($deleteItem)->delete();
        }

        foreach($listNameReward as $key => $value){
            if(isset($listIdReward[$key])){
                $rewardItem = RewardMembership::find($listIdReward[$key]);
                $dataReward = [
                    'name' => $value
                ];

                $rewardItem->update($dataReward);
            }else{
                $dataReward = [
                    'name' => $value,
                    'level_membership_id' => $level->id
                ];

                RewardMembership::create($dataReward);
            }
        }

        return responseSuccess(true);
        
    }

    public function destroy(LevelMembership $level)
    {
        $level->rewards()->delete();
        $level->delete();


        return responseSuccessDelete();
    }
}
