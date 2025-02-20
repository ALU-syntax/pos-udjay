<?php

namespace App\DataTables;

use App\Models\Outlets;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class TransactionsDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('outlet_id', function ($row) {
                return $row->outlet->name;
            })
            ->addColumn('created_at', function ($row) {
                return Carbon::parse($row->created_at)->format('H:i');
            })
            ->addColumn('user_id', function ($row) {
                return $row->user->name;
            })
            ->addColumn('items', function ($row) {
                $items = $row->itemTransaction;
                $itemWithProduct = $items->load(['product']);
                $itemText = '';
                foreach ($itemWithProduct as $item) {
                    if($item->product){
                        $itemText .= "<span>{$item->product->name}, </span>";
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
                return formatRupiah(intval($row->total), "Rp. ");
            })
            ->rawColumns(['items'])
            ->setRowId('id')
            ->setRowAttr([
                'onclick' => function($row){
                    return "handleClickRowTransaction({$row->id})";
                }
            ]);
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Transaction $model): QueryBuilder
    {
        $query = $model->newQuery()->with(['outlet']);
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
            $query->where('outlet_id', $dataOutletUser[0])->whereDate('created_at', Carbon::today());
            
        }

        // Filter berdasarkan rentang tanggal untuk kolom created_at  
        if ($this->request()->has('date') && $this->request()->get('date') != '') {
            $dates = explode(' - ', $this->request()->get('date'));
            if (count($dates) == 2) {
                // Mengonversi format tanggal dari input  
                $startDate = Carbon::createFromFormat('Y/m/d', trim($dates[0]))->startOfDay();
                $endDate = Carbon::createFromFormat('Y/m/d', trim($dates[1]))->endOfDay();

                // Menggunakan whereBetween untuk memfilter berdasarkan created_at  
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }
        }

        // Hitung total  
        $total = $query->sum('total');

        // Simpan total ke dalam session atau variabel statis untuk diakses di frontend  
        session(['total_transaction' => $total]);

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        $userOutlet = json_decode(auth()->user()->outlet_id);
        $dataOutlet = Outlets::find($userOutlet[0]);
        
        return $this->builder()
            ->setTableId('transactions-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            //->dom('Bfrtip')
            ->orderBy(1)
            ->selectStyleSingle()
            ->addTableClass('table-striped')
            ->buttons([
                Button::make('excel'),
                Button::make('csv'),
                Button::make('pdf'),
                Button::make('print'),
                Button::make('reset'),
                Button::make('reload')
            ])
            ->parameters([
                'drawCallback' => 'function(settings) {  
                    var api = this.api();  
                    var data = api.rows({page: "current"}).data();  

                     // Ambil outlet dari request  
                    
                    var outlet = "' . $this->request()->get('outlet') . '"; // Ambil outlet dari request  
                    if(!outlet){
                        outlet = "' . $dataOutlet->name . '";
                        // let outletUser = ' . $dataOutlet->name . '
                        // outlet = outletUser[0]
                    }
                    
                    var total = ' . session('total_transaction', 0) . '; // Ambil total dari session  

                    // Tambahkan custom row di paling atas  
                    if (data.length > 0) {  
                        var customRow = "<tr>" +  
                            "<td colspan=\'4\' style=\'text-align: left; background-color: #f0f0f0;\'> " + outlet + "</td>" +  
                            "<td style=\'text-align: center; background-color: #f0f0f0;\'> " + formatRupiah(total.toString(), "Rp. ") + "</td>" +  
                            "</tr>";  
                        $(api.table().body()).prepend(customRow);  
                    }  
                }'
            ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('outlet_id')->title("Outlet"),
            Column::make('created_at')->title("Time"),
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
        return 'Transactions_' . date('YmdHis');
    }
}
