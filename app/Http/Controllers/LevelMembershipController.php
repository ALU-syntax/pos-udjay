<?php

namespace App\Http\Controllers;

use App\DataTables\LevelMembershipDataTable;
use App\Http\Requests\LevelMembershipRequest;
use App\Models\Category;
use App\Models\LevelMembership;
use App\Models\Outlets;
use App\Models\Product;
use App\Models\ProductBirthdayReward;
use App\Models\RewardLevelMembershipProduct;
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

        $productReward = Product::whereHas('category', function ($q) {
                $q->where('reward_categories', 1);
            })
            ->select('name', 'category_id')
            ->distinct()
            ->get();

        return view("layouts.level_membership.level-membership-modal", [
            'data' => new LevelMembership(),
            'product_rewards' => $productReward,
            'action' => route('membership/level-membership/store'),
            'update' => false,
            'title' => 'Tambah Level Membership'
        ]);
    }

    public function store(LevelMembershipRequest $request)
    {
        // dd($request);
        $data = $request->validated();

        // dd($data['without_reward'] ?? false);
        $dataLevelMembership = [
            'name' =>  $data['name'],
            'benchmark' =>  $data['benchmark'],
            'color' => $data['color']
        ];
        $levelMembership = new LevelMembership($dataLevelMembership);
        $levelMembership->save();

        //kalau level tidak mempunyai reward langsung return
        if($data['without_reward'] ?? false){
            return responseSuccess(false);
        }

        for($x=0; $x < count($data['category_product_id']); $x++){
            $categoryProductId = (int) $data['category_product_id'][$x];
            $nameProduct = $data['name_product'][$x];

            $dataRewardMembership = [
                'name' => $nameProduct,
                'icon' => $data['icon'][$x],
                'description' => $data['level-reward-desc'][$x],
                'level_membership_id' => $levelMembership->id,
            ];

            $rewardMembership = RewardMembership::create($dataRewardMembership);

            DB::transaction(function () use ($nameProduct, $categoryProductId, $rewardMembership) {
                // chunk biar aman kalau outlet banyak
                Outlets::query()->select('id')->chunkById(200, function ($outlets) use($nameProduct, $categoryProductId, $rewardMembership) {
                    foreach ($outlets as $outlet) {
                            $product = $this->findOrCreateRewardProduct(
                                outletId: $outlet->id,
                                categoryId: $categoryProductId,
                                name: $nameProduct,
                                desc: ''
                            );

                        // Asumsi 1 record reward per outlet (lebih masuk akal)
                        RewardLevelMembershipProduct::create([
                            'reward_membership_id' => $rewardMembership->id,
                            'product_id' => $product->id,
                            'outlet_id' => $outlet->id,
                        ]);
                    }
                });
            });

        }

        return responseSuccess(false);
    }

    public function edit(LevelMembership $level)
    {
        $level->load(['rewards']);
        $productReward = Product::whereHas('category', function ($q) {
                $q->where('reward_categories', 1);
            })
            ->select('name', 'category_id')
            ->distinct()
            ->get();
        // dd($level);
        return view('layouts.level_membership.level-membership-modal', [
            'data' => $level,
            'product_rewards' => $productReward,
            'action' => route('membership/level-membership/update', $level->id),
            'update' => true,
            'title' => 'Edit Level Membership'
        ]);
    }

    public function update(LevelMembershipRequest $request, LevelMembership $level){
        $level->load(['rewards']);

        // dd($request);

        $data = $request->validated();

        $dataLevelMembership = [
            'name' =>  $data['name'],
            'benchmark' =>  $data['benchmark'],
            'color' => $data['color']
        ];

        $listIdCategoryReward = $data['category_product_id'] ?? [];
        $listNameProductReward = $data['name_product'] ?? [];

        $listIdReward = $data['id_reward_memberships'] ?? [];

        $level->update($dataLevelMembership);

        if($data['without_reward'] ?? false){
            $dataRewardMembership = RewardMembership::where('level_membership_id', $level->id)->first();
            if($dataRewardMembership){
                $dataRewardMembership->rewardProduct()->delete();
                $dataRewardMembership->delete();
            }
            return responseSuccess(true);
        }

        $idRewardExist = array_column($level->rewards->toArray(), 'id');

        $rewardToDelete = array_diff($idRewardExist, $listIdReward);

        foreach ($rewardToDelete as $deleteItem) {
            $dataRewardMembership = RewardMembership::find($deleteItem);
            if($dataRewardMembership){
                $dataRewardMembership->rewardProduct()->delete();
                $dataRewardMembership->delete();
            }
        }

        foreach($listNameProductReward as $key => $value){
            if(isset($listIdReward[$key])){
                $rewardItem = RewardMembership::where('id', $listIdReward[$key])
                ->with('rewardProduct')
                ->first();

                // dd($rewardItem->rewardProduct);
                $dataReward = [
                    'name' => $value,
                    'description' => $data['level-reward-desc'][$key] ?? '',
                    'icon' => $data['icon'][$key],
                ];

                $rewardItem->update($dataReward);

                DB::transaction(function () use ($rewardItem, $listIdCategoryReward, $listNameProductReward, $key) {
                    // chunk biar aman kalau outlet banyak
                    Outlets::query()->select('id')->chunkById(200, function ($outlets) use($rewardItem, $listIdCategoryReward, $listNameProductReward, $key) {
                        foreach ($outlets as $outlet) {
                            $product = $this->findOrCreateRewardProduct(
                                outletId: $outlet->id,
                                categoryId: $listIdCategoryReward[$key],
                                name: $listNameProductReward[$key],
                                desc: ''
                            );

                            $rewardLevelMembershipProduct = RewardLevelMembershipProduct::where('reward_membership_id', $rewardItem->id)
                                ->where('outlet_id', $outlet->id)
                                ->first();

                            if ($rewardLevelMembershipProduct) {
                                // hanya update kalau product berubah
                                if ($rewardLevelMembershipProduct->product_id !== $product->id) {
                                    $rewardLevelMembershipProduct->update([
                                        'product_id' => $product->id
                                    ]);
                                }
                            } else {
                                RewardLevelMembershipProduct::create([
                                    'reward_membership_id' => $rewardItem->id,
                                    'outlet_id' => $outlet->id,
                                    'product_id' => $product->id
                                ]);
                            }
                        }

                    });
                });

            }else{
                $dataReward = [
                    'name' => $value,
                    'level_membership_id' => $level->id,
                    'description' => $data['level-reward-desc'][$key] ?? '',
                    'icon' => $data['icon'][$key],
                ];

                $rewardItem = RewardMembership::create($dataReward);

                DB::transaction(function () use ($rewardItem, $listIdCategoryReward, $listNameProductReward, $key) {
                    // chunk biar aman kalau outlet banyak
                    Outlets::query()->select('id')->chunkById(200, function ($outlets) use($rewardItem, $listIdCategoryReward, $listNameProductReward, $key) {
                        foreach ($outlets as $outlet) {
                            $product = $this->findOrCreateRewardProduct(
                                outletId: $outlet->id,
                                categoryId: $listIdCategoryReward[$key],
                                name: $listNameProductReward[$key],
                                desc: ''
                            );

                            RewardLevelMembershipProduct::create([
                                'reward_membership_id' => $rewardItem->id,
                                'product_id' => $product->id,
                                'outlet_id' => $outlet->id,
                            ]);
                        }

                    });
                });
            }
        }

        return responseSuccess(true);

    }

    public function destroy(LevelMembership $level)
    {
        $level->rewards->each(function ($reward) {
            $reward->delete(); // ini akan trigger deleting() di RewardMembership
        });

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

    public function searchIcons(Request $request)
    {
        $search = strtolower($request->q ?? '');

        $icons = collect(config('fontawesome'))
            ->filter(function ($label, $class) use ($search) {
                return str_contains(strtolower($class), $search)
                    || str_contains(strtolower($label), $search);
            })
            ->map(function ($label, $class) {
                return [
                    'id'   => $class,
                    'text' => $label,
                    'icon' => $class
                ];
            })
            ->values()
            ->take(50); // batasi biar ringan

        return response()->json([
            'results' => $icons
        ]);
    }
}
