<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\CategoryPayment;
use App\Models\Outlets;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Taxes;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use App\Models\VariantProduct;
use Carbon\Carbon;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;

class BackupImport_ implements OnEachRow, WithHeadingRow, WithChunkReading, WithCustomCsvSettings
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

        $receiptNumber = $row['receipt_number'];
        $checkApakahDataSudahAda = Transaction::where('receipt_number', $receiptNumber)
            ->first();

        if($outlet && !$checkApakahDataSudahAda){

            // // // ---------TANGGAL & waktu---------
            // $dateSerial = $row['date']; // 45656
            // $timeSerial = $row['time']; // 0.87795138888889

            // // 1. Ubah date serial ke timestamp
            // $unixDate = ($dateSerial - 25569) * 86400; // 25569 = offset 1970-01-01
            // $datePart = gmdate('Y-m-d', $unixDate);

            // // 2. Ubah time fraction ke jam:menit:detik
            // $secondsInDay = 86400;
            // $unixTime = $timeSerial * $secondsInDay;
            // $timePart = gmdate('H:i:s', $unixTime);
            // // 3. Gabungkan
            // $combined = $datePart . ' ' . $timePart;
            // // 4. Buat Carbon instance
            // $carbonDateTime = Carbon::parse($combined);

            // SIMPLE DATETIME
            // $dt = $this->excelDateTime($row['date'], $row['time']);
            $dt = $this->normalizeExcelOrCsvDateTime($row['date'] ?? null, $row['time'] ?? null);

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
                if($checkTipePembayaran){
                    $categoryPaymentId = $checkTipePembayaran->categoryPayment->id;
                    $tipePembayaran = $checkTipePembayaran->id;
                    $namaTipePembayaran = $checkTipePembayaran->name;
                }else{
                    $newCategoryPayment = CategoryPayment::firstOrCreate([
                        'name' => 'Backup'
                    ],[
                        'name' => 'Backup',
                        'status' => false
                    ]);

                    $newPayment = Payment::create([
                        'name' => $paymentMethod,
                        'category_payment_id' => $newCategoryPayment->id,
                        'status' => false
                    ]);

                    $categoryPaymentId = $newCategoryPayment->id;
                    $tipePembayaran = $newPayment->id;
                    $namaTipePembayaran = $paymentMethod;
                }
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

            // dd($row, $dt->diffForHumans(), $carbonDateTime->diffForHumans());
            // ---------PAJAK----------
            $discount = $row['discounts'] ?? 0;

            // dd($dt->diffForHumans(), $carbonDateTime->diffForHumans());
            // dd($dt->diffForHumans());
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
                'created_at' => $dt,
                'updated_at' => $dt,
                'open_bill_id' => null,
                'receipt_number' => $row['receipt_number'] ?? null,
            ];

            $transaction = Transaction::create($dataTransaction);

            $items = array_map('trim', explode(',', $row['items']));

            $tmpData = [];
            foreach ($items as $index => $item) {
                preg_match('/^(.+?)(?:\s*\((.*?)\))?(?:\s*x\s*(\d+))?$/i', trim($item), $matches);

                $name     = trim($matches[1] ?? '');
                $variant = $matches[2] ?? null;
                $quantity = isset($matches[3]) ? (int)$matches[3] : 1;

                $dataItems = [
                    'name' => $name,
                    'variant' => $variant,
                    'quantity' => $quantity
                ];

                array_push($tmpData, $dataItems);
                $isCustom = strcasecmp($name, 'Custom Amount') === 0; // true jika nama persis "Custom Amount"

                $findProduct = Product::where('name', 'LIKE', '%' . $name . '%')
                    ->where('outlet_id', $outlet->id)
                    ->first();

                $backupCategory = Category::firstOrCreate(
                    ['name' => 'Backup'],
                [
                    'name' => 'Backup',
                    'status' => true
                ]);

                $idProduct = null;
                $idVariant = null;

                $nameVariant = $variant ? $variant : null;

                if(!$findProduct) {
                    // Jika produk tidak ditemukan, maka buat product baru
                    $dataProduct = [
                        "name" => $name,
                        'category_id' => $backupCategory->id,
                        // 'harga_jual' => getAmount($validatedData['harga_jual']),
                        'harga_modal' => 0,
                        // 'stock' => $validatedData['stock'],
                        'outlet_id' => $outlet->id,
                        'status' => false,
                        'description' => "",
                        'exclude_tax' => false,
                    ];

                    $product = Product::create($dataProduct);

                    $idProduct = $product->id;

                    if($nameVariant){
                        $createVariant = VariantProduct::create([
                            'name' => $nameVariant,
                            'harga' => 0,
                            'stok' => 1000,
                            'product_id' => $product->id, // Gunakan ID langsung dari instance ModifierGroup
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);

                        $idVariant = $createVariant->id;
                    }else{
                        $createVariant = VariantProduct::create([
                            'name' => $name,
                            'harga' => 0,
                            'stok' => 1000,
                            'product_id' => $product->id, // Gunakan ID langsung dari instance ModifierGroup
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);

                        $idVariant = $createVariant->id;
                    }
                }else{
                    $idProduct = $findProduct->id;
                    if($nameVariant){
                        $checkVariantExist = VariantProduct::where('name', 'LIKE', '%'.$nameVariant.'%')
                            ->where('product_id',$findProduct->id)
                            ->first();

                        if(!$checkVariantExist){
                            $newVariantProduct = VariantProduct::create([
                                'name' => $nameVariant,
                                'harga' => 0,
                                'stok' => 1000,
                                'product_id' => $findProduct->id, // Gunakan ID langsung dari instance ModifierGroup
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);

                            $idVariant = $newVariantProduct->id;
                        }else{
                            $idVariant = $checkVariantExist->id;
                        }
                    }
                }

                $dataProduct = [
                    'product_id' => $idProduct,
                    'discount_id' => null,
                    'modifier_id' => null,
                    'harga' => 0,
                    'variant_id' => $idVariant,
                    'promo_id' => null,
                    'reward_item' => false,
                    'transaction_id' => $transaction->id,
                    'catatan' => '',
                    'sales_type_id' => null,
                    'created_at' => $dt,
                    'updated_at' => $dt,
                ];

                TransactionItem::insert($dataProduct);
            }
        }
    }

    function excelDateTime(string|int|float|null $dateSerial, string|int|float|null $timeSerial = 0, string $tz = 'Asia/Jakarta'): ?Carbon
    {
        if ($dateSerial === null || $dateSerial === '') return null;

        // Base Excel (sistem 1900): 1899-12-30
        $dt = Carbon::create(1899, 12, 30, 0, 0, 0, $tz)->addDays((int)$dateSerial);

        // Tambahkan waktu jika ada (fraksi hari)
        if ($timeSerial !== null && $timeSerial !== '') {
            $seconds = (int) round(((float) $timeSerial) * 86400); // 24*60*60
            $dt->addSeconds($seconds);
        }

        return $dt; // ini sudah di TZ lokal; ubah ke UTC jika perlu ->clone()->setTimezone('UTC')
    }

    /**
     * Parse date (XLSX serial atau CSV text) + optional time jadi Carbon.
     * Menangani karakter tersembunyi, CRLF, BOM, dan format waktu dengan titik.
     */
    function normalizeExcelOrCsvDateTime($date, $time = null, $tz = 'Asia/Jakarta'): ?Carbon
    {
        if ($date === null || $date === '') return null;

        // XLSX: serial number (hari sejak 1899-12-30)
        if (is_numeric($date)) {
            $dt = Carbon::create(1899, 12, 30, 0, 0, 0, $tz)->addDays((int) $date);
        } else {
            // --- CSV teks: bersihkan karakter "tak terlihat" ---
            $dateStr = (string) $date;

            // hapus BOM, \r, \n, \t, NBSP, dan spasi berlebih
            $dateStr = str_replace("\xEF\xBB\xBF", '', $dateStr); // BOM
            $dateStr = preg_replace('/[\x00-\x1F\x7F\xC2\xA0]/u', '', $dateStr); // control + NBSP
            $dateStr = trim($dateStr);

            // Jika kolom date ternyata berisi "date + time", pisahkan:
            // ganti titik jadi kolon dulu, agar "21.32.11" -> "21:32:11"
            $tmp = preg_replace('/\./', ':', $dateStr);
            if (preg_match('/\b(\d{1,2}:\d{2}(?::\d{2})?)\b/', $tmp, $m)) {
                // time ketemu di kolom date, pindahkan ke $time
                $time = $time ?: $m[1];
                // hapus bagian waktu dari dateStr
                $dateStr = trim(str_replace($m[0], '', $tmp));
            }

            // Normalisasi pemisah tanggal ke slash (jaga-jaga kalau pakai '-' atau '.')
            $dateStr = preg_replace('/[.\-]/', '/', $dateStr);

            // Validasi pola tanggal: dd/mm/yy atau dd/mm/YYYY
            if (!preg_match('/^\d{1,2}\/\d{1,2}\/(\d{2}|\d{4})$/', $dateStr)) {
                // Kalau masih gagal, lempar exception yang lebih informatif
                throw new \InvalidArgumentException("Tanggal tidak sesuai pola d/m/y atau d/m/Y: `{$dateStr}`");
            }

            // Tentukan format berdasar panjang tahun di bagian ke-3
            $yearPart = explode('/', $dateStr)[2];
            $fmt = (strlen($yearPart) === 2) ? '!d/m/y' : '!d/m/Y';

            // Pakai createFromFormat yang “strict” (diawali '!' untuk reset field lain)
            $dt = Carbon::createFromFormat($fmt, $dateStr, $tz);
        }

        // ---- TIME (opsional) ----
        if ($time !== null && $time !== '') {
            if (is_numeric($time)) {
                // XLSX fraksi hari
                $dt->addSeconds((int) round(((float) $time) * 86400));
            } else {
                $t = (string) $time;
                // bersihkan & normalisasi
                $t = str_replace("\xEF\xBB\xBF", '', $t);
                $t = preg_replace('/[\x00-\x1F\x7F\xC2\xA0]/u', '', $t);
                $t = trim($t);
                $t = str_replace('.', ':', $t); // "21.32.11" -> "21:32:11"

                // coba beberapa format umum
                foreach (['H:i:s', 'H:i'] as $f) {
                    try {
                        $pt = Carbon::createFromFormat('!' . $f, $t, $tz);
                        $dt->setTime($pt->hour, $pt->minute, $pt->second);
                        break;
                    } catch (\Throwable $e) {
                        // coba format berikutnya
                    }
                }
            }
        }

        return $dt;
    }

    /**
     * Chunk size to keep memory usage low.
     */
    public function chunkSize(): int
    {
        return 1000;
    }
}
