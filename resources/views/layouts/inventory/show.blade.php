@extends('layouts.app')
@section('content')
    @include('layouts.reports.partials.ui-styles')
    @include('layouts.warehouse.partials.ui-styles')

    @php
        // Qty tanpa nol di belakang koma: 25000.00000 → "25.000", 0.50000 → "0,5"
        $qty = fn ($value) => rtrim(rtrim(number_format((float) $value, 5, ',', '.'), '0'), ',');
        $unitLabel = fn ($unit) => $unit?->symbol ?: $unit?->name ?: '';
        $canCreate = auth()->user()->can('create warehouse/inventory');
        $canUpdate = auth()->user()->can('update warehouse/inventory');
        $canDelete = auth()->user()->can('delete warehouse/inventory');
        $canRead = auth()->user()->can('read warehouse/inventory');

        // Status per bahan, juga dipakai filter (data-status)
        $statuses = [
            'available' => ['label' => 'Tersedia', 'class' => 'badge-success'],
            'partial' => ['label' => 'Sebagian Dipesan', 'class' => 'badge-warning'],
            'reserved' => ['label' => 'Dipesan Semua', 'class' => 'badge-muted'],
            'empty' => ['label' => 'Habis', 'class' => 'badge-danger'],
        ];
        $statusOf = function ($balance) {
            $available = (float) $balance->qty_available;
            $reserved = (float) $balance->qty_reserved;

            return match (true) {
                $available <= 0 => 'empty',
                $reserved >= $available => 'reserved',
                $reserved > 0 => 'partial',
                default => 'available',
            };
        };
    @endphp

    <div class="main-content ob-page">
        {{-- Header --}}
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">
                    <a href="{{ route('warehouse/inventory') }}">Inventory</a><span class="wh-crumb-sep" aria-hidden="true">/</span>{{ $inventory->code ?: 'Tanpa kode' }}
                </div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-warehouse"></i></span>
                    {{ $inventory->name }}
                </h1>
                <div class="wh-profile-meta">
                    <span class="badge {{ $inventory->is_active ? 'badge-success' : 'badge-muted' }}">{{ $inventory->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                    @if ($inventory->type)
                        <span class="badge badge-primary">{{ $inventory->type->name }}</span>
                    @endif
                    @if ($inventory->parent)
                        <span>
                            Bagian dari
                            @if ($canRead)
                                <a href="{{ route('warehouse/inventory/detail', $inventory->parent->id) }}">{{ $inventory->parent->name }}</a>
                            @else
                                {{ $inventory->parent->name }}
                            @endif
                        </span>
                    @endif
                    <span>Terakhir diubah {{ $inventory->updated_at?->translatedFormat('d M Y, H:i') ?? '-' }}</span>
                </div>
            </div>
            @if ($canUpdate || $canCreate)
                <div class="wh-actions">
                    @if ($canUpdate)
                        <a href="{{ route('warehouse/inventory/edit', $inventory->id) }}" class="btn ob-btn ob-btn-outline action">
                            <i class="fas fa-pen" aria-hidden="true"></i> Edit Lokasi
                        </a>
                    @endif
                    @if ($canCreate)
                        <a href="{{ route('warehouse/inventory/stock-balances/create', $inventory->id) }}" class="btn ob-btn ob-btn-primary action">
                            <i class="fas fa-plus" aria-hidden="true"></i> Tambah Bahan Baku
                        </a>
                    @endif
                </div>
            @endif
        </div>

        {{-- Ringkasan: hitungan bahan, karena qty tiap bahan bersatuan berbeda --}}
        <div class="ob-stats">
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--primary" aria-hidden="true"><i class="fas fa-cubes"></i></span>
                <div>
                    <p class="ob-stat__label">Jumlah Bahan</p>
                    <p class="ob-stat__value">{{ number_format($stockStats['total_materials'], 0, ',', '.') }}</p>
                    <p class="wh-stat-sub">tercatat di lokasi ini</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--danger" aria-hidden="true"><i class="fas fa-exclamation-circle"></i></span>
                <div>
                    <p class="ob-stat__label">Bahan Habis</p>
                    <p class="ob-stat__value">{{ number_format($stockStats['empty_materials'], 0, ',', '.') }}</p>
                    <p class="wh-stat-sub">stok tersedia 0</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--warning" aria-hidden="true"><i class="fas fa-lock"></i></span>
                <div>
                    <p class="ob-stat__label">Bahan Dipesan</p>
                    <p class="ob-stat__value">{{ number_format($stockStats['reserved_materials'], 0, ',', '.') }}</p>
                    <p class="wh-stat-sub">sebagian stok sudah dialokasikan</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--success" aria-hidden="true"><i class="fas fa-sitemap"></i></span>
                <div>
                    <p class="ob-stat__label">Sub-lokasi</p>
                    <p class="ob-stat__value">{{ number_format($inventory->children->count(), 0, ',', '.') }}</p>
                    <p class="wh-stat-sub">lokasi di dalam lokasi ini</p>
                </div>
            </div>
        </div>

        <div class="wh-detail-grid">
            {{-- Kolom utama --}}
            <div>
                <div class="ob-card">
                    <div class="wh-card__head">
                        <div>
                            <h2 class="wh-card__title"><i class="fas fa-boxes" aria-hidden="true"></i> Stok Bahan Baku <span class="wh-count">{{ $stockBalances->count() }}</span></h2>
                            <p class="wh-card__desc">Qty dalam satuan dasar bahan. Bebas = tersedia dikurangi yang sudah dipesan.</p>
                        </div>
                        @if ($stockBalances->isNotEmpty())
                            <div class="inv-stock-filters">
                                <input id="stock-search" type="search" class="form-control" placeholder="Cari bahan atau kode" aria-label="Cari bahan baku">
                                <select id="stock-status" class="form-select" aria-label="Filter status stok">
                                    <option value="">Semua status</option>
                                    @foreach ($statuses as $key => $status)
                                        <option value="{{ $key }}">{{ $status['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                    @if ($stockBalances->isEmpty())
                        <div class="wh-empty">
                            <span class="wh-empty__icon" aria-hidden="true"><i class="fas fa-boxes"></i></span>
                            <p class="wh-empty__title">Belum ada stok</p>
                            <p class="wh-empty__text">Tambahkan bahan baku yang disimpan di lokasi ini beserta qty tersedianya.</p>
                        </div>
                    @else
                        <div class="ob-table-scroll">
                            <table class="wh-table" id="inventory-stock-table">
                                <thead>
                                    <tr>
                                        <th scope="col">Bahan Baku</th>
                                        <th scope="col">Penyimpanan</th>
                                        <th scope="col" class="text-end">Tersedia</th>
                                        <th scope="col" class="text-end">Dipesan</th>
                                        <th scope="col" class="text-end">Bebas</th>
                                        <th scope="col">Status</th>
                                        @if ($canUpdate || $canDelete)
                                            <th scope="col"><span class="visually-hidden">Aksi</span></th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($stockBalances as $item)
                                        @php
                                            $material = $item->rawMaterial;
                                            $materialName = $material?->name ?? 'Bahan #' . $item->raw_material_id;
                                            $unit = $unitLabel($material?->baseUnit);
                                            $statusKey = $statusOf($item);
                                            $free = (float) $item->qty_available - (float) $item->qty_reserved;
                                            $materialInactive = ! $material || $material->trashed() || ! $material->is_active;
                                        @endphp
                                        <tr class="{{ $materialInactive ? 'wh-row-inactive' : '' }}" data-status="{{ $statusKey }}"
                                            data-search="{{ mb_strtolower($materialName . ' ' . ($material?->code ?? '')) }}">
                                            <td>
                                                <div class="wh-cell-stack">
                                                    <span class="wh-strong">{{ $materialName }}</span>
                                                    <span class="ob-cell-meta">
                                                        {{ $material?->code ?: 'Tanpa kode' }} · {{ $material?->category?->name ?? 'Tanpa kategori' }}
                                                    </span>
                                                    @if (! $material || $material->trashed())
                                                        <span class="ob-cell-meta">Bahan sudah diarsipkan</span>
                                                    @elseif (! $material->is_active)
                                                        <span class="ob-cell-meta">Bahan nonaktif</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>{{ $material?->storageType?->name ?? '-' }}</td>
                                            <td class="text-end wh-num wh-strong">{{ $qty($item->qty_available) }} <span class="inv-unit">{{ $unit }}</span></td>
                                            <td class="text-end wh-num">{{ (float) $item->qty_reserved > 0 ? $qty($item->qty_reserved) : '-' }}</td>
                                            <td class="text-end wh-num">{{ $qty(max($free, 0)) }}</td>
                                            <td><span class="badge {{ $statuses[$statusKey]['class'] }}">{{ $statuses[$statusKey]['label'] }}</span></td>
                                            @if ($canUpdate || $canDelete)
                                                <td>
                                                    <div class="ob-row-actions" role="group" aria-label="Aksi stok {{ $materialName }}">
                                                        @if ($canUpdate)
                                                            <a href="{{ route('warehouse/inventory/stock-balances/edit', [$inventory->id, $item->id]) }}" class="ob-icon-btn ob-icon-btn--sm action"
                                                                title="Edit stok" aria-label="Edit stok"><i class="fas fa-pen" aria-hidden="true"></i></a>
                                                        @endif
                                                        @if ($canDelete)
                                                            <a href="{{ route('warehouse/inventory/stock-balances/destroy', [$inventory->id, $item->id]) }}" class="ob-icon-btn ob-icon-btn--sm ob-icon-btn--danger delete-stock"
                                                                data-name="{{ $materialName }}" title="Hapus stok" aria-label="Hapus stok"><i class="fas fa-trash" aria-hidden="true"></i></a>
                                                        @endif
                                                    </div>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="wh-side-empty inv-filter-empty" hidden>Tidak ada bahan yang cocok dengan pencarian atau filter.</p>
                    @endif
                </div>
            </div>

            {{-- Kolom samping --}}
            <aside>
                <div class="ob-card">
                    <div class="wh-card__head">
                        <h2 class="wh-card__title"><i class="fas fa-id-card" aria-hidden="true"></i> Profil Lokasi</h2>
                    </div>
                    <div class="wh-card__body">
                        <dl class="wh-dl">
                            <div class="wh-dl__row"><dt>Kode</dt><dd>{{ $inventory->code }}</dd></div>
                            <div class="wh-dl__row"><dt>Tipe</dt><dd>{{ $inventory->type?->name }}</dd></div>
                            <div class="wh-dl__row"><dt>Bagian dari</dt><dd>{{ $inventory->parent?->name ?? 'Lokasi utama' }}</dd></div>
                            <div class="wh-dl__row"><dt>Outlet</dt><dd>{{ $inventory->outlet?->name ?? 'Semua outlet' }}</dd></div>
                            <div class="wh-dl__row"><dt>Brand</dt><dd>{{ $inventory->brand?->name ?? 'Semua brand' }}</dd></div>
                            <div class="wh-dl__row"><dt>Dibuat</dt><dd>{{ $inventory->created_at?->translatedFormat('d M Y') }}</dd></div>
                        </dl>
                    </div>
                </div>

                <div class="ob-card">
                    <div class="wh-card__head">
                        <h2 class="wh-card__title"><i class="fas fa-sitemap" aria-hidden="true"></i> Sub-lokasi <span class="wh-count">{{ $inventory->children->count() }}</span></h2>
                    </div>
                    @if ($inventory->children->isEmpty())
                        <p class="wh-side-empty">Belum ada lokasi lain di dalam lokasi ini.</p>
                    @else
                        <ul class="wh-list">
                            @foreach ($inventory->children as $child)
                                <li class="wh-list__item {{ $child->is_active ? '' : 'wh-row-inactive' }}">
                                    <div class="wh-list__top">
                                        @if ($canRead)
                                            <a href="{{ route('warehouse/inventory/detail', $child->id) }}" class="wh-strong inv-link">{{ $child->name }}</a>
                                        @else
                                            <span class="wh-strong">{{ $child->name }}</span>
                                        @endif
                                        @unless ($child->is_active)
                                            <span class="badge badge-muted">Nonaktif</span>
                                        @endunless
                                    </div>
                                    <span class="ob-cell-meta">{{ $child->code ?: 'Tanpa kode' }}{{ $child->type ? ' · ' . $child->type->name : '' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </aside>
        </div>
    </div>

    @push('js')
        <script>
            $(function () {
                handleAction(null, null, function () {
                    location.reload();
                });

                // Pencarian & filter status di sisi browser (semua baris sudah dimuat)
                function filterStock() {
                    const keyword = ($('#stock-search').val() || '').toLowerCase().trim();
                    const status = $('#stock-status').val();
                    let visible = 0;

                    $('#inventory-stock-table tbody tr').each(function () {
                        const row = $(this);
                        const show = (!keyword || row.data('search').toString().includes(keyword))
                            && (!status || row.data('status') === status);
                        row.toggle(show);
                        if (show) visible++;
                    });

                    $('.inv-filter-empty').prop('hidden', visible > 0);
                }

                $('#stock-search').on('input search', filterStock);
                $('#stock-status').on('change', filterStock);

                $('.main-content').on('click', '.delete-stock', function (event) {
                    event.preventDefault();
                    const button = this;

                    Swal.fire({
                        title: 'Hapus stok bahan baku?',
                        text: 'Saldo ' + $(button).data('name') + ' di lokasi ini akan dihapus.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, hapus',
                        cancelButtonText: 'Batal'
                    }).then(function (result) {
                        if (!result.isConfirmed) return;

                        $.ajax({
                            url: button.href,
                            method: 'DELETE',
                            data: { _token: @json(csrf_token()) },
                            success: function (response) {
                                showToast(response.status || 'success', response.message || 'Stok bahan baku dihapus');
                                location.reload();
                            },
                            error: function (xhr) {
                                showToast('error', xhr.responseJSON?.message || 'Gagal menghapus stok bahan baku');
                            }
                        });
                    });
                });
            });
        </script>
    @endpush

    @push('css')
        <style>
            .ob-page .ob-stats .ob-stat { margin-bottom: 0; }

            .inv-stock-filters { display: flex; flex-wrap: wrap; gap: .5rem; }
            .inv-stock-filters .form-control,
            .inv-stock-filters .form-select { height: 36px; padding-top: .35rem; padding-bottom: .35rem; font-size: .82rem; }
            .inv-stock-filters .form-control { width: 200px; }
            .inv-stock-filters .form-select { width: 170px; }
            .inv-unit { font-size: .75rem; font-weight: 500; color: var(--ob-muted); }
            .inv-link { text-decoration: none; }
            .inv-link:hover { color: var(--ob-primary); text-decoration: underline; }

            @media (max-width: 575.98px) {
                .inv-stock-filters, .inv-stock-filters .form-control, .inv-stock-filters .form-select { width: 100%; }
            }
        </style>
    @endpush
@endsection
