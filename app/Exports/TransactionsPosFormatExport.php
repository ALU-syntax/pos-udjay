<?php

namespace App\Exports;

// use Illuminate\Contracts\Queue\ShouldQueue;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithChunkSize;
// use Maatwebsite\Excel\Concerns\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class TransactionsPosFormatExport implements
    FromQuery, WithHeadings, WithMapping, WithCustomChunkSize, ShouldQueue, WithColumnFormatting
{
    use Exportable;

    public function __construct(
        public ?string $from = null,         // 'YYYY-MM-DD'
        public ?string $to   = null,         // 'YYYY-MM-DD'
        public array $outletIds = []         // [1,2,3]
    ) {}

    public function query()
    {
        // 1) Total harga item per transaksi (untuk Gross Sales)
        $sumItems = DB::table('transaction_items')
            ->selectRaw('transaction_id, SUM(COALESCE(harga,0)) AS items_total')
            ->groupBy('transaction_id');

            // dd($this->from, $this->to, $this->outletIds);


        // 2) Qty per (product, variant) → untuk string Items
        $g0 = DB::table('transaction_items as ti')
            ->leftJoin('products as p', 'p.id', '=', 'ti.product_id')
            ->leftJoin('variant_products as vp', 'vp.id', '=', 'ti.variant_id')
            ->selectRaw("
                ti.transaction_id,
                COALESCE(p.name,'')  AS pname,
                COALESCE(vp.name,'') AS vname,
                COUNT(*)             AS qty
            ")
            ->groupBy('ti.transaction_id','pname','vname');

        // 3) Gabungkan jadi "Nama (Varian) x Qty, ... "
        $itemsConcat = DB::query()->fromSub($g0, 'g0')
            ->selectRaw("
                g0.transaction_id,
                GROUP_CONCAT(
                  CONCAT(
                    g0.pname,
                    CASE WHEN g0.vname='' THEN '' ELSE CONCAT(' (', g0.vname, ')') END,
                    ' x ', g0.qty
                  )
                  ORDER BY g0.pname SEPARATOR ', '
                ) AS items_str
            ")
            ->groupBy('g0.transaction_id');

        // 4) Query utama transaksi
        return DB::table('transactions as t')
            ->leftJoin('outlets as o', 'o.id', '=', 't.outlet_id')
            ->leftJoin('users as u', 'u.id', '=', 't.user_id')
            ->leftJoin('customers as c', 'c.id', '=', 't.customer_id')
            ->leftJoin('category_payments as cp', 'cp.id', '=', 't.category_payment_id')
            ->leftJoinSub($sumItems, 'si', 'si.transaction_id', '=', 't.id')
            ->leftJoinSub($itemsConcat, 'ic', 'ic.transaction_id', '=', 't.id')
            ->when($this->outletIds, fn($q) => $q->whereIn('t.outlet_id', $this->outletIds))
            ->when($this->from && $this->to, fn($q) => $q->whereBetween('t.created_at', [
                $this->from.' 00:00:00', $this->to.' 23:59:59'
            ]))
            ->orderBy('t.id')
            ->select([
                't.id',
                't.created_at',
                'o.name as outlet_name',
                'u.name as collected_by',
                'c.name as customer_name',
                'c.telfon as customer_phone',
                't.total_modifier',
                't.total_diskon',
                't.diskon_all_item',
                't.total_pajak',
                't.rounding_amount',
                't.catatan',
                't.nominal_bayar',
                DB::raw("COALESCE(t.nama_tipe_pembayaran, cp.name) AS payment_method"),
                DB::raw('COALESCE(si.items_total,0) AS items_total'),
                DB::raw('COALESCE(ic.items_str, "") AS items_str'),
            ]);

    }

    public function headings(): array
    {
        return [
            'Outlet',
            'Date',
            'Time',
            'Gross Sales',
            'Discounts',
            'Refunds',
            'Net Sales',
            'Gratuity',
            'Tax',
            'Total Collected',
            'Total Amount',
            'Other Note (Optional)',
            'Collected By',
            'Customer',
            'Customer Phone',
            'Items',
            'Payment Method',
        ];
    }

    public function columnFormats(): array
    {
        // Pastikan angka tidak dibaca Excel sebagai "time"
        return [
            'D' => NumberFormat::FORMAT_NUMBER,      // Gross Sales
            'E' => NumberFormat::FORMAT_NUMBER,      // Discounts
            'F' => NumberFormat::FORMAT_NUMBER,      // Refunds
            'G' => NumberFormat::FORMAT_NUMBER,      // Net Sales
            'H' => NumberFormat::FORMAT_NUMBER,      // Gratuity
            'I' => NumberFormat::FORMAT_NUMBER,      // Tax
            'J' => NumberFormat::FORMAT_NUMBER,      // Total Collected
            'K' => NumberFormat::FORMAT_NUMBER,      // Total Amount
        ];
    }

    public function map($r): array
    {
        // Parse JSON totals (tax & diskon_all_item)
        $sumJsonTotals = function ($json) {
            if (!$json) return 0;
            try {
                $arr = is_array($json) ? $json : json_decode($json, true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($arr)) return 0;
                $sum = 0;
                foreach ($arr as $item) {
                    $sum += (float)($item['total'] ?? 0);
                }
                return $sum;
            } catch (\Throwable $e) {
                return 0;
            }
        };

        $grossSales = (float)$r->items_total + (float)($r->total_modifier ?? 0);
        $discounts  = (float)($r->total_diskon ?? 0) + $sumJsonTotals($r->diskon_all_item);
        $refunds    = 0.0; // ganti sesuai logika retur/void kamu
        $netSales   = max(0, $grossSales - $discounts - $refunds);
        $tax        = $sumJsonTotals($r->total_pajak);
        $gratuity   = 0.0; // jika ada tips/gratuity, isi dari kolom kamu
        $totalAmount    = $netSales + $tax + (float)($r->rounding_amount ?? 0);
        $totalCollected = (float)($r->nominal_bayar ?? $totalAmount);

        $dt = \Illuminate\Support\Carbon::parse($r->created_at)->timezone('Asia/Jakarta');

        return [
            $r->outlet_name,
            $dt->toDateString(),            // Date
            $dt->format('H:i:s'),           // Time
            $grossSales,
            $discounts,
            $refunds,
            $netSales,
            $gratuity,
            $tax,
            $totalCollected,
            $totalAmount,
            $r->catatan,
            $r->collected_by,
            $r->customer_name,
            $r->customer_phone,
            $r->items_str,
            $r->payment_method,
        ];
    }

    public function chunkSize(): int
    {
        return 2000; // aman utk data besar
    }
}
