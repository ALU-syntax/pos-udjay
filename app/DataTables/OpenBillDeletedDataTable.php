<?php

namespace App\DataTables;

use App\Models\OpenBill;
use App\Models\OpenBillDeleted;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class OpenBillDeletedDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
        ->addColumn('status', function ($row) {
                $status = $row->deleted_at ? "<span class='badge badge-success'>Sudah Dibayar</span></br>" : "<span class='badge badge-danger'>Belum Dibayar</span></br>";
                return $status;
            })
            ->addColumn('created_at', function ($row) {
                return Carbon::parse($row->created_at)->format('H:i');
            })
            ->addColumn('user_id', function ($row) {
                return $row->user->name;
            })
            ->addColumn('outlet', function($row) {
                $listOutlet = "<span class='badge badge-primary'>{$row->outlet->name} </span>";
                return $listOutlet; // Menampilkan nama peran dari relasi
            })
            ->addColumn('items', function ($row) {
                $items = $row->itemWithTrashed;
                $itemWithProduct = $items->load(['product']);
                $itemText = '';
                foreach ($itemWithProduct as $item) {
                    if($item->product){
                        $itemText .= "<span>{$item->product->name}, </span>";
                    }else{
                        $itemText .= "<span>custom, </span>";
                    }
                }

                if(count($itemWithProduct)){
                    $result = substr($itemText, 0, -9);
                }else{
                    $result = '-';
                }

                return $result;
            })
            ->addColumn('total', function ($row) {
                $items = $row->itemWithTrashed;
                $total = 0;

                foreach ($items as $item) {
                    $harga = $item->harga;
                    $itemTerbayar = $item->qty_terbayar ? $item->qty_terbayar : 0;
                    $qty = $item->quantity + $itemTerbayar;

                    $hargaAkhir = $harga * $qty;

                    $total += $hargaAkhir;
                }

                return formatRupiah(intval($total), "Rp. ");
            })
            ->rawColumns(['items', 'status', 'outlet'])
            ->setRowId('id')
            ->setRowAttr([
                'onclick' => function($row){
                    return "handleClickRowOpenBill({$row->id})";
                }
            ]);
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(OpenBill $model): QueryBuilder
    {
        $query = $model->newQuery()->withTrashed()->with(['outlet', 'item'])->whereNotNull('delete_permanen');
        if ($this->request()->has('outlet') && $this->request()->get('outlet') != '') {
            if ($this->request()->get('outlet') == 'all') {
                $query->whereIn('outlet_id', json_decode(auth()->user()->outlet_id));
            } else {
                $query->where('outlet_id', $this->request()->get('outlet'));
            }
        } elseif ($this->request()->has('outlet') && $this->request()->get('outlet') == 'all') {
            $query->whereIn('outlet_id', json_decode(auth()->user()->outlet_id));
        } else {
            $dataOutletUser = json_decode(auth()->user()->outlet_id);
            // $query->where('outlet_id', $dataOutletUser[0]);
            $query->whereIn('outlet_id', $dataOutletUser);
        }

        $dataOpenBill = $query->get();
        $total = 0;

        foreach($dataOpenBill as $openBill){
            foreach($openBill->item as $item){
                $harga = $item->harga;
                $itemTerbayar = $item->qty_terbayar ? $item->qty_terbayar : 0;
                $qty = $item->quantity + $itemTerbayar;

                $hargaAkhir = $harga * $qty;

                $total += $hargaAkhir;
            }
        }

        // Simpan total ke dalam session atau variabel statis untuk diakses di frontend
        session(['total_transaction' => $total]);

        return $query;

    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('openbilldeleted-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax(route('report/openbill/deleted/data'))
                    // ->dom('Bfrtip')
                    ->orderBy(1)
                    ->selectStyleSingle()
                    ->buttons([
                        Button::make('excel'),
                        Button::make('csv'),
                        Button::make('pdf'),
                        Button::make('print'),
                        Button::make('reset'),
                        Button::make('reload')
                    ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('status')->title("Status"),
            Column::make('name')->title("Nama"),
            Column::make('created_at')->title("Time"),
            Column::make('outlet')->title("Outlet"),
            Column::make('user_id')->title('Collected By'),
            Column::make('items')->title('Items'),
            Column::make('total')->title('Total Price'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'OpenBillDeleted_' . date('YmdHis');
    }
}
