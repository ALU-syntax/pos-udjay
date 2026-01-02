<?php

// app/Exports/ItemSalesExport.php

namespace App\Exports;

use App\Models\VariantProduct;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
// use Maatwebsite\Excel\Concerns\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\Exportable;

class ItemSalesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithCustomChunkSize, ShouldQueue
{
    use Exportable;

    protected $startDate;
    protected $endDate;
    protected $outlet;

    public function __construct($startDate, $endDate, $outlet)
    {
        $this->startDate = $startDate;
        $this->endDate   = $endDate;
        $this->outlet    = $outlet;
        // dd($startDate, $endDate, $outlet);
    }

    public function query()
    {
        $query = VariantProduct::query()
            ->with([
                'itemTransaction' => function ($transaction) {
                    $transaction->whereBetween('created_at', [$this->startDate, $this->endDate]);
                },
                'product.outlet',
                'product.category' => function ($category) {
                    $category->withTrashed();
                },
            ]);

        if ($this->outlet !== 'all') {
            $query->whereHas('product', function ($q) {
                $q->where('outlet_id', $this->outlet);
            });
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Name',
            'Category',
            'Item Sold',
            'Gross Sales',
            'Discounts',
            'Net Sales',
            'Gross Profit',
            'Gross Margin',
        ];
    }

    public function map($row): array
    {
        // Hitung sama seperti di DataTables
        $itemSold = $row->itemTransaction->count();

        $grossSales = $itemSold * $row->harga;

        $totalDiscount = 0;
        foreach ($row->itemTransaction as $itemTransaction) {
            if (!$itemTransaction->discount_id) continue;

            $dataDiscount = json_decode($itemTransaction->discount_id);
            if (!is_array($dataDiscount)) continue;

            foreach ($dataDiscount as $discount) {
                $totalDiscount += $discount->result ?? 0;
            }
        }

        $netSales    = $grossSales - $totalDiscount;
        $grossProfit = $netSales; // atau pakai harga_modal kalau mau lebih akurat
        $grossMargin = $itemSold ? '100%' : '0%';

        // dd($row);

        $name = ($this->outlet === 'all')
            ? (($row->name == $row->product->name)
                ? $row->product->name . " (" . $row->product->outlet->name . ")"
                : $row->product->name . ' - ' . $row->name . " (" . $row->product->outlet->name . ")")
            : (($row->name == $row->product->name)
                ? $row->product->name
                : $row->product->name . ' - ' . $row->name);

        return [
            $name,
            optional($row->product->category)->name,
            $itemSold,
            $grossSales,
            $totalDiscount,
            $netSales,
            $grossProfit,
            $grossMargin,
        ];
    }

    public function chunkSize(): int
    {
        return 2000; // aman utk data besar
    }

}
