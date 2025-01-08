<?php

namespace App\Http\Controllers;

use App\DataTables\PromoDatatables;
use App\Models\Category;
use App\Models\Outlets;
use App\Models\Product;
use App\Models\Promo;
use App\Models\SalesType;
use App\Models\VariantProduct;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class PromoController extends Controller
{
    public function index(PromoDatatables $datatables)
    {
        return $datatables->render('layouts.promo.index');
    }

    public function create()
    {
        return view('layouts.promo.promo-modal', [
            'action' => route("library/promo/store"),
            'data' =>  new Promo(),
            "outlets" => Outlets::whereIn('id', json_decode(auth()->user()->outlet_id))->get(),
            'salesTypes' => SalesType::all(),
            // 'products' => Product::whereIn('outlet_id', json_decode(auth()->user()->outlet_id))->get(),
            'categorys' => Category::all()
        ]);
    }

    public function store(Request $request)
    {
        // dd($request);
        $dataValidate = $request->validate([
            'name' => 'required',
            'promo_type' => 'required',
            'outlet_id' => 'required|array',
            'sales_type' => 'required',
            'sales_type_choose' => 'nullable|array',
            'requirement_type' => 'required',
            'qty_requirement_item' => 'nullable|array',
            'item_requirement' => 'nullable|array',
            'condition_purchase_requirement' => 'nullable|array',
            'qty_requirement_category_item' => 'nullable',
            'category_requirement' => 'nullable',
            'variant_item_requirement' => 'nullable|array',
            'amount' => 'nullable',
            'satuan' => 'nullable',
            'apply_multiple' => 'nullable',
            'promo_time_period' => 'nullable',
            'schedule_promo' => 'nullable',
            'start_hour' => 'nullable',
            'end_hour' => 'nullable',
            'day' => 'nullable|array'
        ]);

        foreach ($dataValidate['outlet_id'] as $outlet) {
            $salesType = $dataValidate['sales_type'] == "all_sales_type" ? json_encode([]) : json_encode($dataValidate['sales_type_choose']);
            $requirementType = $dataValidate['requirement_type'] == "specific_item_requirement" ? "any_item" : "any_category";
            $qtyRequirementItem = $dataValidate['qty_requirement_item'];
            $itemRequirement = $dataValidate['item_requirement'];
            $variantItemRequirement = $dataValidate['variant_item_requirement'];
            $conditionPurchaseRequirement = $dataValidate['condition_purchase_requirement'];

            $qtyRequirementCategoryItem = isset($dataValidate['qty_requirement_category_item']);
            $categoryRequirement = isset($dataValidate['category_requirement']);
            $dataProductRequirement = [];
            $dataTmpRequirement = [];

            if ($requirementType == "any_item") {
                foreach ($itemRequirement as $key => $item) {
                    try {
                        $dataProduct = Product::where('name', 'like', '%' . $item . '%')
                            ->where('status', true)
                            ->where('outlet_id', intval($outlet))
                            ->first();

                        if (!$dataProduct) {
                            throw new Exception("Product not found for item: " . $item);
                        }

                        $checkVariant = $variantItemRequirement[$key] == "all" ? 0 : VariantProduct::where('name', 'like', '%' . $variantItemRequirement[$key] . '%')
                            ->where('product_id', $dataProduct->id)
                            ->first();

                        $idVariant = $checkVariant ? $checkVariant->id : 0;

                        if ($key < 1) {
                            array_push($dataTmpRequirement, [$dataProduct->id, $idVariant, $qtyRequirementItem[$key]]);
                        } else {
                            if ($conditionPurchaseRequirement[$key - 1] == "OR") {
                                array_push($dataTmpRequirement, [$dataProduct->id, $idVariant, $qtyRequirementItem[$key]]);
                            } else {
                                array_push($dataProductRequirement, $dataTmpRequirement);
                                $dataTmpRequirement = [];

                                array_push($dataTmpRequirement, [$dataProduct->id, $idVariant, $qtyRequirementItem[$key]]);
                            }
                        }

                        if (count($itemRequirement) == 1 || $key == count($itemRequirement) - 1) {
                            array_push($dataProductRequirement, $dataTmpRequirement);
                        }
                    } catch (Exception $e) {
                        // Tangani kesalahan di sini  
                        // Misalnya, Anda bisa mencatat kesalahan atau mengeluarkan pesan  
                        error_log("Error processing item at key $key: " . $e->getMessage());
                        // Anda bisa juga melanjutkan ke iterasi berikutnya jika perlu  
                        // continue;
                    }
                }
            } else {
                array_push($dataProductRequirement, [$categoryRequirement, $qtyRequirementCategoryItem]);
            }

            $qtyRewardItem = isset($dataValidate['qty_reward_item']);
            $itemReward = isset($dataValidate['item_reward']) ? $dataValidate['item_reward'] : [];
            $conditionPurchaseReward = isset($dataValidate['condition_purchase_reward']);
            $variantItemReward = isset($dataValidate['variant_item_reward']);
            $reward = [];
            $dataTmpReward = [];
            if ($dataValidate['promo_type'] == "discount") {
                array_push($reward, [$dataValidate['satuan'] => formatRupiah(intval($dataValidate['amount']), "Rp. ")]);
            } else {
                foreach ($itemReward as $key => $value) {
                    $dataProduct = Product::where('name', 'like', '%' . $value . '%')->where('status', true)->where('outlet_id', intval($outlet))->first();

                    $checkVariant = $variantItemReward[$key] == "all" ? 0 : VariantProduct::where('name', 'like', '%' . $variantItemReward[$key] . '%')->where('product_id', $dataProduct->id)->first()->pluck('id');
                    $idVariant = $checkVariant ? $checkVariant->id : 0;
                    if ($key < 1) {
                        array_push($dataTmpReward, [$dataProduct->id, $idVariant, $qtyRewardItem[$key]]);
                    } else {
                        if ($conditionPurchaseReward[$key - 1] == "OR") {
                            array_push($dataTmpReward, [$dataProduct->id, $idVariant, $qtyRewardItem[$key]]);
                        } else {
                            array_push($reward, $dataTmpReward);
                            $dataTmpReward = [];

                            array_push($dataTmpReward, [$dataProduct->id, $idVariant, $qtyRewardItem[$key]]);
                        }
                    }

                    if (count($itemReward) == 1 || $key == count($itemReward) - 1) {
                        array_push($reward, $dataTmpReward);
                    }
                }
            }

            $multiple = isset($dataValidate['apply_multiple']) ? true : false;

            $checkStatus = isset($dataValidate['promo_time_period']) ? true : false;

            $dates = explode(" - ", $dataValidate['schedule_promo']);
            if ($checkStatus) {
                $startDate = Carbon::createFromFormat('Y/m/d', $dates[0])->toDateString();
                $endDate = Carbon::createFromFormat('Y/m/d', $dates[1])->toDateString();
                $timeStart = Carbon::createFromFormat('H:i', $dataValidate['start_hour']);
                $timeEnd = Carbon::createFromFormat('H:i', $dataValidate['end_hour']);
                $dayAllowed = $dataValidate['day'];
            } else {
                $startDate = null;
                $endDate = null;
                $timeStart = null;
                $timeEnd = null;
                $dayAllowed = [];
            }

            $data = [
                'name' => $dataValidate['name'],
                'type' => $dataValidate['promo_type'],
                'sales_type' => $salesType,
                'purchase_requirement' => $requirementType,
                'product_requirement' => json_encode($dataProductRequirement),
                'reward' => json_encode($reward),
                'multiple' => $multiple,
                'status' => $checkStatus,
                'promo_date_periode_start' => $startDate,
                'promo_date_periode_end' => $endDate,
                'promo_time_periode_start' => $timeStart,
                'promo_time_periode_end' => $timeEnd,
                'day_allowed' => json_encode($dayAllowed),
                'created_at' => now(),
                'updated_at' => now()
            ];

            $data['outlet_id'] = $outlet;

            // Simpan semua Promo secara bulk
            Promo::insert($data); // Bulk insert lebih efisien
        }
    }
}
