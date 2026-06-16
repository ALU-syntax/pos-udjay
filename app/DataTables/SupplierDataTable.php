<?php

namespace App\DataTables;

use App\Enums\ProcurementMode;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class SupplierDataTable extends DataTable
{
    private const DAY_NAMES = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu',
    ];

    private const DAY_SHORT_NAMES = [
        1 => 'Sen',
        2 => 'Sel',
        3 => 'Rab',
        4 => 'Kam',
        5 => 'Jum',
        6 => 'Sab',
        7 => 'Min',
    ];

    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('action', function (Supplier $supplier) {
                return view('layouts.supplier.action', compact('supplier'))->render();
            })
            ->editColumn('code', function (Supplier $supplier) {
                return $supplier->code
                    ? '<span class="supplier-code-pill">' . e($supplier->code) . '</span>'
                    : '<span class="text-muted">Tanpa kode</span>';
            })
            ->editColumn('name', function (Supplier $supplier) {
                $status = $supplier->is_active
                    ? '<span class="supplier-status-dot is-active"></span>Aktif'
                    : '<span class="supplier-status-dot"></span>Tidak Aktif';

                return '<div class="supplier-name-cell">'
                    . '<span class="supplier-name-text">' . e($supplier->name) . '</span>'
                    . '<small>' . $status . '</small>'
                    . '</div>';
            })
            ->editColumn('procurement_mode', function (Supplier $supplier) {
                return $this->renderProcurementMode($supplier);
            })
            ->addColumn('operational_window', function (Supplier $supplier) {
                return $this->renderOperationalWindow($supplier);
            })
            ->addColumn('order_place', function (Supplier $supplier) {
                return $this->renderOrderPlace($supplier);
            })
            ->addColumn('raw_material_names', function (Supplier $supplier) {
                return $this->renderRawMaterials($supplier);
            })
            ->filterColumn('procurement_mode', function (QueryBuilder $query, $keyword) {
                $keyword = strtolower($keyword);
                $modes = [];

                if (str_contains($keyword, 'online')) {
                    $modes[] = ProcurementMode::ONLINE->value;
                    $modes[] = ProcurementMode::BOTH->value;
                }

                if (str_contains($keyword, 'offline')) {
                    $modes[] = ProcurementMode::OFFLINE->value;
                    $modes[] = ProcurementMode::BOTH->value;
                }

                if (str_contains($keyword, 'keduanya') || str_contains($keyword, 'both')) {
                    $modes[] = ProcurementMode::BOTH->value;
                }

                if ($modes !== []) {
                    $query->whereIn('procurement_mode', array_unique($modes));
                }
            })
            ->filterColumn('operational_window', function (QueryBuilder $query, $keyword) {
                $keyword = strtolower($keyword);
                $dayMatches = collect(self::DAY_NAMES)
                    ->filter(fn ($name, $day) => str_contains(strtolower($name), $keyword)
                        || str_contains(strtolower(self::DAY_SHORT_NAMES[$day]), $keyword))
                    ->keys()
                    ->all();

                $query->whereHas('operationalHours', function (QueryBuilder $hours) use ($keyword, $dayMatches) {
                    $hours->whereNull('deleted_at')
                        ->where(function (QueryBuilder $hours) use ($keyword, $dayMatches) {
                            $hours->where('open_time', 'like', "%{$keyword}%")
                                ->orWhere('close_time', 'like', "%{$keyword}%")
                                ->orWhere('notes', 'like', "%{$keyword}%");

                            if ($dayMatches !== []) {
                                $hours->orWhereIn('day_of_week', $dayMatches);
                            }
                        });
                });
            })
            ->filterColumn('order_place', function (QueryBuilder $query, $keyword) {
                $query->whereHas('orderChannels', function (QueryBuilder $channel) use ($keyword) {
                    $channel->whereNull('deleted_at')
                        ->where(function (QueryBuilder $channel) use ($keyword) {
                            $channel->where('channel_name', 'like', "%{$keyword}%")
                                ->orWhere('identifier', 'like', "%{$keyword}%")
                                ->orWhere('address', 'like', "%{$keyword}%")
                                ->orWhereHas('channelType', fn (QueryBuilder $type) => $type->where('name', 'like', "%{$keyword}%"));
                        });
                });
            })
            ->filterColumn('raw_material_names', function (QueryBuilder $query, $keyword) {
                $query->whereHas('rawMaterials', function (QueryBuilder $material) use ($keyword) {
                    $material->where('supplier_material_name', 'like', "%{$keyword}%")
                        ->orWhereHas('rawMaterial', fn (QueryBuilder $rawMaterial) => $rawMaterial->where('name', 'like', "%{$keyword}%"));
                });
            })
            ->rawColumns([
                'action',
                'code',
                'name',
                'procurement_mode',
                'operational_window',
                'order_place',
                'raw_material_names',
            ])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Supplier $model): QueryBuilder
    {
        return $model->newQuery()
            ->with([
                'operationalHours' => fn ($hours) => $hours->whereNull('deleted_at'),
                'orderChannels' => fn ($channels) => $channels->whereNull('deleted_at')->with('channelType'),
                'rawMaterials.rawMaterial',
            ]);
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('supplier-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    ->responsive(true)
                    ->orderBy(1)
                    ->selectStyleSingle();
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('code')->title('Kode'),
            Column::make('name')->title('Nama Supplier'),
            Column::make('procurement_mode')->title('Mode Procurement'),
            Column::make('operational_window')->title('Jadwal Operasional')->searchable(true)->orderable(false),
            Column::make('order_place')->title('Tempat Pemesanan')->orderable(false),
            Column::make('raw_material_names')->title('Nama Bahan Baku')->orderable(false),
            Column::computed('action')
                  ->exportable(false)
                  ->printable(false)
                  ->width(112)
                  ->addClass('text-center'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Supplier_' . date('YmdHis');
    }

    private function renderProcurementMode(Supplier $supplier): string
    {
        $mode = (int) $supplier->procurement_mode;
        $label = $mode === ProcurementMode::BOTH->value
            ? 'Online / Offline'
            : ProcurementMode::labelFor($mode);

        $class = match ($mode) {
            ProcurementMode::ONLINE->value => 'mode-online',
            ProcurementMode::OFFLINE->value => 'mode-offline',
            ProcurementMode::BOTH->value => 'mode-both',
            default => 'mode-empty',
        };

        $icon = match ($mode) {
            ProcurementMode::ONLINE->value => 'fa-globe',
            ProcurementMode::OFFLINE->value => 'fa-store',
            ProcurementMode::BOTH->value => 'fa-random',
            default => 'fa-minus',
        };

        return '<span class="supplier-mode-chip ' . $class . '">'
            . '<i class="fa ' . $icon . '"></i>'
            . e($label)
            . '</span>';
    }

    private function renderOperationalWindow(Supplier $supplier): string
    {
        $hours = $supplier->operationalHours
            ->sortBy(fn ($hour) => sprintf('%02d-%02d', $hour->day_of_week, $hour->sequence))
            ->values();

        if ($hours->isEmpty()) {
            return '<div class="supplier-stack-cell">'
                . '<span class="supplier-main-line text-muted">Belum diatur</span>'
                . '<small class="supplier-sub-line">Jam operasional belum tersedia</small>'
                . '</div>';
        }

        $days = $hours->pluck('day_of_week')->unique()->sort()->values()->all();
        $schedule = $this->formatDayList($days);
        $timeRanges = $hours
            ->map(fn ($hour) => $this->formatTimeRange($hour))
            ->unique()
            ->values();
        $timeDisplay = $timeRanges->take(2)->implode(', ');

        if ($timeRanges->count() > 2) {
            $timeDisplay .= ' +' . ($timeRanges->count() - 2) . ' sesi';
        }

        return '<div class="supplier-stack-cell" title="' . e($schedule . ' | ' . $timeRanges->implode(', ')) . '">'
            . '<span class="supplier-main-line"><i class="fa fa-calendar"></i>' . e($schedule) . '</span>'
            . '<small class="supplier-sub-line"><i class="fa fa-clock"></i>' . e($timeDisplay) . '</small>'
            . '</div>';
    }

    private function renderOrderPlace(Supplier $supplier): string
    {
        $channels = $supplier->orderChannels
            ->where('is_active', true)
            ->sortByDesc('is_primary')
            ->map(fn ($channel) => $channel->channel_name
                ?: $channel->channelType?->name
                ?: $channel->identifier)
            ->filter()
            ->unique()
            ->values();

        if ($channels->isEmpty()) {
            return '<span class="text-muted">-</span>';
        }

        $visibleChannels = $channels->take(2)
            ->map(fn ($channel) => '<span class="supplier-channel-pill">' . e($channel) . '</span>')
            ->implode('');

        if ($channels->count() > 2) {
            $visibleChannels .= '<span class="supplier-channel-more">+' . ($channels->count() - 2) . '</span>';
        }

        return '<div class="supplier-channel-list" title="' . e($channels->implode(', ')) . '">' . $visibleChannels . '</div>';
    }

    private function renderRawMaterials(Supplier $supplier): string
    {
        $materials = $supplier->rawMaterials
            ->map(fn ($material) => $material->rawMaterial?->name
                ?: $material->supplier_material_name
                ?: 'Bahan #' . $material->id)
            ->filter()
            ->unique()
            ->values();

        $count = $supplier->rawMaterials->count();

        if ($count === 0) {
            return '<span class="text-muted">-</span>';
        }

        if ($count === 1) {
            return '<span class="supplier-material-single">' . e($materials->first()) . '</span>';
        }

        return '<span class="supplier-material-count" data-bs-toggle="tooltip" data-bs-placement="top" title="' . e($materials->take(5)->implode(', ')) . '">'
            . '<i class="fa fa-boxes"></i>'
            . $count . ' Bahan'
            . '</span>';
    }

    private function formatDayList(array $days): string
    {
        if (count($days) === 7) {
            return 'Setiap hari';
        }

        return collect($days)
            ->map(fn ($day) => self::DAY_SHORT_NAMES[$day] ?? null)
            ->filter()
            ->implode(', ');
    }

    private function formatTimeRange($hour): string
    {
        if ($hour->is_24_hours) {
            return '24 Jam';
        }

        $open = $hour->open_time ? substr((string) $hour->open_time, 0, 5) : null;
        $close = $hour->close_time ? substr((string) $hour->close_time, 0, 5) : null;

        if ($open && $close) {
            return $open . ' - ' . $close;
        }

        return 'Jam belum diisi';
    }
}
