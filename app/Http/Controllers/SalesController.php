<?php

namespace App\Http\Controllers;

use App\Exports\ItemSalesExport;
use App\Exports\SalesSummaryExport;
use App\Models\Category;
use App\Models\CategoryPayment;
use App\Models\Discount;
use App\Models\ModifierGroup;
use App\Models\Modifiers;
use App\Models\Outlets;
use App\Models\Product;
use App\Models\SalesType;
use App\Models\Taxes;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Models\VariantProduct;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        $dates = explode(' - ', $request->input('date'));
        if (count($dates) == 2) {
            $startDate = Carbon::createFromFormat('Y/m/d', trim($dates[0]))->startOfDay();
            $endDate = Carbon::createFromFormat('Y/m/d', trim($dates[1]))->endOfDay();
        } else {
            // Tetapkan tanggal default jika input 'date' hilang atau tidak valid
            $startDate = Carbon::yesterday()->startOfDay();
            $endDate = Carbon::yesterday()->endOfDay();
        }

        $outlet = $request->input('outlet');

        // $data = ModifierGroup::with(['modifier.itemTransaction'])->where('outlet_id', 1)->get();

        // $data = TransactionItem::whereJsonContains('modifier_id', ['id' => "1"])->get();



        // dd(json_decode($data[0]->variants[0]->itemTransaction[0]->discount_id));
        return view('layouts.sales.index', [
            "outlets" => Outlets::whereIn('id', json_decode(auth()->user()->outlet_id))->get(),
        ]);
    }

    public function getSalesSummary(Request $request)
    {
        $dates = explode(' - ', $request->input('date'));
        if (count($dates) == 2) {
            $startDate = Carbon::createFromFormat('Y/m/d', trim($dates[0]))->startOfDay();
            $endDate = Carbon::createFromFormat('Y/m/d', trim($dates[1]))->endOfDay();
        } else {
            // Tetapkan tanggal default jika input 'date' hilang atau tidak valid
            $startDate = Carbon::now()->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        }

        $outlet = $request->input('outlet');

        if($outlet == "all"){
            $dataTransaction = Transaction::with(['itemTransaction'])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get(); // Ambil data sesuai kebutuhan
        }else{
            $dataTransaction = Transaction::with(['itemTransaction'])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('outlet_id', $outlet)->get(); // Ambil data sesuai kebutuhan
        }

        $grossSales = 0;
        $discount = 0;
        $netSales = 0;
        $tax = 0;
        $rounding = 0;


        foreach ($dataTransaction as $data) {

            $discount += $data->total_diskon;

            $totalTax = 0;
            foreach (json_decode($data->total_pajak) as $itemPajak) {
                $totalTax += $itemPajak->total;
            }
            $grossSales += $data->total + $data->total_diskon - $totalTax;

            $netSales += $data->total - $totalTax;

            $tax += $totalTax;
            $rounding += $data->rounding_amount;
        }

        $totalCollected = $netSales + $tax + $rounding;


        return response()->json([
            'grossSales' => $grossSales,
            'discount' => $discount,
            'netSales' => $netSales,
            'tax' => $tax,
            'rounding' => $rounding,
            'totalCollect' => $totalCollected
        ]);
    }

    public function getPaymentMethodSales(Request $request)
    {
        $dates = explode(' - ', $request->input('date'));
        if (count($dates) == 2) {
            $startDate = Carbon::createFromFormat('Y/m/d', trim($dates[0]))->startOfDay();
            $endDate = Carbon::createFromFormat('Y/m/d', trim($dates[1]))->endOfDay();
        }

        $outlet = $request->input('outlet');

        if($outlet == "all"){
            $data = CategoryPayment::with(['payment' => function ($payment) use ($startDate, $endDate, $outlet) {
                $payment->with(['transactions' => function ($transaction) use ($startDate, $endDate) {
                    $transaction->whereBetween('created_at', [$startDate, $endDate]);
                    // $transaction->whereDate('created_at', Carbon::yesterday())->where('outlet_id', $outlet);
                }]);
            }, 'transactions' => function ($transaction) use ($startDate, $endDate) {
                $transaction->whereBetween('created_at', [$startDate, $endDate]);
                // $transaction->whereDate('created_at', Carbon::yesterday())->where('outlet_id', $outlet);
            }])->get();
        }else{
            $data = CategoryPayment::with(['payment' => function ($payment) use ($startDate, $endDate, $outlet) {
                $payment->with(['transactions' => function ($transaction) use ($startDate, $endDate, $outlet) {
                    $transaction->whereBetween('created_at', [$startDate, $endDate])->where('outlet_id', $outlet);
                    // $transaction->whereDate('created_at', Carbon::yesterday())->where('outlet_id', $outlet);
                }]);
            }, 'transactions' => function ($transaction) use ($startDate, $endDate, $outlet) {
                $transaction->whereBetween('created_at', [$startDate, $endDate])->where('outlet_id', $outlet);
                // $transaction->whereDate('created_at', Carbon::yesterday())->where('outlet_id', $outlet);
            }])->get();

        }


        // Format data untuk dikembalikan
        $result = [];
        foreach ($data as $category) {
            $tmpData = [];
            if ($category->name == "Cash" || $category->id == 1) {
                $tmpData['payment_method'] = $category->name;
                $tmpData['number_of_transactions'] = count($category->transactions);
                $tmpData['parent'] = true;
                $totalCollected = 0;

                foreach ($category->transactions as $transaction) {
                    $totalCollected += $transaction->total;
                }

                $tmpData['total_collected'] = $totalCollected;

                array_push($result, $tmpData);
            } else {
                $tmpData['payment_method'] = $category->name;
                $tmpData['number_of_transactions'] = "";
                $tmpData['total_collected'] = "";
                $tmpData['parent'] = true;

                array_push($result, $tmpData);

                foreach ($category->payment as $payment) {
                    $tmpData['payment_method'] = $payment->name;
                    $tmpData['number_of_transactions'] = count($payment->transactions);
                    $tmpData['parent'] = false;
                    $paymentTotalCollected = 0;

                    foreach ($payment->transactions as $paymentTransaction) {
                        $paymentTotalCollected += $paymentTransaction->total;
                    }
                    $tmpData['total_collected'] = $paymentTotalCollected;

                    array_push($result, $tmpData);
                }
            }
        }

        return response()->json($result);
    }

    public function getGrossProfit(Request $request)
    {
        $dates = explode(' - ', $request->input('date'));
        if (count($dates) == 2) {
            $startDate = Carbon::createFromFormat('Y/m/d', trim($dates[0]))->startOfDay();
            $endDate = Carbon::createFromFormat('Y/m/d', trim($dates[1]))->endOfDay();
        } else {
            // Tetapkan tanggal default jika input 'date' hilang atau tidak valid
            $startDate = Carbon::now()->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        }

        $outlet = $request->input('outlet');

        if($outlet == "all"){
            $dataTransaction = Transaction::with(['itemTransaction'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get(); // Ambil semua data outlet
        }else{
            $dataTransaction = Transaction::with(['itemTransaction'])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('outlet_id', $outlet)->get(); // Ambil data sesuai kebutuhan
        }

        $grossSales = 0;
        $discount = 0;
        $netSales = 0;

        foreach ($dataTransaction as $transaction) {
            $discount += $transaction->total_diskon;

            $totalTax = 0;
            if ($transaction->total_pajak){
                foreach (json_decode($transaction->total_pajak) as $itemPajak) {
                    $totalTax += $itemPajak->total;
                }
            }
            $grossSales += $transaction->total + $transaction->total_diskon - $totalTax;

            $netSales += $transaction->total - $totalTax;
        }

        return response()->json([
            'grossSales' => $grossSales,
            'discount' => $discount,
            'netSales' => $netSales
        ]);
    }

    public function getSalesType(Request $request)
    {
        $dates = explode(' - ', $request->input('date'));
        if (count($dates) == 2) {
            $startDate = Carbon::createFromFormat('Y/m/d', trim($dates[0]))->startOfDay();
            $endDate = Carbon::createFromFormat('Y/m/d', trim($dates[1]))->endOfDay();
        } else {
            // Tetapkan tanggal default jika input 'date' hilang atau tidak valid
            $startDate = Carbon::now()->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        }

        $outlet = $request->input('outlet');

        if($outlet == "all"){
            $query = SalesType::with(['itemTransaction' => function ($transaction) use ($startDate, $endDate) {
                $transaction->with(['variant'])->whereBetween('created_at', [$startDate, $endDate]);
            }])->get();
        }else{
            $query = SalesType::with(['itemTransaction' => function ($transaction) use ($startDate, $endDate) {
                $transaction->with(['variant'])->whereBetween('created_at', [$startDate, $endDate]);
            }])->where('outlet_id', $outlet)->get();
        }

        return DataTables::of($query)
            ->addColumn('sales_type', function ($row) {
                return $row->name;
            })
            ->addColumn('count', function ($row) {
                // return "<span class='badge badge-primary'>{$row->outlet->name}</span>";
                return count($row->itemTransaction);
            })
            ->addColumn('total_collected', function ($row) {
                $totalTransaction = 0;
                foreach ($row->itemTransaction as $data) {
                    $totalTransaction += $data->variant->harga;
                }
                return formatRupiah(strval($totalTransaction), "Rp. ");
            })
            ->setRowId('id')
            ->make(true);
    }

    // getItemSales Default
    // public function getItemSales(Request $request)
    // {
    //     $dates = explode(' - ', $request->input('date'));
    //     if (count($dates) == 2) {
    //         $startDate = Carbon::createFromFormat('Y/m/d', trim($dates[0]))->startOfDay();
    //         $endDate = Carbon::createFromFormat('Y/m/d', trim($dates[1]))->endOfDay();
    //     } else {
    //         // Tetapkan tanggal default jika input 'date' hilang atau tidak valid
    //         $startDate = Carbon::now()->startOfDay();
    //         $endDate = Carbon::now()->endOfDay();
    //     }

    //     $outlet = $request->input('outlet');

    //     if($outlet == "all"){
    //         $data = VariantProduct::with(['itemTransaction' => function ($transaction) use ($startDate, $endDate) {
    //             $transaction->whereBetween('created_at', [$startDate, $endDate]);
    //         }, 'product.category' => function($category){
    //             $category->withTrashed();
    //         }, 'product.outlet'])->get();
    //     }else{
    //         $data = VariantProduct::with(['itemTransaction' => function ($transaction) use ($startDate, $endDate) {
    //             $transaction->whereBetween('created_at', [$startDate, $endDate]);
    //         }, 'product.category' => function($category){
    //             $category->withTrashed();
    //         }, 'product.outlet'])->whereHas('product', function ($query) use ($outlet) {
    //             $query->where('outlet_id', $outlet);
    //         })->get();
    //     }

    //     return DataTables::of($data)
    //         ->addColumn('name', function ($row) use($outlet) {
    //             // $namaVariant
    //             if($outlet == "all"){
    //                 return ($row->name == $row->product->name) ? $row->product->name . " (" . $row->product->outlet->name . ")" : $row->product->name . ' - ' . $row->name . " (" . $row->product->outlet->name . ")";
    //             }else{
    //                 return ($row->name == $row->product->name) ? $row->product->name : $row->product->name . ' - ' . $row->name;
    //             }
    //         })
    //         ->addColumn('category', function ($row) {
    //             return $row->product->category->name;
    //         })
    //         ->addColumn('item_sold', function ($row) {
    //             $itemSold = count($row->itemTransaction);
    //             return $itemSold;
    //         })
    //         ->addColumn('gross_sales', function ($row) {
    //             $itemSold = count($row->itemTransaction);
    //             $grossSales = $itemSold * $row->harga;
    //             return $grossSales == 0 ? "Rp. 0" : formatRupiah(strval($grossSales), "Rp. ");
    //         })
    //         ->addColumn('discounts', function ($row) {
    //             $totalDiscount = 0;
    //             foreach ($row->itemTransaction as $itemTransaction) {
    //                 $dataDiscount = json_decode($itemTransaction->discount_id);
    //                 foreach ($dataDiscount as $discount) {
    //                     $totalDiscount += $discount->result;
    //                 }
    //             }
    //             return $totalDiscount == 0 ? "Rp. 0" : formatRupiah(strval($totalDiscount), "Rp. ");
    //         })
    //         ->addColumn('net_sales', function ($row) {
    //             $totalDiscount = 0;
    //             $jumlahTransaksi = count($row->itemTransaction);
    //             $grossSales = $jumlahTransaksi * $row->harga;
    //             foreach ($row->itemTransaction as $itemTransaction) {
    //                 $dataDiscount = json_decode($itemTransaction->discount_id);

    //                 foreach ($dataDiscount as $discount) {
    //                     $totalDiscount += $discount->result;
    //                 }
    //             }

    //             $netSales = $grossSales -= $totalDiscount;
    //             return $netSales == 0 ? "Rp. 0" : formatRupiah(strval($netSales), "Rp. ");
    //         })
    //         ->addColumn('gross_profit', function ($row) {
    //             $totalDiscount = 0;
    //             $jumlahTransaksi = count($row->itemTransaction);
    //             $grossSales = $jumlahTransaksi * $row->harga;
    //             foreach ($row->itemTransaction as $itemTransaction) {
    //                 $dataDiscount = json_decode($itemTransaction->discount_id);

    //                 foreach ($dataDiscount as $discount) {
    //                     $totalDiscount += $discount->result;
    //                 }
    //             }

    //             $grossProfit = $grossSales -= $totalDiscount;
    //             return $grossProfit == 0 ? "Rp. 0" : formatRupiah(strval($grossProfit), "Rp. ");
    //         })
    //         ->addColumn('gross_margin', function ($row) {
    //             $grossMargin = count($row->itemTransaction) ? "100%" : "0%";
    //             return $grossMargin;
    //         })
    //         ->setRowId('id')
    //         ->make(true);
    // }

    public function getItemSales(Request $request)
    {
        $dates = explode(' - ', $request->input('date'));
        if (count($dates) == 2) {
            $startDate = Carbon::createFromFormat('Y/m/d', trim($dates[0]))->startOfDay();
            $endDate = Carbon::createFromFormat('Y/m/d', trim($dates[1]))->endOfDay();
        } else {
            $startDate = Carbon::now()->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        }

        $outlet = $request->input('outlet');

        $variants = VariantProduct::query()
            ->join('products', 'products.id', '=', 'variant_products.product_id')
            ->join('outlets', 'outlets.id', '=', 'products.outlet_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->whereNull('products.deleted_at')
            ->when($outlet !== 'all', function ($query) use ($outlet) {
                $query->where('products.outlet_id', $outlet);
            })
            ->select([
                'variant_products.id',
                'variant_products.name as variant_name',
                'variant_products.harga',
                'products.id as product_id',
                'products.name as product_name',
                'outlets.name as outlet_name',
                'categories.name as category_name',
            ])
            ->get();

        $items = [];
        foreach ($variants as $variant) {
            $name = $variant->variant_name == $variant->product_name
                ? $variant->product_name
                : $variant->product_name.' - '.$variant->variant_name;

            if ($outlet === 'all') {
                $name .= ' ('.$variant->outlet_name.')';
            }

            $items[(string) $variant->id] = [
                'id' => $variant->id,
                'product_id' => $variant->product_id,
                'name' => $name,
                'category' => $variant->category_name,
                'item_sold' => 0,
                'gross_sales' => 0,
                'discounts' => 0,
                'net_sales' => 0,
                'gross_profit' => 0,
                'gross_margin' => 0,
                'price' => (float) $variant->harga,
            ];
        }

        if ($items) {
            TransactionItem::query()
                ->select(['variant_id', 'discount_id'])
                ->whereIn('variant_id', array_keys($items))
                ->whereBetween('created_at', [$startDate, $endDate])
                ->cursor()
                ->each(function ($transactionItem) use (&$items) {
                    $variantId = (string) $transactionItem->variant_id;
                    if (!isset($items[$variantId])) {
                        return;
                    }

                    $discountAmount = 0;
                    $discounts = json_decode($transactionItem->discount_id, true);
                    if (is_array($discounts)) {
                        foreach ($discounts as $discount) {
                            $discountAmount += (float) ($discount['result'] ?? 0);
                        }
                    }

                    $items[$variantId]['item_sold']++;
                    $items[$variantId]['gross_sales'] += $items[$variantId]['price'];
                    $items[$variantId]['discounts'] += $discountAmount;
                });
        }

        foreach ($items as &$item) {
            $item['net_sales'] = $item['gross_sales'] - $item['discounts'];
            $item['gross_profit'] = $item['net_sales'];
            $item['gross_margin'] = $item['item_sold'] ? 100 : 0;
        }
        unset($item);

        $hideZeroSales = filter_var(
            $request->input('hide_zero_sales', true),
            FILTER_VALIDATE_BOOLEAN
        );
        if ($hideZeroSales) {
            $items = array_filter($items, fn ($item) => $item['item_sold'] > 0);
        }

        $search = Str::lower(trim((string) $request->input('search.value', '')));
        $matchingItems = collect($items);
        if ($search !== '') {
            $matchingItems = $matchingItems->filter(function ($item) use ($search) {
                return Str::contains(Str::lower((string) $item['name']), $search)
                    || Str::contains(Str::lower((string) ($item['category'] ?? '')), $search);
            });
        }

        $totals = [
            'item_sold' => $matchingItems->sum('item_sold'),
            'gross_sales' => $matchingItems->sum('gross_sales'),
            'discounts' => $matchingItems->sum('discounts'),
            'net_sales' => $matchingItems->sum('net_sales'),
            'gross_profit' => $matchingItems->sum('gross_profit'),
        ];

        $order = $request->input('order', [
            ['column' => 2, 'dir' => 'desc'],
            ['column' => 0, 'dir' => 'asc'],
        ]);
        $sortColumns = [
            0 => 'name',
            1 => 'category',
            2 => 'item_sold',
            3 => 'gross_sales',
            4 => 'discounts',
            5 => 'net_sales',
            6 => 'gross_profit',
            7 => 'gross_margin',
        ];

        uasort($items, function ($first, $second) use ($order, $sortColumns) {
            foreach ($order as $criterion) {
                $sortColumn = $sortColumns[$criterion['column'] ?? null] ?? null;
                if (!$sortColumn) {
                    continue;
                }

                $direction = ($criterion['dir'] ?? 'asc') === 'desc' ? -1 : 1;
                $firstValue = $first[$sortColumn] ?? '';
                $secondValue = $second[$sortColumn] ?? '';
                $comparison = is_numeric($firstValue) && is_numeric($secondValue)
                    ? $firstValue <=> $secondValue
                    : strnatcasecmp((string) $firstValue, (string) $secondValue);

                if ($comparison !== 0) {
                    return $comparison * $direction;
                }
            }

            return $first['id'] <=> $second['id'];
        });

        return DataTables::of(array_values($items))
            ->order(function () {
                // Raw values are sorted before formatting so pagination remains global.
            })
            ->editColumn('gross_sales', function ($row) {
                return $row['gross_sales'] == 0 ? 'Rp. 0' : formatRupiah((string) $row['gross_sales'], 'Rp. ');
            })
            ->editColumn('discounts', function ($row) {
                return $row['discounts'] == 0 ? 'Rp. 0' : formatRupiah((string) $row['discounts'], 'Rp. ');
            })
            ->editColumn('net_sales', function ($row) {
                return $row['net_sales'] == 0 ? 'Rp. 0' : formatRupiah((string) $row['net_sales'], 'Rp. ');
            })
            ->editColumn('gross_profit', function ($row) {
                return $row['gross_profit'] == 0 ? 'Rp. 0' : formatRupiah((string) $row['gross_profit'], 'Rp. ');
            })
            ->editColumn('gross_margin', function ($row) {
                return $row['gross_margin'].'%';
            })
            ->removeColumn('price')
            ->setRowId('id')
            ->with('totals', $totals)
            ->make(true);
    }


    public function getCategorySales(Request $request)
    {
        $dates = explode(' - ', $request->input('date'));
        if (count($dates) == 2) {
            $startDate = Carbon::createFromFormat('Y/m/d', trim($dates[0]))->startOfDay();
            $endDate = Carbon::createFromFormat('Y/m/d', trim($dates[1]))->endOfDay();
        } else {
            // Tetapkan tanggal default jika input 'date' hilang atau tidak valid
            $startDate = Carbon::now()->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        }

        $outlet = $request->input('outlet');

        if($outlet == "all"){
            $data = Category::with(['products' => function ($product) use ($startDate, $endDate) {
                $product->with(['outlet', 'variants' => function ($variant) use ($startDate, $endDate) {
                    $variant->with(['itemTransaction' => function ($itemTransaction) use ($startDate, $endDate) {
                        $itemTransaction->whereBetween('created_at', [$startDate, $endDate]);
                    }]);
                }, 'itemTransaction' => function ($itemTransaction) use ($startDate, $endDate) {
                    $itemTransaction->whereBetween('created_at', [$startDate, $endDate]);
                }])->orderBy('outlet_id');
            }])
            ->whereHas('products')
            ->get();
        }else{
            $data = Category::with(['products' => function ($product) use ($startDate, $endDate, $outlet) {
                $product->with(['variants' => function ($variant) use ($startDate, $endDate) {
                    $variant->with(['itemTransaction' => function ($itemTransaction) use ($startDate, $endDate) {
                        $itemTransaction->whereBetween('created_at', [$startDate, $endDate]);
                    }]);
                }, 'itemTransaction' => function ($itemTransaction) use ($startDate, $endDate) {
                    $itemTransaction->whereBetween('created_at', [$startDate, $endDate]);
                }])->where('outlet_id', $outlet);
            }])->whereHas('products', function ($query) use ($outlet) {
                $query->where('outlet_id', $outlet);
            })->get();
        }


        return DataTables::of($data)
            ->addColumn('category', function ($row) use($outlet) {
                return $row->name;
            })
            ->addColumn('item_sold', function ($row) {
                $itemSold = 0;
                foreach ($row->products as $product) {
                    $countBuy = Count($product->itemTransaction);
                    $itemSold += $countBuy;
                }
                return $itemSold;
            })
            ->addColumn('gross_sales', function ($row) {
                $grossSales = 0;
                foreach ($row->products as $product) {
                    foreach($product->variants as $variant){
                        $countTransaction = Count($variant->itemTransaction);
                        $hargaTotal = $countTransaction * $variant->harga;
                        $grossSales += $hargaTotal;
                    }
                }
                return $grossSales == 0 ? "Rp. 0" : formatRupiah(strval($grossSales), "Rp. ");
            })
            ->addColumn('discounts', function ($row) {
                $totalDiscount = 0;
                foreach($row->products as $product){
                    foreach($product->variants as $variant){
                        $discountVariant = 0;
                        foreach($variant->itemTransaction as $itemTransaction){
                            $dataDiscount = json_decode($itemTransaction->discount_id);

                            foreach($dataDiscount as $discount){
                                $discountVariant += $discount->result;
                            }
                        }
                        $totalDiscount += $discountVariant;
                    }
                }

                return $totalDiscount == 0 ? "Rp. 0" : formatRupiah(strval($totalDiscount), "Rp. ");
            })
            ->setRowId('id')
            ->make(true);
    }

    public function getModifierSales(Request $request)
    {
        $dates = explode(' - ', $request->input('date'));
        if (count($dates) == 2) {
            $startDate = Carbon::createFromFormat('Y/m/d', trim($dates[0]))->startOfDay();
            $endDate = Carbon::createFromFormat('Y/m/d', trim($dates[1]))->endOfDay();
        } else {
            // Tetapkan tanggal default jika input 'date' hilang atau tidak valid
            $startDate = Carbon::now()->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        }

        $outlet = $request->input('outlet');
        $customData = [];

        if ($outlet == "all") {
            $dataModifier = ModifierGroup::with(['modifier', 'outlet'])->get();
        } else {
            $dataModifier = ModifierGroup::with(['modifier'])->where('outlet_id', $outlet)->get();
        }

        $groups = [];
        $modifierGroupKeys = [];

        foreach ($dataModifier as $modifierGroup) {
            $groupKey = 'group-' . $modifierGroup->id;
            $groups[$groupKey] = [
                'name' => $modifierGroup->name,
                'outlet_name' => $outlet == 'all' ? optional($modifierGroup->outlet)->name : null,
                'modifiers' => [],
            ];

            foreach ($modifierGroup->modifier as $modifier) {
                $modifierId = (string) $modifier->id;
                $groups[$groupKey]['modifiers'][$modifierId] = [
                    'name' => $modifier->name,
                    'quantity_sold' => 0,
                    'gross_sales' => 0,
                    'discounts' => 0,
                ];
                $modifierGroupKeys[$modifierId] = $groupKey;
            }
        }

        $historicalModifiers = [];
        $transactionItems = TransactionItem::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->select([
                'transaction_items.modifier_id',
                'transaction_items.discount_id',
                'transactions.outlet_id',
            ])
            ->whereNull('transactions.deleted_at')
            ->whereNotNull('transaction_items.modifier_id')
            ->where('transaction_items.modifier_id', '!=', '[]')
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->when($outlet != 'all', function ($query) use ($outlet) {
                $query->where('transactions.outlet_id', $outlet);
            });

        $transactionItems->cursor()
            ->each(function ($transactionItem) use (&$groups, &$modifierGroupKeys, &$historicalModifiers) {
                $modifiers = json_decode($transactionItem->modifier_id, true);
                if (!is_array($modifiers)) {
                    return;
                }

                $discountPercentage = 0;
                $discounts = json_decode($transactionItem->discount_id, true);
                if (is_array($discounts)) {
                    foreach ($discounts as $discount) {
                        $discountPercentage += (float) ($discount['value'] ?? 0);
                    }
                }

                foreach ($modifiers as $modifierSnapshot) {
                    if (!is_array($modifierSnapshot) || !isset($modifierSnapshot['id'])) {
                        continue;
                    }

                    $modifierId = (string) $modifierSnapshot['id'];
                    $price = (float) ($modifierSnapshot['harga'] ?? 0);
                    $discountAmount = $price * $discountPercentage / 100;

                    if (isset($modifierGroupKeys[$modifierId])) {
                        $groupKey = $modifierGroupKeys[$modifierId];
                        $groups[$groupKey]['modifiers'][$modifierId]['quantity_sold']++;
                        $groups[$groupKey]['modifiers'][$modifierId]['gross_sales'] += $price;
                        $groups[$groupKey]['modifiers'][$modifierId]['discounts'] += $discountAmount;
                        continue;
                    }

                    if (!isset($historicalModifiers[$modifierId])) {
                        $historicalModifiers[$modifierId] = [
                            'name' => $modifierSnapshot['nama'] ?? 'Modifier Terhapus',
                            'outlet_id' => (string) $transactionItem->outlet_id,
                            'quantity_sold' => 0,
                            'gross_sales' => 0,
                            'discounts' => 0,
                        ];
                    }

                    $historicalModifiers[$modifierId]['quantity_sold']++;
                    $historicalModifiers[$modifierId]['gross_sales'] += $price;
                    $historicalModifiers[$modifierId]['discounts'] += $discountAmount;
                }
            });

        if ($historicalModifiers) {
            $historicalModels = Modifiers::withTrashed()
                ->with(['modifierGroup' => function ($query) {
                    $query->withTrashed()->with('outlet');
                }])
                ->whereIn('id', array_keys($historicalModifiers))
                ->get()
                ->keyBy(fn ($modifier) => (string) $modifier->id);

            $fallbackOutletNames = $outlet == 'all'
                ? Outlets::whereIn('id', collect($historicalModifiers)->pluck('outlet_id')->unique())->pluck('name', 'id')
                : collect();

            foreach ($historicalModifiers as $modifierId => $totals) {
                $historicalModel = $historicalModels->get($modifierId);
                $historicalGroup = optional($historicalModel)->modifierGroup;

                if ($historicalGroup) {
                    $groupKey = 'group-' . $historicalGroup->id;
                    if (!isset($groups[$groupKey])) {
                        $groups[$groupKey] = [
                            'name' => $historicalGroup->name,
                            'outlet_name' => $outlet == 'all' ? optional($historicalGroup->outlet)->name : null,
                            'modifiers' => [],
                        ];
                    }
                } else {
                    $groupKey = 'deleted-' . $totals['outlet_id'];
                    if (!isset($groups[$groupKey])) {
                        $groups[$groupKey] = [
                            'name' => 'Modifier Terhapus',
                            'outlet_name' => $outlet == 'all' ? $fallbackOutletNames->get($totals['outlet_id']) : null,
                            'modifiers' => [],
                        ];
                    }
                }

                $groups[$groupKey]['modifiers'][$modifierId] = $totals;
            }
        }

        foreach ($groups as $groupKey => $group) {
            $groups[$groupKey]['quantity_sold'] = array_sum(array_column($group['modifiers'], 'quantity_sold'));
            $groups[$groupKey]['gross_sales'] = array_sum(array_column($group['modifiers'], 'gross_sales'));
            $groups[$groupKey]['discounts'] = array_sum(array_column($group['modifiers'], 'discounts'));
            $groups[$groupKey]['net_sales'] = $groups[$groupKey]['gross_sales'] - $groups[$groupKey]['discounts'];
        }

        $order = $request->input('order.0');
        $sortColumns = [
            0 => 'name',
            1 => 'quantity_sold',
            2 => 'gross_sales',
            3 => 'discounts',
            4 => 'net_sales',
        ];

        if (isset($sortColumns[$order['column'] ?? null])) {
            $sortColumn = $sortColumns[$order['column']];
            $sortDirection = ($order['dir'] ?? 'asc') === 'desc' ? -1 : 1;

            uasort($groups, function ($first, $second) use ($sortColumn, $sortDirection) {
                $firstValue = $first[$sortColumn];
                $secondValue = $second[$sortColumn];
                $comparison = is_numeric($firstValue) && is_numeric($secondValue)
                    ? $firstValue <=> $secondValue
                    : strnatcasecmp($firstValue, $secondValue);

                return $comparison * $sortDirection;
            });
        }

        $id = 0;
        foreach ($groups as $modifierParent) {
            $tmpDataParent = [];
            $quantitySoldParent = 0;
            $grossSoldParent = 0;
            $discountParent = 0;
            $netSalesParent = 0;

            $id++;
            array_push($tmpDataParent, $id);
            array_push($tmpDataParent, $modifierParent['name']);

            $tmpDataChild = [];
            foreach ($modifierParent['modifiers'] as $modifier) {
                $tmpDataModifier = [];
                $quantitySoldModifier = $modifier['quantity_sold'];
                $grossSalesModifier = $modifier['gross_sales'];
                $totalDiskon = $modifier['discounts'];
                $netSales = $grossSalesModifier - $totalDiskon;

                $id++;
                array_push($tmpDataModifier, $id);
                array_push($tmpDataModifier, $modifier['name']);
                array_push($tmpDataModifier, $quantitySoldModifier);
                array_push($tmpDataModifier, $grossSalesModifier);
                array_push($tmpDataModifier, $totalDiskon);
                array_push($tmpDataModifier, $netSales);
                array_push($tmpDataModifier, false);

                $quantitySoldParent += $quantitySoldModifier;
                $grossSoldParent += $grossSalesModifier;
                $discountParent += $totalDiskon;
                $netSalesParent += $netSales;

                array_push($tmpDataChild, $tmpDataModifier);
            }

            array_push($tmpDataParent, $quantitySoldParent);
            array_push($tmpDataParent, $grossSoldParent);
            array_push($tmpDataParent, $discountParent);
            array_push($tmpDataParent, $netSalesParent);
            array_push($tmpDataParent, true);

            if ($outlet == "all") {
                array_push($tmpDataParent, $modifierParent['outlet_name']);
            }

            array_push($customData, $tmpDataParent);
            array_push($customData, ...$tmpDataChild);
        }

        return DataTables::of($customData)
        ->order(function () {
            // Parent groups are sorted before flattening so their children stay attached.
        })
        ->addColumn('name',function($row) use($outlet){
            if($row[6]){
                if($outlet == "all"){
                    return $row[1] . " (" . $row[7] . ")";
                }else{
                    return $row[1];
                }
            }else{
                return "- " . $row[1];
            }
        })
        ->addColumn('quantity_sold', function($row){
            return $row[2];
        })
        ->addColumn('gross_sales', function($row){
            return $row[3] == 0 ? "Rp. 0" : formatRupiah(strval($row[3]), "Rp. ");
        })
        ->addColumn('discounts', function($row){
            return $row[4] == 0 ? "Rp. 0" : formatRupiah(strval($row[4]), "Rp. ");
        })
        ->addColumn('net_sales', function($row){
            return $row[5] == 0 ? "Rp. 0" : formatRupiah(strval($row[5]), "Rp. ");
        })
        ->setRowId('id')
        ->make(true);
    }

    public function getDiscountSales(Request $request){
        $dates = explode(' - ', $request->input('date'));
        if (count($dates) == 2) {
            $startDate = Carbon::createFromFormat('Y/m/d', trim($dates[0]))->startOfDay();
            $endDate = Carbon::createFromFormat('Y/m/d', trim($dates[1]))->endOfDay();
        } else {
            // Tetapkan tanggal default jika input 'date' hilang atau tidak valid
            $startDate = Carbon::now()->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        }

        $outlet = $request->input('outlet');

        if($outlet == "all"){
            $dataDiscount = Discount::with(['outlet'])->get();
        }else{
            $dataDiscount = Discount::where('outlet_id', $outlet)->get();
        }

        foreach($dataDiscount as $discount){
            if($discount->satuan == "percent"){
                $dataTransactions = TransactionItem::whereBetween('created_at', [$startDate, $endDate])->whereJsonContains('discount_id', ['id' => strval($discount->id)])->get();
                $discount['count'] = count($dataTransactions);
                $totalDiscount = 0;
                foreach($dataTransactions as $transaction){
                    $discountData = json_decode($transaction->discount_id);
                    foreach($discountData as $data){
                        if($data->id == $discount->id){
                            $totalDiscount += $data->result;
                        }
                    }
                }
                $discount['total_discount'] = $totalDiscount;
            }else{
                $dataTransactions = Transaction::whereBetween('created_at', [$startDate, $endDate])->whereJsonContains('diskon_all_item', ['id' => $discount->id])->get();
                $discount['count'] = count($dataTransactions);
                $discount['total_discount'] = count($dataTransactions) * $discount->amount;
            }
        }

        return DataTables::of($dataDiscount)
        ->addColumn('name', function($row) use($outlet){
            if($outlet == "all"){
                return $row->name . " (" . $row->outlet->name . ")";
            }else{
                return $row->name;
            }
        })
        ->addColumn('discount_amount', function($row){
            if($row->satuan == "percent"){
                return strval($row->amount) . "%";
            }else{
                return formatRupiah(strval($row->amount), "Rp. ");
            }
        })
        ->addColumn('count', function($row){
            return $row->count;
        })
        ->addColumn('discount_total', function($row){
            return $row->total_discount == 0 ? "Rp. 0" : formatRupiah(strval($row->total_discount), "Rp. ");
        })
        ->setRowId('id')
        ->make(true);
    }

    public function getTaxSales(Request $request){
        $dates = explode(' - ', $request->input('date'));
        if (count($dates) == 2) {
            $startDate = Carbon::createFromFormat('Y/m/d', trim($dates[0]))->startOfDay();
            $endDate = Carbon::createFromFormat('Y/m/d', trim($dates[1]))->endOfDay();
        } else {
            // Tetapkan tanggal default jika input 'date' hilang atau tidak valid
            $startDate = Carbon::now()->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        }

        $outlet = $request->input('outlet');

        if($outlet == "all"){
            $data = Taxes::with(['outlets'])->get();
        }else{
            $data = Taxes::where('outlet_id', $outlet)->get();
        }


        foreach($data as $tax){
            $dataTransactions = Transaction::with(['itemTransaction'])->whereBetween('created_at', [$startDate, $endDate])->whereJsonContains('total_pajak', ['id' => $tax->id])->get();
            $totalTaxableAmount = 0;
            $totalTaxCollected = 0;

            foreach($dataTransactions as $transaction){
                $dataTax = $transaction->total_pajak ? json_decode($transaction->total_pajak) : [] ;

                foreach($dataTax as $item){
                    if($item->id == $tax->id){
                        $totalTaxableAmount += $transaction->total;
                        $totalTaxCollected += $item->total;
                    }
                }
            }

            $tax['taxable_amount'] = $totalTaxableAmount;
            $tax['tax_collected'] = $totalTaxCollected;
        }

        return DataTables::of($data)
        ->addColumn('name', function($row) use($outlet){
            if($outlet == "all"){
                return $row->name . " (" . $row->outlets->name . ")";
            }else{
                return $row->name;
            }
        })
        ->addColumn('tax_rate', function($row){
            return strval($row->amount) . "%";
        })
        ->addColumn('taxable_amount', function($row){
            $taxableAmount = $row->taxable_amount - $row->tax_collected;
            return $taxableAmount == 0 ? "Rp. 0" : formatRupiah(strval($taxableAmount), "Rp. ");
        })
        ->addColumn('tax_collected', function($row){
            return $row->tax_collected == 0 ? "Rp. 0" : formatRupiah(strval($row->tax_collected), "Rp. ");
        })
        ->setRowId('id')
        ->make(true);
    }

    public function getCollectedBySales(Request $request){
        $dates = explode(' - ', $request->input('date'));
        if (count($dates) == 2) {
            $startDate = Carbon::createFromFormat('Y/m/d', trim($dates[0]))->startOfDay();
            $endDate = Carbon::createFromFormat('Y/m/d', trim($dates[1]))->endOfDay();
        } else {
            // Tetapkan tanggal default jika input 'date' hilang atau tidak valid
            $startDate = Carbon::now()->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        }

        $outlet = $request->input('outlet');

        $data = User::with(['transaction' => function($transaction) use($startDate, $endDate){
            $transaction->whereBetween('created_at', [$startDate, $endDate]);
        }])->where('name' , '!=', 'ardian')->get();

        if($outlet == "all"){
            $filteredData = $data;
        }else{
            $filteredData = $data->filter(function($user) use($outlet) {
                $outletIds = json_decode($user->outlet_id);
                return in_array($outlet, $outletIds);
            });
        }


        return DataTables::of($filteredData)
        ->addColumn('name', function($row){
            return $row->name;
        })
        ->addColumn('title', function($row){
            return $row->getRoleNames()[0];
        })
        ->addColumn('number_of_transaction', function($row){
            return count($row->transaction);
        })
        ->addColumn('total_collected', function($row){
            $totalCollected = 0;
            foreach($row->transaction as $transaction){
                $totalCollected += $transaction->total;
            }

            return $totalCollected == 0 ? "Rp. 0" : formatRupiah(strval($totalCollected), "Rp. ");
        })
        ->setRowId('id')
        ->make(true);
    }

    public function getDetailItemCategorySales(Request $request){
        $dates = explode(' - ', $request->date);
        if (count($dates) == 2) {
            $startDate = Carbon::createFromFormat('Y/m/d', trim($dates[0]))->startOfDay();
            $endDate = Carbon::createFromFormat('Y/m/d', trim($dates[1]))->endOfDay();
        } else {
            // Tetapkan tanggal default jika input 'date' hilang atau tidak valid
            $startDate = Carbon::now()->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        }

        $outlet = $request->outlet;

        if($outlet == "all"){
            $product = Product::where('category_id', $request->idCategory)
            ->whereHas('itemTransaction', function($query) use($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->with(['variants' => function($variant) use($startDate, $endDate) {
                $variant->whereHas('itemTransaction', function($query) use($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                })
                ->with(['itemTransaction' => function($itemTransaction) use($startDate, $endDate) {
                    $itemTransaction->select(
                        'variant_id',
                        DB::raw('COUNT(*) as total_count'),
                        'product_id',
                        'discount_id',
                        'modifier_id',
                        'promo_id',
                        'sales_type_id',
                        'transaction_id',
                        'catatan',
                        'reward_item',
                        'created_at'
                    )
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->groupBy('variant_id', 'product_id', 'discount_id', 'modifier_id', 'promo_id', 'sales_type_id', 'transaction_id', 'catatan', 'reward_item', 'created_at');
                }]);
            }, 'outlet'])
            ->get();
        }else{
         $product = Product::where('category_id', $request->idCategory)
            ->where('outlet_id', $outlet)
            ->whereHas('itemTransaction', function($query) use($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->with(['variants' => function($variant) use($startDate, $endDate) {
                $variant->whereHas('itemTransaction', function($query) use($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                })
                ->with(['itemTransaction' => function($itemTransaction) use($startDate, $endDate) {
                    $itemTransaction->select(
                        'variant_id',
                        DB::raw('COUNT(*) as total_count'),
                        'product_id',
                        'discount_id',
                        'modifier_id',
                        'promo_id',
                        'sales_type_id',
                        'transaction_id',
                        'catatan',
                        'reward_item',
                        'created_at'
                    )
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->groupBy('variant_id', 'product_id', 'discount_id', 'modifier_id', 'promo_id', 'sales_type_id', 'transaction_id', 'catatan', 'reward_item', 'created_at');
                }]);
            }, 'outlet'])
            ->get();

        }

        $categoryName = Category::find($request->idCategory)->name;

        return view("layouts.sales.modal-detail-category-sales",[
            'products' => $product,
            'categoryName' => $categoryName,
        ]);
    }

     public function exportSalesSummary(Request $request)
    {
        $date   = $request->get('date');   // sama seperti di UI
        $outlet = $request->get('outlet'); // 'all' atau id tertentu

        // tentukan daftar outlet yang akan diringkas
        if ($outlet === 'all') {
            // gunakan daftar outlet user atau semua outlet sesuai kebutuhanmu
            $outlets = Outlets::whereIn('id', json_decode(auth()->user()->outlet_id ?? '[]') ?: [])
                        ->get(['id','name']);
        } else {
            $o = Outlets::find($outlet);
            $outlets = collect($o ? [$o] : []);
        }

        $rows = [];
        foreach ($outlets as $o) {
            // Panggil service/perhitungan yang juga dipakai AJAX Summary
            // Pastikan function ini mengembalikan keys: gross, discount, refund, net, gratuity, tax, rounding, total_collected
            $summary = $this->computeOutletSummary($date, $o->id); // <- implementasikan sama dengan logic tab Summary
            $rows[] = [
                'outlet'          => $o->name,
                'gross'           => $summary['gross'] ?? 0,
                'discount'        => $summary['discount'] ?? 0,
                'refund'          => $summary['refund'] ?? 0,
                'net'             => $summary['net'] ?? 0,
                'gratuity'        => $summary['gratuity'] ?? 0,
                'tax'             => $summary['tax'] ?? 0,
                'rounding'        => $summary['rounding'] ?? 0,
                'total_collected' => $summary['total_collected'] ?? 0,
            ];
        }

        $export = new SalesSummaryExport($rows, withTotals: true);
        $filename = 'Sales Summary Report '.now()->format('Ymd_His').'.xlsx';
        return Excel::download($export, $filename);
    }

    // TODO: Samakan isi function ini dengan logika yang dipakai oleh AJAX Sales Summary kamu
    private function computeOutletSummary(string $dateRange, int $outletId): array
    {
        // Implementasikan sesuai logic-mu:
        // - parse $dateRange (single date / range)
        // - filter transactions by outlet & date range
        // - hitung gross, discount, refund, net, gratuity, tax, rounding, total_collected
        return [
            'gross' => 0,
            'discount' => 0,
            'refund' => 0,
            'net' => 0,
            'gratuity' => 0,
            'tax' => 0,
            'rounding' => 0,
            'total_collected' => 0,
        ];
    }

    public function getDetailItemSales(Request $request)
    {

        $dates = explode(' - ', $request->date);
        if (count($dates) == 2) {
            $startDate = Carbon::createFromFormat('Y/m/d', trim($dates[0]))->startOfDay();
            $endDate = Carbon::createFromFormat('Y/m/d', trim($dates[1]))->endOfDay();
        } else {
            // Tetapkan tanggal default jika input 'date' hilang atau tidak valid
            $startDate = Carbon::now()->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        }

        $product = Product::findOrFail($request->idProduct);
        $nameProductVariant = $request->nameProductVariant;

        // ambil semua group & modifier TANPA transaksi dulu
        $groups = $product->modifierGroups()
        ->with('modifier')
        ->get()
        ->makeHidden(['created_at', 'updated_at', 'deleted_at'])
        ->each(function($group){
            $group->modifier->makeHidden(['created_at', 'updated_at', 'deleted_at']);
        });

        // kumpulkan semua id modifier
        $modifierIds = $groups->pluck('modifier.*.id')->flatten()->filter()->unique()->values();

        // ambil semua transaction_items yang mencocokkan salah satu modifier id
        $items = TransactionItem::query()
            ->select('variant_id',
                DB::raw('COUNT(*) as total_count'),
                'product_id',
                'discount_id',
                'modifier_id',
                'promo_id',
                'sales_type_id',
                'transaction_id',
                'catatan',
                'reward_item',
                'created_at')
            ->where(function ($q) use ($modifierIds) {
                foreach ($modifierIds as $mid) {
                    $q->orWhereRaw("JSON_CONTAINS(modifier_id, JSON_OBJECT('id', ?), '$')", [(string) $mid]);
                }
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('variant_id', $request->idVariant)
            ->groupBy('variant_id', 'product_id', 'discount_id', 'modifier_id', 'promo_id', 'sales_type_id', 'transaction_id', 'catatan', 'reward_item', 'created_at')
            ->get();

        // indekskan per modifier id
        $byModifier = [];
        foreach ($items as $it) {
            // ambil semua id modifier yang ada di JSON kolom ini
            foreach (json_decode($it->modifier_id, true) ?? [] as $m) {
                $byModifier[(string)$m['id']][] = $it;
            }
        }

        // sisipkan ke masing-masing modifier sebagai atribut sementara `transaction_items`
        $groups->each(function ($group) use (&$byModifier) {
            $group->modifier->each(function ($mod) use (&$byModifier) {
                $mod->setRelation(
                    'transaction_items',
                    collect($byModifier[(string)$mod->id] ?? [])
                );
            });
        });

        // debug
        // dd($groups->toArray());

        return view('layouts.sales.modal-detail-item-sales', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'modifierGroups' => $groups,
            'product'        => $product,
            'nameProductVariant' => $nameProductVariant
        ]);
    }

    public function getPaymentMerchantMethodSales(Request $request)
    {
        $dates = explode(' - ', $request->input('date'));
        if (count($dates) == 2) {
            $startDate = Carbon::createFromFormat('Y/m/d', trim($dates[0]))->startOfDay();
            $endDate = Carbon::createFromFormat('Y/m/d', trim($dates[1]))->endOfDay();
        }

        $outlet = $request->input('outlet');

        if($outlet == "all"){
            $data = CategoryPayment::with(['payment' => function ($payment) use ($startDate, $endDate, $outlet) {
                $payment->with(['transactions' => function ($transaction) use ($startDate, $endDate) {
                    $transaction->whereBetween('created_at', [$startDate, $endDate]);
                    $transaction->with(['itemTransaction' => function($itemTransaction) use($startDate, $endDate){
                        $itemTransaction->whereBetween('created_at', [$startDate, $endDate])
                        ->whereHas('product', function ($q) {
                            $q->where('exclude_tax', 1); // atau true, pastikan casts boolean
                        });
                    }]);
                    // $transaction->whereDate('created_at', Carbon::yesterday())->where('outlet_id', $outlet);
                }]);
            }, 'transactions' => function ($transaction) use ($startDate, $endDate) {
                $transaction->whereBetween('created_at', [$startDate, $endDate]);
                $transaction->with(['itemTransaction' => function($itemTransaction) use($startDate, $endDate){
                        $itemTransaction->whereBetween('created_at', [$startDate, $endDate])
                        ->whereHas('product', function ($q) {
                            $q->where('exclude_tax', 1); // atau true, pastikan casts boolean
                        });
                    }]);
                // $transaction->whereDate('created_at', Carbon::yesterday())->where('outlet_id', $outlet);
            }])->get();
        }else{
            $data = CategoryPayment::with(['payment' => function ($payment) use ($startDate, $endDate, $outlet) {
                $payment->with(['transactions' => function ($transaction) use ($startDate, $endDate, $outlet) {
                    $transaction->whereBetween('created_at', [$startDate, $endDate])->where('outlet_id', $outlet);
                    $transaction->with(['itemTransaction' => function($itemTransaction) use($startDate, $endDate){
                        $itemTransaction->whereBetween('created_at', [$startDate, $endDate])
                        ->whereHas('product', function ($q) {
                            $q->where('exclude_tax', 1); // atau true, pastikan casts boolean
                        });
                    }]);
                    // $transaction->whereDate('created_at', Carbon::yesterday())->where('outlet_id', $outlet);
                }]);
            }, 'transactions' => function ($transaction) use ($startDate, $endDate, $outlet) {
                $transaction->whereBetween('created_at', [$startDate, $endDate])->where('outlet_id', $outlet);
                $transaction->with(['itemTransaction' => function($itemTransaction) use($startDate, $endDate){
                    $itemTransaction->whereBetween('created_at', [$startDate, $endDate])
                    ->whereHas('product', function ($q) {
                        $q->where('exclude_tax', 1); // atau true, pastikan casts boolean
                    });
                }]);
                // $transaction->whereDate('created_at', Carbon::yesterday())->where('outlet_id', $outlet);
            }])->get();

        }

        // dd($data->toArray());
        // Format data untuk dikembalikan
        $result = [];
        foreach ($data as $category) {
            $tmpData = [];
            if ($category->name == "Cash" || $category->id == 1) {
                $tmpData['payment_method'] = $category->name;
                $tmpData['number_of_transactions'] = count($category->transactions);
                $tmpData['parent'] = true;
                $totalCollected = 0;


                foreach ($category->transactions as $transaction) {
                    foreach($transaction->itemTransaction as $itemTransaction) {
                        $totalCollected += $itemTransaction->harga;
                    }
                }

                $tmpData['total_collected'] = $totalCollected;

                array_push($result, $tmpData);
            } else {
                $tmpData['payment_method'] = $category->name;
                $tmpData['number_of_transactions'] = "";
                $tmpData['total_collected'] = "";
                $tmpData['parent'] = true;

                array_push($result, $tmpData);

                // dd($category->toArray());
                foreach ($category->payment as $payment) {
                    $tmpData['payment_method'] = $payment->name;
                    $tmpData['number_of_transactions'] = count($payment->transactions);
                    $tmpData['parent'] = false;
                    $paymentTotalCollected = 0;

                    foreach ($payment->transactions as $paymentTransaction) {
                        foreach($paymentTransaction->itemTransaction as $itemTransaction) {
                            $paymentTotalCollected += $itemTransaction->harga;
                        }
                    }
                    $tmpData['total_collected'] = $paymentTotalCollected;

                    array_push($result, $tmpData);
                }
            }
        }

        return response()->json($result);
    }

    // old version
    // public function exportItemSales(Request $request)
    // {
    //     $dates = explode(' - ', $request->input('date'));
    //     if (count($dates) == 2) {
    //         $startDate = Carbon::createFromFormat('Y/m/d', trim($dates[0]))->startOfDay();
    //         $endDate   = Carbon::createFromFormat('Y/m/d', trim($dates[1]))->endOfDay();
    //     } else {
    //         $startDate = Carbon::now()->startOfDay();
    //         $endDate   = Carbon::now()->endOfDay();
    //     }

    //     $outlet = $request->input('outlet', 'all');

    //     $fileName = 'item_sales_report_' . now()->format('Ymd_His') . '.xlsx';
    //     $path = "exports/{$fileName}";

    //     (new ItemSalesExport(
    //         $startDate,
    //         $endDate,
    //         $outlet
    //     ))->queue($path, 'public');

    //     // URL ini baru valid setelah job selesai. Simpan/ingatkan user saja dulu.
    //     return response()->json([
    //         'ok'           => true,
    //         'message'      => 'Export dimulai di background. Anda akan dapat file ketika proses selesai.',
    //         'path'         => $path,
    //         'download_url' => Storage::disk('public')->url($path),
    //     ]);
    //     // return Excel::download(
    //     //     new ItemSalesExport($startDate, $endDate, $outlet),
    //     //     $fileName
    //     // );
    // }

     public function exportItemSales(Request $r)
    {
        // Ambil outlet pertama untuk nama file (kalau multi outlet dipilih)
        $outletName = 'all_outlets';

        $outletIds = (array) $r->input('outlet_id', []); // dari outlet_id[]
        if (!empty($outletIds)) {
            $outlet = Outlets::whereIn('id', $outletIds)->first();
            if ($outlet) {
                $outletName = Str::slug($outlet->name, '_');
            }
        }

        $filename = 'item_sales_' . $outletName . '_' . now()->format('Ymd_His') . '.xlsx';
        $path     = "exports/{$filename}";

        // Kirim ke queue, pakai Laravel Excel
        (new ItemSalesExport(
            from:      $r->input('from'),
            to:        $r->input('to'),
            outletIds: $outletIds
        ))->queue($path, 'public');

        return response()->json([
            'ok'           => true,
            'message'      => 'Export Item Sales dimulai di background. File akan tersedia ketika proses selesai.',
            'path'         => $path,
            'download_url' => Storage::disk('public')->url($path),
        ]);
    }

}
