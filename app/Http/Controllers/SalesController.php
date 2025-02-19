<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CategoryPayment;
use App\Models\Discount;
use App\Models\ModifierGroup;
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
use Yajra\DataTables\Services\DataTable;

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
                return $grossSales == 0 ? "Rp. 0" : formatRupiah(strval($grossSales), "Rp. ");
            })
            ->addColumn('discounts', function ($row) {
                $totalDiscount = 0;
                foreach ($row->itemTransaction as $itemTransaction) {
                    $dataDiscount = json_decode($itemTransaction->discount_id);
                    foreach ($dataDiscount as $discount) {
                        $totalDiscount += $discount->result;
                    }
                }
                return $totalDiscount == 0 ? "Rp. 0" : formatRupiah(strval($totalDiscount), "Rp. ");
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

                $netSales = $grossSales -= $totalDiscount;
                return $netSales == 0 ? "Rp. 0" : formatRupiah(strval($netSales), "Rp. ");
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

                $grossProfit = $grossSales -= $totalDiscount;
                return $grossProfit == 0 ? "Rp. 0" : formatRupiah(strval($grossProfit), "Rp. ");
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

    public function getModifierSales(Request $request){
        
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

        $dataModifier = ModifierGroup::with(['modifier'])->where('outlet_id', $outlet)->get();

        $id = 0;
        foreach($dataModifier as $modifierParent){
            $tmpDataParent = [];
            $quantitySoldParent = 0;
            $grossSoldParent = 0;
            $discountParent = 0;
            $netSalesParent = 0;

            $id++;
            array_push($tmpDataParent, $id);
            array_push($tmpDataParent, $modifierParent->name);
            
            $tmpDataChild = [];
            foreach($modifierParent->modifier as $modifier){
                $tmpDataModifier = [];
                $transactions = TransactionItem::whereJsonContains('modifier_id', ['id' => strval($modifier->id)])->whereBetween('created_at', [$startDate, $endDate])->get();
                $quantitySoldModifier = count($transactions);
                $grossSalesModifier = $modifier->harga * $quantitySoldModifier;
                $totalDiskon = 0;

                foreach($transactions as $transaction){
                    $dataDiskonTransaction = json_decode($transaction->discount_id);
                    foreach($dataDiskonTransaction as $diskon){
                        $totalDiskon += $modifier->harga * $diskon->value / 100;
                    }
                }
                $netSales = $grossSalesModifier - $totalDiskon;

                $id++;
                array_push($tmpDataModifier, $id);
                array_push($tmpDataModifier, $modifier->name);
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

            array_push($customData, $tmpDataParent);
            array_push($customData, ...$tmpDataChild);
        }

        return DataTables::of($customData)
        ->addColumn('name',function($row){
            if($row[6]){
                return $row[1];
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
        
        $dataDiscount = Discount::where('outlet_id', $outlet)->get();

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
        ->addColumn('name', function($row){
            return $row->name;
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
        
        $data = Taxes::where('outlet_id', $outlet)->get();
        

        foreach($data as $tax){
            $dataTransactions = Transaction::with(['itemTransaction'])->whereBetween('created_at', [$startDate, $endDate])->whereJsonContains('total_pajak', ['id' => $tax->id])->get();
            $totalTaxableAmount = 0;
            $totalTaxCollected = 0;

            foreach($dataTransactions as $transaction){
                $dataTax = json_decode($transaction->total_pajak);

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
        ->addColumn('name', function($row){
            return $row->name;
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

        $filteredData = $data->filter(function($user) use($outlet) {
            $outletIds = json_decode($user->outlet_id);
            return in_array($outlet, $outletIds);
        });
        

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
}
