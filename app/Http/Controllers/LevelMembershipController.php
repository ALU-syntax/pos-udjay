<?php

namespace App\Http\Controllers;

use App\DataTables\LevelMembershipDataTable;
use App\Http\Requests\LevelMembershipRequest;
use App\Models\Category;
use App\Models\LevelMembership;
use App\Models\Outlets;
use App\Models\Product;
use App\Models\ProductBirthdayReward;
use App\Models\RewardMembership;
use App\Models\VariantProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LevelMembershipController extends Controller
{
    public function index(LevelMembershipDataTable $datatable)
    {
        $birthdayReward = ProductBirthdayReward::with('product')->first();
        $excludeName = optional($birthdayReward?->product)->name; // aman kalau null

        $productReward = Product::whereHas('category', function ($q) {
                $q->where('reward_categories', 1);
            })
            ->select('name', 'category_id')
            ->when($excludeName, fn ($q) => $q->where('name', '!=', $excludeName))
            ->distinct()
            ->get();


        return $datatable->render("layouts.level_membership.index", [
            'product_rewards' => $productReward,
            'birthday_reward_choose' => $birthdayReward
        ]);
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

    public function updateBirthdayReward(Request $request)
    {
        $data = $request->validate([
            'name_birthday_reward' => ['required', 'string', 'max:255'],
            'category_id'          => ['required', 'integer'], // kalau ada tabel kategori: tambahkan exists:categories,id
            'desc_birthday_reward'  => ['nullable', 'string', 'max:1000'],
        ]);

        $name = $data['name_birthday_reward'];
        $categoryId = (int) $data['category_id'];
        $desc = $data['desc_birthday_reward'] ?? '';

        DB::transaction(function () use ($desc, $name, $categoryId) {
            // chunk biar aman kalau outlet banyak
            Outlets::query()->select('id')->chunkById(200, function ($outlets) use ($desc, $name, $categoryId) {
                foreach ($outlets as $outlet) {
                        $product = $this->findOrCreateRewardProduct(
                            outletId: $outlet->id,
                            categoryId: $categoryId,
                            name: $name,
                            desc: $desc
                        );

                    // Asumsi 1 record reward per outlet (lebih masuk akal)
                    ProductBirthdayReward::updateOrCreate(
                        ['outlet_id' => $outlet->id],
                        [
                            'product_id'   => $product->id,
                            'product_name' => $name,
                        ]
                    );
                }
            });
        });

        return responseSuccess(true, "Birthday reward berhasil diperbarui");
    }

    private function findOrCreateRewardProduct(int $outletId, int $categoryId, string $name, string $desc): Product
    {
        // WAJIB pakai outlet_id biar tidak nyasar ambil product outlet lain
        $product = Product::query()
            ->where('outlet_id', $outletId)
            ->where('category_id', $categoryId)
            ->where('name', $name)   // lebih aman daripada LIKE
            ->first();

        if (! $product) {
            $product = Product::create([
                'name'        => $name,
                'category_id' => $categoryId,
                'harga_modal' => 0,
                'outlet_id'   => $outletId,
                'status'      => true,
                'description' => $desc,
                'exclude_tax' => false,
            ]);

            VariantProduct::create([
                'name' => $name,
                'harga' => 0,
                'stok' => 1000000,
                'product_id' => $product->id, // Gunakan ID langsung dari instance ModifierGroup
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }else{
            if($product->description !== $desc){
                $product->update([
                    'description' => $desc
                ]);
            }
        }

        return $product;
    }
}
