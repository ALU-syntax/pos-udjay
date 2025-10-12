<?php

namespace App\Imports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\ShouldQueue;

class BackupImport implements OnEachRow, WithHeadingRow, WithChunkReading, ShouldQueue, SkipsOnFailure
{
    use SkipsErrors;

    /** @var array<int, array<string, mixed>> */
    protected array $buffer = [];

    /** @var int jumlah baris per flush upsert */
    protected int $bufferSize = 1000;

    /** @var string timezone app */
    protected string $tz = 'Asia/Jakarta';

    public function __construct()
    {
        // Kurangi overhead query logging saat import besar
        DB::disableQueryLog();
    }

    public function onRow(Row $row): void
    {
        $r = $row->toArray();

        // Normalisasi tanggal+jam jadi Carbon (support .xlsx serial dan .csv string)
        $dt = $this->normalizeExcelOrCsvDateTime($r['date'] ?? null, $r['time'] ?? null, $this->tz);

        // Normalisasi angka (CSV sering berupa string "00.00")
        $gross      = $this->num($r['gross_sales']   ?? 0);
        $discounts  = $this->num($r['discounts']     ?? 0);
        $refunds    = $this->num($r['refunds']       ?? 0);
        $netSales   = $this->num($r['net_sales']     ?? 0);
        $gratuity   = $this->num($r['gratuity']      ?? 0);
        $tax        = $this->num($r['tax']           ?? 0);
        $totalColl  = $this->num($r['total_collected'] ?? $r['total_amount'] ?? 0);
        $totalAmt   = $this->num($r['total_amount']  ?? $r['total_collected'] ?? 0);

        // Natural key terbaik dari datamu tampaknya adalah nomor struk
        $receipt = $r['receipt_number'] ?? null;

        // Siapkan 1 baris payload (SES UAIKAN dengan kolom tabelmu)
        // Saran: kalau tabel transactions kamu berbeda nama kolomnya, mapping di sini.
        $this->buffer[] = [
            'receipt_number'     => $receipt,                             // <— pastikan ada UNIQUE index untuk ini
            'outlet'             => $r['outlet'] ?? null,
            'event_type'         => $r['event_type'] ?? null,
            'reason_of_refund'   => $r['reason_of_refund'] ?? null,
            'collected_by'       => $r['collected_by'] ?? null,
            'served_by'          => $r['served_by'] ?? null,
            'customer'           => $r['customer'] ?? null,
            'customer_phone'     => $r['customer_phone'] ?? null,
            'items'              => $r['items'] ?? null,                  // jika nantinya dipecah ke table items, proses terpisah
            'payment_method'     => $r['payment_method'] ?? null,

            'gross_sales'        => $gross,
            'discounts'          => $discounts,
            'refunds'            => $refunds,
            'net_sales'          => $netSales,
            'gratuity'           => $gratuity,
            'tax'                => $tax,
            'total_collected'    => $totalColl,
            'total_amount'       => $totalAmt,

            'created_at'         => $dt,    // simpan waktu transaksi ke created_at
            'updated_at'         => $dt,
        ];

        if (count($this->buffer) >= $this->bufferSize) {
            $this->flush();
        }
    }

    public function __destruct()
    {
        $this->flush();
    }

    /** Upsert buffer ke DB, 1 query untuk banyak baris */
    protected function flush(): void
    {
        if (!$this->buffer) return;

        // Pastikan tabel & kolom target sesuai proyekmu
        // Gunakan upsert dengan unique key 'receipt_number' agar tidak duplikat.
        DB::table('transactions')->upsert(
            $this->buffer,
            ['receipt_number'], // conflict key (UNIQUE INDEX disarankan)
            [
                // Kolom yang akan di-update jika sudah ada
                'outlet', 'event_type', 'reason_of_refund', 'collected_by', 'served_by',
                'customer', 'customer_phone', 'items', 'payment_method',
                'gross_sales','discounts','refunds','net_sales','gratuity','tax',
                'total_collected','total_amount','updated_at',
            ]
        );

        $this->buffer = [];
    }

    public function chunkSize(): int
    {
        // 500–1000 baris per chunk umumnya ideal
        return 500;
    }

    /** Konversi angka string CSV → float/int (atau tetap numerik apa adanya) */
    protected function num($v): float|int
    {
        if (is_null($v) || $v === '') return 0;
        if (is_numeric($v)) return $v + 0;
        // "1.234,56" → 1234.56 (kalau CSV lokal pakai koma)
        $v = str_replace(['.'], [''], $v);     // hilangkan pemisah ribuan
        $v = str_replace([','], ['.'], $v);    // koma jadi titik desimal
        return is_numeric($v) ? $v + 0 : 0;
    }

    /**
     * Parser tanggal fleksibel:
     * - XLSX: date sebagai serial number, time sebagai fraksi hari
     * - CSV: date "dd/mm/yy" atau "dd/mm/yyyy", time "HH:mm[:ss]" atau "HH.mm.ss"
     */
    protected function normalizeExcelOrCsvDateTime($date, $time = null, string $tz = 'Asia/Jakarta'): ?Carbon
    {
        if ($date === null || $date === '') return null;

        // XLSX serial (integer)
        if (is_numeric($date)) {
            $dt = Carbon::create(1899,12,30,0,0,0,$tz)->addDays((int)$date);
        } else {
            // Bersihkan karakter aneh
            $dateStr = (string)$date;
            $dateStr = str_replace("\xEF\xBB\xBF", '', $dateStr);
            $dateStr = preg_replace('/[\x00-\x1F\x7F\xC2\xA0]/u', '', $dateStr);
            $dateStr = trim($dateStr);

            // Jika kolom date ikut memuat waktu, ekstrak
            $tmp = str_replace('.', ':', $dateStr);
            if (preg_match('/\b(\d{1,2}:\d{2}(?::\d{2})?)\b/', $tmp, $m)) {
                $time = $time ?: $m[1];
                $dateStr = trim(str_replace($m[0], '', $tmp));
            }

            // Normalisasi pemisah tanggal
            $dateStr = preg_replace('/[.\-]/', '/', $dateStr);

            // Validasi pola d/m/y(yy)
            if (!preg_match('/^\d{1,2}\/\d{1,2}\/(\d{2}|\d{4})$/', $dateStr)) {
                throw new \InvalidArgumentException("Tanggal tidak sesuai pola d/m/y atau d/m/Y: `{$dateStr}`");
            }

            $yearPart = explode('/', $dateStr)[2] ?? '';
            $fmt = (strlen($yearPart) === 2) ? '!d/m/y' : '!d/m/Y';

            $dt = Carbon::createFromFormat($fmt, $dateStr, $tz);
        }

        // TIME
        if ($time !== null && $time !== '') {
            if (is_numeric($time)) {
                $dt->addSeconds((int) round(((float)$time) * 86400));
            } else {
                $t = (string)$time;
                $t = str_replace("\xEF\xBB\xBF", '', $t);
                $t = preg_replace('/[\x00-\x1F\x7F\xC2\xA0]/u', '', $t);
                $t = trim($t);
                $t = str_replace('.', ':', $t);

                foreach (['H:i:s','H:i'] as $f) {
                    try {
                        $pt = Carbon::createFromFormat('!'.$f, $t, $tz);
                        $dt->setTime($pt->hour, $pt->minute, $pt->second);
                        break;
                    } catch (\Throwable $e) {}
                }
            }
        }

        return $dt;
    }
}
