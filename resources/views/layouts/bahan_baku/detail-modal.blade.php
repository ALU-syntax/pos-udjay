{{-- ob-page memberi token & gaya report ke modal (modal dirender di luar .main-content) --}}
{{-- :title (bukan title="{{ }}") agar nama tidak ter-escape dua kali; komponen sudah meng-escape $title --}}
<x-modal addStyle="modal-xl ob-page ob-modal bb-detail-modal" :title="'Detail Bahan Baku: ' . $rawMaterial->name" action="#" method="GET" customSubmit="true">
    @include('layouts.warehouse.partials.ui-styles')

    @php
        $unitLabel = fn ($unit) => $unit?->symbol ?: $unit?->name ?: '-';
        // Qty tanpa nol di belakang koma: 25000.00000 → "25.000", 0.50000 → "0,5"
        $qty = fn ($value) => rtrim(rtrim(number_format((float) $value, 5, ',', '.'), '0'), ',');
        $rupiah = fn ($value) => 'Rp ' . number_format((float) $value, fmod((float) $value, 1) ? 2 : 0, ',', '.');
        $baseUnit = $unitLabel($rawMaterial->baseUnit);

        $totalAvailable = $stockBalances->sum(fn ($balance) => (float) $balance->qty_available);
        $totalReserved = $stockBalances->sum(fn ($balance) => (float) $balance->qty_reserved);
        $activeSuppliers = $supplierMaterials->filter(fn ($item) => $item->is_active && $item->supplier->is_active)->count();
        $preferredSuppliers = $supplierMaterials->where('is_preferred', true)->count();
        $canReadSupplier = auth()->user()->can('read warehouse/supplier');
    @endphp

    <div class="col-12">
        <p class="ob-modal__desc">
            <span class="badge {{ $rawMaterial->is_active ? 'badge-success' : 'badge-muted' }}">{{ $rawMaterial->is_active ? 'Aktif' : 'Nonaktif' }}</span>
            <span class="bb-detail__code">{{ $rawMaterial->code ?: 'Tanpa kode' }}</span>
            @if ($rawMaterial->notes)
                <span class="bb-detail__notes">{{ $rawMaterial->notes }}</span>
            @endif
        </p>
    </div>

    {{-- Info master --}}
    <div class="col-12">
        <dl class="ob-meta bb-detail__meta">
            <div class="ob-meta__item"><dt>Kategori</dt><dd>{{ $rawMaterial->category?->name ?? 'Tanpa kategori' }}</dd></div>
            <div class="ob-meta__item"><dt>Satuan Dasar</dt><dd>{{ $rawMaterial->baseUnit ? $rawMaterial->baseUnit->name . ($rawMaterial->baseUnit->symbol ? ' (' . $rawMaterial->baseUnit->symbol . ')' : '') : '' }}</dd></div>
            <div class="ob-meta__item"><dt>Penyimpanan</dt><dd>{{ $rawMaterial->storageType?->name }}</dd></div>
            <div class="ob-meta__item"><dt>Diperbarui</dt><dd>{{ $rawMaterial->updated_at?->translatedFormat('d M Y, H:i') }}</dd></div>
        </dl>
    </div>

    {{-- Ringkasan --}}
    <div class="col-12">
        <div class="bb-detail__stats">
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--primary" aria-hidden="true"><i class="fas fa-warehouse"></i></span>
                <div>
                    <p class="ob-stat__label">Stok Tersedia</p>
                    <p class="ob-stat__value">{{ $qty($totalAvailable) }} <span class="bb-detail__unit">{{ $baseUnit }}</span></p>
                    <p class="bb-detail__stat-sub">{{ $stockBalances->isEmpty() ? 'Belum ada saldo' : 'di ' . $stockBalances->count() . ' lokasi · ' . $qty($totalReserved) . ' dipesan' }}</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--success" aria-hidden="true"><i class="fas fa-truck"></i></span>
                <div>
                    <p class="ob-stat__label">Supplier Aktif</p>
                    <p class="ob-stat__value">{{ number_format($activeSuppliers, 0, ',', '.') }}</p>
                    <p class="bb-detail__stat-sub">{{ $preferredSuppliers > 0 ? $preferredSuppliers . ' supplier utama' : 'Belum ada supplier utama' }}</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--warning" aria-hidden="true"><i class="fas fa-exchange-alt"></i></span>
                <div>
                    <p class="ob-stat__label">Konversi Satuan</p>
                    <p class="ob-stat__value">{{ number_format($unitConversions->count(), 0, ',', '.') }}</p>
                    <p class="bb-detail__stat-sub">aturan satuan beli ke satuan lain</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Stok per lokasi + konversi satuan --}}
    <div class="col-12">
        <div class="bb-detail__grid">
            <div class="ob-card bb-detail__card">
                <div class="wh-card__head">
                    <div>
                        <h3 class="wh-card__title"><i class="fas fa-warehouse" aria-hidden="true"></i> Stok per Lokasi <span class="wh-count">{{ $stockBalances->count() }}</span></h3>
                        <p class="wh-card__desc">Dalam satuan dasar ({{ $baseUnit }}). Dipesan = sudah dialokasikan, belum keluar.</p>
                    </div>
                </div>
                @if ($stockBalances->isEmpty())
                    <div class="wh-empty">
                        <span class="wh-empty__icon" aria-hidden="true"><i class="fas fa-warehouse"></i></span>
                        <p class="wh-empty__title">Belum ada stok</p>
                        <p class="wh-empty__text">Saldo stok bahan ini belum tercatat di lokasi mana pun. Lihat menu Warehouse / Inventory.</p>
                    </div>
                @else
                    <div class="ob-table-scroll">
                        <table class="wh-table">
                            <thead>
                                <tr>
                                    <th scope="col">Lokasi</th>
                                    <th scope="col" class="text-end">Tersedia</th>
                                    <th scope="col" class="text-end">Dipesan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($stockBalances as $balance)
                                    @php
                                        $inventory = $balance->inventory;
                                        $inventoryActive = $inventory && ! $inventory->trashed() && $inventory->is_active;
                                    @endphp
                                    <tr class="{{ $inventoryActive ? '' : 'wh-row-inactive' }}">
                                        <td>
                                            <div class="wh-cell-stack">
                                                <span class="wh-strong">{{ $inventory?->name ?? 'Lokasi #' . $balance->inventory_id }}</span>
                                                @if (! $inventory || $inventory->trashed())
                                                    <span class="ob-cell-meta">Lokasi dihapus</span>
                                                @elseif (! $inventory->is_active)
                                                    <span class="ob-cell-meta">Lokasi nonaktif</span>
                                                @elseif ($inventory->code)
                                                    <span class="ob-cell-meta">{{ $inventory->code }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-end wh-num wh-strong">{{ $qty($balance->qty_available) }}</td>
                                        <td class="text-end wh-num">{{ (float) $balance->qty_reserved > 0 ? $qty($balance->qty_reserved) : '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="ob-card bb-detail__card">
                <div class="wh-card__head">
                    <div>
                        <h3 class="wh-card__title"><i class="fas fa-exchange-alt" aria-hidden="true"></i> Konversi Satuan <span class="wh-count">{{ $unitConversions->count() }}</span></h3>
                        <p class="wh-card__desc">Dipakai untuk menghitung satuan beli ke satuan stok.</p>
                    </div>
                </div>
                @if ($unitConversions->isEmpty())
                    <div class="wh-empty">
                        <span class="wh-empty__icon" aria-hidden="true"><i class="fas fa-exchange-alt"></i></span>
                        <p class="wh-empty__title">Belum ada konversi</p>
                        <p class="wh-empty__text">Tambahkan lewat menu Library / Satuan, bagian Konversi per Bahan Baku.</p>
                    </div>
                @else
                    <div class="ob-table-scroll">
                        <table class="wh-table">
                            <thead>
                                <tr>
                                    <th scope="col">Formula</th>
                                    <th scope="col">Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($unitConversions as $conversion)
                                    <tr>
                                        <td>
                                            <div class="wh-cell-stack">
                                                <span class="wh-strong wh-num">1 {{ $unitLabel($conversion->fromUnit) }} = {{ $qty($conversion->multiplier) }} {{ $unitLabel($conversion->toUnit) }}</span>
                                                <span class="ob-cell-meta">{{ $conversion->fromUnit?->name ?? '-' }} → {{ $conversion->toUnit?->name ?? '-' }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            @if ($conversion->notes)
                                                <span class="ob-cell-meta wh-truncate" title="{{ $conversion->notes }}">{{ $conversion->notes }}</span>
                                            @else
                                                <span class="ob-cell-meta">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Supplier --}}
    <div class="col-12">
        <div class="ob-card bb-detail__card">
            <div class="wh-card__head">
                <div>
                    <h3 class="wh-card__title"><i class="fas fa-truck" aria-hidden="true"></i> Supplier <span class="wh-count">{{ $supplierMaterials->count() }}</span></h3>
                    <p class="wh-card__desc">Harga per satuan beli. Supplier bertanda Utama diprioritaskan saat procurement.</p>
                </div>
            </div>
            @if ($supplierMaterials->isEmpty())
                <div class="wh-empty">
                    <span class="wh-empty__icon" aria-hidden="true"><i class="fas fa-truck"></i></span>
                    <p class="wh-empty__title">Belum ada supplier</p>
                    <p class="wh-empty__text">Hubungkan bahan ini lewat menu Warehouse / Supplier: buka detail supplier, lalu Tambah Bahan Baku.</p>
                </div>
            @else
                <div class="ob-table-scroll">
                    <table class="wh-table">
                        <thead>
                            <tr>
                                <th scope="col">Supplier</th>
                                <th scope="col">Satuan Beli</th>
                                <th scope="col" class="text-end">Harga</th>
                                <th scope="col">Lead Time</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($supplierMaterials as $item)
                                @php
                                    $supplier = $item->supplier;
                                    $isActive = $item->is_active && $supplier->is_active;
                                    $aliases = collect([
                                        $item->supplier_material_name && $item->supplier_material_name !== $rawMaterial->name ? 'Di supplier: ' . $item->supplier_material_name : null,
                                        $item->supplier_sku ? 'SKU ' . $item->supplier_sku : null,
                                    ])->filter();
                                @endphp
                                <tr class="{{ $isActive ? '' : 'wh-row-inactive' }}">
                                    <td>
                                        <div class="wh-cell-stack">
                                            <span class="wh-strong">
                                                @if ($canReadSupplier)
                                                    <a href="{{ route('warehouse/supplier/show', $supplier->id) }}" class="bb-detail__link">{{ $supplier->name }}</a>
                                                @else
                                                    {{ $supplier->name }}
                                                @endif
                                                @if ($item->is_preferred)
                                                    <span class="badge badge-primary bb-badge-inline" title="Supplier utama untuk bahan ini"><i class="fas fa-star" aria-hidden="true"></i> Utama</span>
                                                @endif
                                            </span>
                                            @if ($aliases->isNotEmpty())
                                                <span class="ob-cell-meta">{{ $aliases->implode(' · ') }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>{{ $item->purchaseUnit?->name ?? '-' }}</td>
                                    <td class="text-end">
                                        @if ($item->current_price !== null)
                                            <span class="wh-strong wh-num">{{ $rupiah($item->current_price) }}</span>
                                        @else
                                            <span class="ob-cell-meta">Belum diisi</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">{{ $item->lead_time_days > 0 ? $item->lead_time_days . ' hari' : 'Hari itu juga' }}</td>
                                    <td>
                                        @if (! $supplier->is_active)
                                            <span class="badge badge-muted">Supplier nonaktif</span>
                                        @elseif (! $item->is_active)
                                            <span class="badge badge-muted">Nonaktif</span>
                                        @else
                                            <span class="badge badge-success">Aktif</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="col-12">
        <div class="bb-detail__foot">
            <button type="button" class="btn ob-btn ob-btn-outline" data-bs-dismiss="modal">Tutup</button>
        </div>
    </div>

    <style>
        /* Footer bawaan komponen kosong (customSubmit), tombol tutup ada di body */
        .bb-detail-modal .modal-footer { display: none; }
        .bb-detail-modal .modal-body { padding-bottom: 1.25rem; }

        .bb-detail__code { margin-left: .4rem; font-weight: 600; color: var(--ob-text-2); }
        .bb-detail__notes { display: block; margin-top: .5rem; white-space: pre-line; }

        .bb-detail-modal .bb-detail__meta { grid-template-columns: repeat(4, minmax(0, 1fr)); margin-bottom: 1rem; }

        .bb-detail__stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .75rem; margin-bottom: 1rem; }
        .bb-detail__stats .ob-stat { margin-bottom: 0; }
        .bb-detail__unit { font-size: .8rem; font-weight: 600; color: var(--ob-muted); }
        .bb-detail__stat-sub { margin: .1rem 0 0; font-size: .76rem; color: var(--ob-muted); }

        .bb-detail__grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
        .bb-detail-modal .bb-detail__card { margin-bottom: 1rem; overflow: hidden; }
        .bb-detail__link { color: inherit; text-decoration: none; }
        .bb-detail__link:hover { color: var(--ob-primary); text-decoration: underline; }
        .bb-detail-modal .bb-badge-inline { margin-left: .35rem; vertical-align: 1px; }
        .bb-detail-modal .bb-badge-inline::before { display: none; }

        .bb-detail__foot { display: flex; justify-content: flex-end; }

        @media (max-width: 991.98px) {
            .bb-detail-modal .bb-detail__meta { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .bb-detail__grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 767.98px) {
            .bb-detail__stats { grid-template-columns: 1fr; }
        }
    </style>
</x-modal>
