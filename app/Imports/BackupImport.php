<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\CategoryPayment;
use App\Models\Outlets;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Taxes;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;

class BackupImport implements OnEachRow, WithHeadingRow, WithChunkReading, WithCustomCsvSettings
{
    /**
     * Custom CSV settings for semicolon delimiter and Latin‑1 encoding.
     */
    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ';',
            'encoding'  => 'ISO-8859-1',   // Latin‑1
        ];
    }

    /**
     * Process each row.
     */
    public function onRow(Row $row)
    {
        // Cek dan buat hanya jika belum ada
        $user = User::firstOrCreate(
            ['email' => 'backup@gmail.com'], // Kriteria unik untuk pengecekan
            [
                'name' => 'Backup',
                'username' => 'backup',
                'password' => bcrypt('password'),
                'status' => 1,
                'role' => 1,
                'outlet_id' => json_encode([1])
            ]
        )->assignRole('admin');

        $row = $row->toArray();

        // dd($row, $user);

        // Convert numeric strings (remove non‑numeric chars) & cast to float/int
        $numeric = fn($value) => (float) preg_replace('/[^0-9.\-]/', '', $value);

        // ---------CHECK OUTLET---------
        $namaOutlet = $row['outlet'] ?? '';
        $words = explode(' ', $namaOutlet);
        $lastWordOutlet = trim(end($words));
        $likeWordOutlet = "%" . $lastWordOutlet;
        $outlet = Outlets::where('name', 'LIKE' ,$likeWordOutlet)->first();

        // ---------TANGGAL & waktu---------
        $dateSerial = $row['date']; // 45656
        $timeSerial = $row['time']; // 0.87795138888889

        // 1. Ubah date serial ke timestamp
        $unixDate = ($dateSerial - 25569) * 86400; // 25569 = offset 1970-01-01
        $datePart = gmdate('Y-m-d', $unixDate);

        // 2. Ubah time fraction ke jam:menit:detik
        $secondsInDay = 86400;
        $unixTime = $timeSerial * $secondsInDay;
        $timePart = gmdate('H:i:s', $unixTime);
        // 3. Gabungkan
        $combined = $datePart . ' ' . $timePart;
        // 4. Buat Carbon instance
        $carbonDateTime = Carbon::parse($combined);

        // ---------TOTAL----------
        $total = $numeric($row['total_amount'] ?? 0);

        // ---------TOTAL COLLECTED----------
        $nominalBayar = $numeric($row['total_collected'] ?? 0);

        // ---------KEMBALIAN----------
        $change = intval($nominalBayar) - intval($total);

        // PAYMENT METHOD
        $paymentMethod = $row['payment_method'] ?? '';

        $checkApakahCashAtauBukan = CategoryPayment::where('name', 'LIKE', '%' . $paymentMethod . '%')->first();

        if($checkApakahCashAtauBukan){
            $categoryPaymentId = $checkApakahCashAtauBukan->id;
            $tipePembayaran = null;
            $namaTipePembayaran = "Cash";
        }else{
            $checkTipePembayaran = Payment::where('name', 'LIKE', '%' . $paymentMethod . '%')->with(['categoryPayment'])->first();
            $categoryPaymentId = $checkTipePembayaran->categoryPayment->id;
            $tipePembayaran = $checkTipePembayaran->id;
            $namaTipePembayaran = $checkTipePembayaran->name;
        }

        // ---------PAJAK----------
        $nominalPajak = $numeric($row['tax'] ?? 0);
        $dataPajak = Taxes::where('outlet_id', $outlet->id)->first();
        $resultPajak = [
            "id" => $dataPajak->id,
            "name" => $dataPajak->name,
            "total" => $nominalPajak,
            "amount" => $dataPajak->amount,
            "satuan" =>$dataPajak->satuan
        ];
        $tax = json_encode($resultPajak);

        // ---------PAJAK----------
        $discount = $row['discounts'] ?? 0;


        // dd($row, $categoryPaymentId, $tipePembayaran, $namaTipePembayaran);

        // Transaction::create([
        //     'outlet'          => $row['outlet'] ?? null,
        //     'date'            => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['date'])->format('Y-m-d'),
        //     'time'            => $row['time'] ?? null,
        //     'gross_sales'     => $numeric($row['gross_sales'] ?? 0),
        //     'discounts'       => $numeric($row['discounts'] ?? 0),
        //     'refunds'         => $numeric($row['refunds'] ?? 0),
        //     'net_sales'       => $numeric($row['net_sales'] ?? 0),
        //     'gratuity'        => $numeric($row['gratuity'] ?? 0),
        //     'tax'             => $numeric($row['tax'] ?? 0),
        //     'total_collected' => $numeric($row['total_collected'] ?? 0),
        //     'total_amount'    => $numeric($row['total_amount'] ?? 0),
        //     'receipt_number'  => $row['receipt_number'] ?? null,
        //     'collected_by'    => $row['collected_by'] ?? null,
        //     'served_by'       => $row['served_by'] ?? null,
        //     'customer'        => $row['customer'] ?? null,
        //     'customer_phone'  => $row['customer_phone'] ?? null,
        //     'items'           => $row['items'] ? (int) $row['items'] : null,
        //     'payment_method'  => $row['payment_method'] ?? null,
        //     'other_note'      => $row['other_note_(optional)'] ?? null,
        // ]);

        $dataTransaction = [
            'outlet_id' => $outlet->id ?? null,
            'user_id' => $user->id,
            'customer_id' => null,
            'total' => $total,
            'nominal_bayar' => $nominalBayar,
            'category_payment_id' => $categoryPaymentId,
            'nama_tipe_pembayaran' => $namaTipePembayaran,
            'change' => $change,
            'tipe_pembayaran' => $tipePembayaran,
            'total_pajak' => $tax,
            'total_modifier' => 0,
            'total_diskon' => $discount,
            'diskon_all_item' => json_encode([]),
            'rounding_amount' => 0,
            'tanda_rounding' => null,
            'patty_cash_id' => 1,
            'catatan' => null,
            'potongan_point' => 0,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'open_bill_id' => null
        ];

        // $transaction = Transaction::create($dataTransaction);

        $textItem = "Tiramisu Cream Latte, Cookies, Danish Creamcheese, Magic, Custom Amount";

        $items = array_map('trim', explode(',', $textItem));

        foreach ($items as $item) {
            preg_match('/^(.+?)(?:\s*\((.*?)\))?(?:\s*x\s*(\d+))?$/i', trim($item), $matches);

            $name     = trim($matches[1] ?? '');
            $modifier = $matches[2] ?? null;
            $quantity = isset($matches[3]) ? (int)$matches[3] : 1;

            $isCustom = strcasecmp($name, 'Custom Amount') === 0; // true jika nama persis "Custom Amount"

            $findProduct = Product::where('name', 'LIKE', '%' . $name . '%')->first();

            $backupCategory = Category::firstOrCreate(
                ['name' => 'Backup'],
            [
                'name' => 'Backup',
                'status' => true
            ]);


            if(!$findProduct) {
                // Jika produk tidak ditemukan, maka buat product baru
                $dataProduct = [
                    "name" => $name,
                    'category_id' => $backupCategory->id,
                    // 'harga_jual' => getAmount($validatedData['harga_jual']),
                    'harga_modal' => getAmount($validatedData['harga_modal']),
                    // 'stock' => $validatedData['stock'],
                    'outlet_id' => $outlet,
                    'status' => $validatedData['status'],
                    'description' => $validatedData['description'],
                    'exclude_tax' => $validatedData['exclude_tax'] ?? false
                ];

                if ($request->hasFile('photo')) {
                    $dataProduct['photo'] = $request->file('photo')->store('product');
                }

                $product = Product::create($dataProduct);
                continue;
            }
            dd($name, $modifier, $quantity, $isCustom, $findProduct);
            $idProduct = $request->idProduct[$x] == 'null' ? null : intval($request->idProduct[$x]);
            $dataProduct = [
                'product_id' => $idProduct,
                'discount_id' => $request->discount_id[$x],
                'modifier_id' => $request->modifier_id[$x],
                'harga' => $request->harga[$x],
                'variant_id' => ($request->idVariant[$x] == 'null' || $request->idVariant[$x] == 'undefined') ? null : $request->idVariant[$x],
                'promo_id' => $request->promo_id[$x],
                'reward_item' => $request->reward[$x] == "true" ? true : false,
                'transaction_id' => $transaction->id,
                'catatan' => isset($request->catatan[$x]) ? $request->catatan[$x] : '',
                'sales_type_id' => ($request->sales_type[$x] == 'null' || $request->sales_type[$x] == 'undefined') ?  null : $request->sales_type[$x],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];

            // dd($billId, $listIdItemOpenBill, $request->idProduct);
            if($billId && count($listIdItemOpenBill)){
                // $dataProduct['item_open_bill_id'] = $listIdItemOpenBill[$x];
                $dataProduct['item_open_bill_id'] = $billId;
            }

            TransactionItem::insert($dataProduct);
        }
    }

    /**
     * Chunk size to keep memory usage low.
     */
    public function chunkSize(): int
    {
        return 1000;
    }
}
