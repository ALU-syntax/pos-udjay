<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CategoryPayment;
use App\Models\Outlets;
use App\Models\Product;
use App\Models\SalesType;
use App\Models\Transaction;
use App\Models\VariantProduct;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;
use Illuminate\Http\Request;

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
            $startDate = Carbon::now()->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        }

        $outlet = $request->input('outlet');

        $data = Category::with(['products' => function ($product) use ($startDate, $endDate) {
            $product->with(['variants' => function ($variant) use ($startDate, $endDate) {
                $variant->with(['itemTransaction' => function ($itemTransaction) use ($startDate, $endDate) {
                    $itemTransaction->whereBetween('created_at', [$startDate, $endDate]);
                }]);
            }, 'itemTransaction' => function ($itemTransaction) use ($startDate, $endDate) {
                $itemTransaction->whereBetween('created_at', [$startDate, $endDate]);
            }])->where('outlet_id', 1);;
        }])->whereHas('products', function ($query) use ($outlet) {
            $query->where('outlet_id', 1);
        })->get();


        // $data = Product::with(['variants' => function($variant) use($startDate, $endDate){
        //     $variant->with(['itemTransaction' => function($itemTransaction) use($startDate, $endDate){
        //         $itemTransaction->whereBetween('created_at', [$startDate, $endDate]);
        //     }]);
        // }])->where('outlet_id', 1)->get();

        // dd($data);
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

        $dataTransaction = Transaction::with(['itemTransaction'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('outlet_id', $outlet)->get(); // Ambil data sesuai kebutuhan  
        // dd($data);

        // dd(json_decode($dataTransaction[0]->total_pajak));
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

        $data = CategoryPayment::with(['payment' => function ($payment) use ($startDate, $endDate, $outlet) {
            $payment->with(['transactions' => function ($transaction) use ($startDate, $endDate, $outlet) {
                $transaction->whereBetween('created_at', [$startDate, $endDate])->where('outlet_id', $outlet);
                // $transaction->whereDate('created_at', Carbon::yesterday())->where('outlet_id', $outlet);
            }]);
        }, 'transactions' => function ($transaction) use ($startDate, $endDate, $outlet) {
            $transaction->whereBetween('created_at', [$startDate, $endDate])->where('outlet_id', $outlet);
            // $transaction->whereDate('created_at', Carbon::yesterday())->where('outlet_id', $outlet);
        }])->get();

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

        $dataTransaction = Transaction::with(['itemTransaction'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('outlet_id', $outlet)->get(); // Ambil data sesuai kebutuhan  

        $grossSales = 0;
        $discount = 0;
        $netSales = 0;

        foreach ($dataTransaction as $transaction) {
            $discount += $transaction->total_diskon;

            $totalTax = 0;
            foreach (json_decode($transaction->total_pajak) as $itemPajak) {
                $totalTax += $itemPajak->total;
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

        $query = SalesType::with(['itemTransaction' => function ($transaction) use ($startDate, $endDate, $outlet) {
            $transaction->with(['variant'])->whereBetween('created_at', [$startDate, $endDate]);
        }])->where('outlet_id', $outlet)->get();
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

    public function getItemSales(Request $request)
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

        $data = VariantProduct::with(['itemTransaction' => function ($transaction) use ($startDate, $endDate) {
            $transaction->whereBetween('created_at', [$startDate, $endDate]);
        }, 'product.category'])->whereHas('product', function ($query) use ($outlet) {
            $query->where('outlet_id', $outlet);
        })->get();

        return DataTables::of($data)
            ->addColumn('name', function ($row) {
                // $namaVariant
                return ($row->name == $row->product->name) ? $row->product->name : $row->product->name . ' - ' . $row->name;
            })
            ->addColumn('category', function ($row) {
                return $row->product->category->name;
            })
            ->addColumn('item_sold', function ($row) {
                $itemSold = count($row->itemTransaction);
                return $itemSold;
            })
            ->addColumn('gross_sales', function ($row) {
                $itemSold = count($row->itemTransaction);
                $grossSales = $itemSold * $row->harga;
                return $grossSales;
            })
            ->addColumn('discounts', function ($row) {
                $totalDiscount = 0;
                foreach ($row->itemTransaction as $itemTransaction) {
                    $dataDiscount = json_decode($itemTransaction->discount_id);
                    foreach ($dataDiscount as $discount) {
                        $totalDiscount += $discount->result;
                    }
                }
                return $totalDiscount;
            })
            ->addColumn('net_sales', function ($row) {
                $totalDiscount = 0;
                $jumlahTransaksi = count($row->itemTransaction);
                $grossSales = $jumlahTransaksi * $row->harga;
                foreach ($row->itemTransaction as $itemTransaction) {
                    $dataDiscount = json_decode($itemTransaction->discount_id);

                    foreach ($dataDiscount as $discount) {
                        $totalDiscount += $discount->result;
                    }
                }

                return $grossSales -= $totalDiscount;
            })
            ->addColumn('gross_profit', function ($row) {
                $totalDiscount = 0;
                $jumlahTransaksi = count($row->itemTransaction);
                $grossSales = $jumlahTransaksi * $row->harga;
                foreach ($row->itemTransaction as $itemTransaction) {
                    $dataDiscount = json_decode($itemTransaction->discount_id);

                    foreach ($dataDiscount as $discount) {
                        $totalDiscount += $discount->result;
                    }
                }

                return $grossSales -= $totalDiscount;
            })
            ->addColumn('gross_margin', function ($row) {
                $grossMargin = count($row->itemTransaction) ? "100%" : "0%";
                return $grossMargin;
            })
            ->setRowId('id')
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

        return DataTables::of($data)
            ->addColumn('category', function ($row) {
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
                return $grossSales;
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
                
                return $totalDiscount;
            })
            ->setRowId('id')
            ->make(true);
    }
}
