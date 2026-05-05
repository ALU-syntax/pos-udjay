<?php

namespace App\DataTables;

use App\Models\Customer;
use App\Traits\DataTableHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class CustomerDataTable extends DataTable
{
    use DataTableHelper;
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function ($row) {
                return view('layouts.customer.action', [
                    'detail' => route('membership/customer/detailCustomer', $row->id),
                    'edit' =>route('membership/customer/edit', $row->id),
                    'routeDelete' =>route('membership/customer/destroy', $row->id),
                    'listReferee' =>route('membership/customer/listReferee', $row->id),
                ]);
            })
            ->editColumn('level_memberships_id', function($row){
                $levelName = $row->levelMembership?->name ?? '-';
                $levelColor = $this->normalizeHexColor($row->levelMembership?->color);
                $levelTextColor = $this->getReadableTextColor($levelColor);
                $communityName = $row->community?->name ?? 'No Community';

                return '<div class="customer-membership-info">'
                    . '<a class="action customer-membership-level" href="' . e(route('membership/customer/checkRewardConfirmation', $row->id)) . '" type="button" style="background-color: ' . e($levelColor) . '; border-color: ' . e($levelColor) . '; color: ' . e($levelTextColor) . ';">'
                    . e($levelName)
                    . '</a>'
                    . '<span class="customer-membership-community">' . e($communityName) . '</span>'
                    . '</div>';
            })

            // ->addColumn('community_id', fn($row) => optional($row->community)->name ?? '-')
            // ->addColumn('level_membership_id', fn($row) => optional($row->levelMembership)->name ?? '-')

            // // Petakan order ke kolom tabel relasi
            // ->orderColumn('community_id', function ($q, $order) {
            //     $q->leftJoin('communities', 'communities.id', '=', 'customers.community_id')
            //     ->orderBy('communities.name', $order);
            // })
            // ->orderColumn('level_membership_id', function ($q, $order) {
            //     $q->leftJoin('level_memberships', 'level_memberships.id', '=', 'customers.level_memberships_id')
            //     ->orderBy('level_memberships.name', $order);
            // })

            ->editColumn('created_at', function($row){
                $createdAt = Carbon::parse($row->created_at);

                return '<div class="customer-date-info">'
                    . '<span class="customer-date-main">' . e($createdAt->format('d-m-Y')) . '</span>'
                    . '<span class="customer-date-time">' . e($createdAt->format('H:i')) . '</span>'
                    . '</div>';
            })
            ->editColumn('name', function ($row) {
                $initials = $this->getInitials($row->name);
                $email = $row->email ?: '-';

                return '<a href="' . e(route('membership/customer/detail', $row->id)) . '" type="button" class="customer-member-link">'
                    . '<span class="customer-member-avatar">' . e($initials) . '</span>'
                    . '<span class="customer-member-info">'
                    . '<span class="customer-member-name">' . e($row->name) . '</span>'
                    . '<span class="customer-member-email">' . e($email) . '</span>'
                    . '</span>'
                    . '</a>';
            })
            ->editColumn('point', function ($row) {
                return '<div class="customer-progress-info">'
                    . '<a class="action customer-point-link" href="' . e(route('membership/customer/historyPointUse', $row->id)) . '" type="button">'
                    . '<span class="customer-progress-value">' . e($this->formatNumber($row->point)) . '</span>'
                    . '<span class="customer-progress-label">Point</span>'
                    . '</a>'
                    . '<span class="customer-exp-chip">'
                    . '<span class="customer-progress-value">' . e($this->formatNumber($row->exp)) . '</span>'
                    . '<span class="customer-progress-label">EXP</span>'
                    . '</span>'
                    . '</div>';
            })
            ->rawColumns(['created_at', 'name', 'level_memberships_id', 'point'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Customer $model): QueryBuilder
    {
        return $model->with(['community', 'levelMembership'])->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('customer-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            //->dom('Bfrtip')
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
            Column::make('name')->title('Member'),
            Column::make('telfon'),
            Column::make('point')->title('Progress'),
            Column::make('level_memberships_id')->title('Membership')->orderable(false),
            Column::make('created_at')->title("Created Date"),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width(60)
                ->addClass('text-center'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Customer_' . date('YmdHis');
    }

    private function getInitials(?string $name): string
    {
        $words = preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY);

        if (empty($words)) {
            return '?';
        }

        $initials = mb_substr($words[0], 0, 1);

        if (count($words) > 1) {
            $initials .= mb_substr($words[1], 0, 1);
        }

        return mb_strtoupper($initials);
    }

    private function formatNumber($value): string
    {
        return number_format((int) $value, 0, ',', '.');
    }

    private function normalizeHexColor(?string $color): string
    {
        $color = trim((string) $color);

        if (preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            return $color;
        }

        return '#2563eb';
    }

    private function getReadableTextColor(string $backgroundColor): string
    {
        $hex = ltrim($backgroundColor, '#');
        $red = hexdec(substr($hex, 0, 2));
        $green = hexdec(substr($hex, 2, 2));
        $blue = hexdec(substr($hex, 4, 2));
        $brightness = (($red * 299) + ($green * 587) + ($blue * 114)) / 1000;

        return $brightness > 150 ? '#111827' : '#ffffff';
    }
}
