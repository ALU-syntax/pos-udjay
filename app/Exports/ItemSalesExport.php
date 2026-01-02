<?php

namespace App\Exports;

use App\Models\VariantProduct;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ItemSalesExport implements FromQuery, WithHeadings, WithMapping, WithChunkReading, ShouldQueue
{
    use Exportable;

    public function __construct(
        public readonly ?string $from,
        public readonly ?string $to,
        public readonly array $outletIds = [],
    ) {}

    public function query()
    {
        $startDate = $this->from
            ? Carbon::parse($this->from)->startOfDay()
            : Carbon::now()->startOfDay();

        $endDate = $this->to
            ? Carbon::parse($this->to)->endOfDay()
            : Carbon::now()->endOfDay();

        $query = VariantProduct::query()
            ->with([
                'itemTransaction' => function ($transaction) use ($startDate, $endDate) {
                    $transaction->whereBetween('created_at', [$startDate, $endDate]);
                },
                'product.outlet',
                'product.category' => function ($category) {
                    $category->withTrashed();
                },
            ]);

        if (!empty($this->outletIds)) {
            $query->whereHas('product', function ($q) {
                $q->whereIn('outlet_id', $this->outletIds);
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
        $itemSold = $row->itemTransaction->count();

        $grossSales = $itemSold * $row->harga;

        $totalDiscount = 0;
        foreach ($row->itemTransaction as $itemTransaction) {
            if (!$itemTransaction->discount_id) {
                continue;
            }

            $dataDiscount = json_decode($itemTransaction->discount_id);
            if (!is_array($dataDiscount)) {
                continue;
            }

            foreach ($dataDiscount as $discount) {
                $totalDiscount += $discount->result ?? 0;
            }
        }

        $netSales    = $grossSales - $totalDiscount;
        $grossProfit = $netSales; // kalau mau lebih akurat bisa minus modal juga
        $grossMargin = $itemSold ? '100%' : '0%';

        // Kalau multi outlet, tulis nama outlet di belakang
        $isAllOutletsExport = empty($this->outletIds);

        // dd($this->outletIds, $isAllOutletsExport);
        $name = $isAllOutletsExport
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
        // Sesuaikan dengan kapasitas server
        return 500;
    }
}
