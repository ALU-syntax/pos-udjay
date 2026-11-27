@extends('layouts.app')
@section('content')
    @include('layouts.reports.partials.ui-styles')
    @include('layouts.warehouse.partials.ui-styles')

    @php
        $dayNames = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
        // Rupiah tanpa ,00 kalau bulat; qty tanpa nol di belakang koma (decimal:4)
        $rupiah = fn ($value) => 'Rp ' . number_format((float) $value, fmod((float) $value, 1) ? 2 : 0, ',', '.');
        $qty = fn ($value) => rtrim(rtrim(number_format((float) $value, 4, ',', '.'), '0'), ',');
        $hoursByDay = $supplier->operationalHours->groupBy('day_of_week');
        $canUpdate = auth()->user()->can('update warehouse/supplier');
    @endphp

    <div class="main-content ob-page">
        {{-- Header --}}
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">
                    <a href="{{ route('warehouse/supplier') }}">Supplier</a><span class="sp-crumb-sep" aria-hidden="true">/</span>{{ $supplier->code ?: 'Tanpa kode' }}
                </div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-truck"></i></span>
                    {{ $supplier->name }}
                </h1>
                <div class="sp-profile-meta">
                    <span class="badge {{ $supplier->is_active ? 'badge-success' : 'badge-muted' }}">{{ $supplier->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                    <span class="badge badge-primary">{{ $supplier->procurement_mode_label === 'Keduanya' ? 'Online & Offline' : $supplier->procurement_mode_label }}</span>
                    <span>Terakhir diubah {{ $supplier->updated_at?->translatedFormat('d M Y, H:i') ?? '-' }}</span>
                </div>
            </div>
            @if ($canUpdate)
                <div class="sp-actions">
                    <a href="{{ route('warehouse/supplier/edit', $supplier->id) }}" class="btn ob-btn ob-btn-outline action">
                        <i class="fas fa-pen" aria-hidden="true"></i> Edit Supplier
                    </a>
                    <a href="{{ route('warehouse/supplier/raw-materials/create', $supplier->id) }}" class="btn ob-btn ob-btn-primary action">
                        <i class="fas fa-plus" aria-hidden="true"></i> Tambah Bahan Baku
                    </a>
                </div>
            @endif
        </div>

        {{-- Ringkasan --}}
        <div class="ob-stats">
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--primary" aria-hidden="true"><i class="fas fa-boxes"></i></span>
                <div>
                    <p class="ob-stat__label">Bahan Baku</p>
                    <p class="ob-stat__value">{{ number_format($stats['materials'], 0, ',', '.') }}</p>
                    <p class="sp-stat-sub">{{ number_format($stats['active_materials'], 0, ',', '.') }} aktif</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--success" aria-hidden="true"><i class="fas fa-star"></i></span>
                <div>
                    <p class="ob-stat__label">Supplier Utama</p>
                    <p class="ob-stat__value">{{ number_format($stats['preferred'], 0, ',', '.') }}</p>
                    <p class="sp-stat-sub">bahan yang diprioritaskan</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--warning" aria-hidden="true"><i class="fas fa-shipping-fast"></i></span>
                <div>
                    <p class="ob-stat__label">Rata-rata Lead Time</p>
                    <p class="ob-stat__value">{{ $stats['avg_lead_time'] !== null ? str_replace('.', ',', (string) $stats['avg_lead_time']) . ' hari' : '-' }}</p>
                    <p class="sp-stat-sub">dari pesan sampai barang tiba</p>
                </div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--danger" aria-hidden="true"><i class="fas fa-chart-line"></i></span>
                <div>
                    <p class="ob-stat__label">Perubahan Harga</p>
                    <p class="ob-stat__value">{{ number_format($stats['price_changes'], 0, ',', '.') }}</p>
                    <p class="sp-stat-sub">{{ $stats['last_price_change'] ? 'Terakhir ' . $stats['last_price_change']->translatedFormat('d M Y') : 'Belum ada' }}</p>
                </div>
            </div>
        </div>

        <div class="sp-grid">
            {{-- Kolom utama --}}
            <div>
                <div class="ob-card">
                    <div class="wh-card__head">
                        <div>
                            <h2 class="wh-card__title"><i class="fas fa-boxes" aria-hidden="true"></i> Bahan Baku <span class="wh-count">{{ $supplier->rawMaterials->count() }}</span></h2>
                            <p class="wh-card__desc">Harga dan MOQ dihitung per satuan beli. Bahan bertanda Utama diprioritaskan saat procurement.</p>
                        </div>
                    </div>
                    @if ($supplier->rawMaterials->isEmpty())
                        <div class="wh-empty">
                            <span class="wh-empty__icon" aria-hidden="true"><i class="fas fa-boxes"></i></span>
                            <p class="wh-empty__title">Belum ada bahan baku</p>
                            <p class="wh-empty__text">Tambahkan bahan baku yang dijual supplier ini agar bisa dipilih di procurement plan.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="wh-table">
                                <thead>
                                    <tr>
                                        <th>Bahan Baku</th>
                                        <th>Satuan Beli</th>
                                        <th class="text-end">MOQ</th>
                                        <th>Lead Time</th>
                                        <th class="text-end">Harga</th>
                                        <th>Status</th>
                                        @if ($canUpdate)
                                            <th><span class="visually-hidden">Aksi</span></th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($supplier->rawMaterials as $item)
                                        @php
                                            $materialName = $item->rawMaterial?->name ?? ($item->supplier_material_name ?: 'Bahan #' . $item->id);
                                            $unitName = $item->purchaseUnit?->name;
                                            $aliases = collect([
                                                $item->supplier_material_name && $item->supplier_material_name !== $item->rawMaterial?->name ? 'Di supplier: ' . $item->supplier_material_name : null,
                                                $item->supplier_sku ? 'SKU ' . $item->supplier_sku : null,
                                            ])->filter();
                                        @endphp
                                        <tr class="{{ $item->is_active ? '' : 'wh-row-inactive' }}">
                                            <td>
                                                <div class="wh-cell-stack">
                                                    <span class="wh-strong">
                                                        {{ $materialName }}
                                                        @if ($item->is_preferred)
                                                            <span class="badge badge-primary sp-badge-inline" title="Supplier utama untuk bahan ini"><i class="fas fa-star" aria-hidden="true"></i> Utama</span>
                                                        @endif
                                                    </span>
                                                    @if ($aliases->isNotEmpty())
                                                        <span class="ob-cell-meta">{{ $aliases->implode(' · ') }}</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>{{ $unitName ?? '-' }}</td>
                                            <td class="text-end wh-num">{{ (float) $item->minimum_order_qty > 0 ? $qty($item->minimum_order_qty) : '-' }}</td>
                                            <td class="text-nowrap">{{ $item->lead_time_days > 0 ? $item->lead_time_days . ' hari' : 'Hari itu juga' }}</td>
                                            <td class="text-end">
                                                @if ($item->current_price !== null)
                                                    <div class="wh-cell-stack wh-cell-stack--end">
                                                        <span class="wh-strong wh-num">{{ $rupiah($item->current_price) }}</span>
                                                        <span class="ob-cell-meta">{{ $unitName ? '/ ' . $unitName : '' }}{{ $item->price_updated_at ? ' · ' . \Illuminate\Support\Carbon::parse($item->price_updated_at)->translatedFormat('d M Y') : '' }}</span>
                                                    </div>
                                                @else
                                                    <span class="ob-cell-meta">Belum diisi</span>
                                                @endif
                                            </td>
                                            <td><span class="badge {{ $item->is_active ? 'badge-success' : 'badge-muted' }}">{{ $item->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                            @if ($canUpdate)
                                                <td>
                                                    <div class="ob-row-actions" role="group" aria-label="Aksi bahan {{ $materialName }}">
                                                        <a href="{{ route('warehouse/supplier/raw-materials/edit', [$supplier->id, $item->id]) }}" class="ob-icon-btn ob-icon-btn--sm action"
                                                            title="Edit bahan baku" aria-label="Edit bahan baku"><i class="fas fa-pen" aria-hidden="true"></i></a>
                                                        <a href="{{ route('warehouse/supplier/raw-materials/destroy', [$supplier->id, $item->id]) }}" class="ob-icon-btn ob-icon-btn--sm ob-icon-btn--danger delete-raw-material"
                                                            data-name="{{ $materialName }}" title="Hapus bahan baku" aria-label="Hapus bahan baku"><i class="fas fa-trash" aria-hidden="true"></i></a>
                                                    </div>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="ob-card">
                    <div class="wh-card__head">
                        <div>
                            <h2 class="wh-card__title"><i class="fas fa-history" aria-hidden="true"></i> Histori Harga <span class="wh-count">{{ $priceHistories->count() }}</span></h2>
                            <p class="wh-card__desc">Tercatat otomatis setiap kali harga bahan diisi atau diubah. Terbaru di atas.</p>
                        </div>
                    </div>
                    @if ($priceHistories->isEmpty())
                        <div class="wh-empty">
                            <span class="wh-empty__icon" aria-hidden="true"><i class="fas fa-history"></i></span>
                            <p class="wh-empty__title">Belum ada histori harga</p>
                            <p class="wh-empty__text">Histori muncul setelah harga bahan baku diisi.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="wh-table">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Bahan Baku</th>
                                        <th class="text-end">Harga</th>
                                        <th>Perubahan</th>
                                        <th>Diubah oleh</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($priceHistories as $history)
                                        @php
                                            $historyMaterial = $history->supplierRawMaterial;
                                            $price = (float) $history->price;
                                            $previous = $history->previous_price;
                                            $diff = $previous !== null ? $price - $previous : null;
                                            $percent = $diff !== null && $previous > 0 ? abs($diff) / $previous * 100 : null;
                                        @endphp
                                        <tr>
                                            <td class="text-nowrap">{{ $history->effective_from?->translatedFormat('d M Y') ?? '-' }}</td>
                                            <td>
                                                <div class="wh-cell-stack">
                                                    <span class="wh-strong">{{ $historyMaterial?->rawMaterial?->name ?? ($historyMaterial?->supplier_material_name ?: '-') }}</span>
                                                    @if ($historyMaterial?->purchaseUnit)
                                                        <span class="ob-cell-meta">per {{ $historyMaterial->purchaseUnit->name }}</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-end wh-strong wh-num">{{ $rupiah($price) }}</td>
                                            <td class="text-nowrap">
                                                @if ($diff === null)
                                                    <span class="sp-trend sp-trend--flat">Harga awal</span>
                                                @elseif ($diff > 0)
                                                    <span class="sp-trend sp-trend--up" title="Naik dari {{ $rupiah($previous) }}">
                                                        <i class="fas fa-arrow-up" aria-hidden="true"></i> {{ $rupiah($diff) }}{{ $percent !== null ? ' · ' . number_format($percent, 1, ',', '.') . '%' : '' }}
                                                    </span>
                                                @elseif ($diff < 0)
                                                    <span class="sp-trend sp-trend--down" title="Turun dari {{ $rupiah($previous) }}">
                                                        <i class="fas fa-arrow-down" aria-hidden="true"></i> {{ $rupiah(abs($diff)) }}{{ $percent !== null ? ' · ' . number_format($percent, 1, ',', '.') . '%' : '' }}
                                                    </span>
                                                @else
                                                    <span class="sp-trend sp-trend--flat">Tetap</span>
                                                @endif
                                            </td>
                                            <td>{{ $history->updatedBy?->name ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Kolom samping --}}
            <aside>
                <div class="ob-card">
                    <div class="wh-card__head">
                        <h2 class="wh-card__title"><i class="fas fa-id-card" aria-hidden="true"></i> Profil Supplier</h2>
                    </div>
                    <div class="wh-card__body">
                        <dl class="sp-dl">
                            <div class="sp-dl__row"><dt>Kode</dt><dd>{{ $supplier->code }}</dd></div>
                            <div class="sp-dl__row"><dt>Mode</dt><dd>{{ $supplier->procurement_mode_label === 'Keduanya' ? 'Online & Offline' : $supplier->procurement_mode_label }}</dd></div>
                            <div class="sp-dl__row"><dt>Dibuat</dt><dd>{{ $supplier->created_at?->translatedFormat('d M Y') }}</dd></div>
                            <div class="sp-dl__row"><dt>Catatan</dt><dd class="sp-notes">{{ $supplier->notes }}</dd></div>
                        </dl>
                    </div>
                </div>

                <div class="ob-card">
                    <div class="wh-card__head">
                        <h2 class="wh-card__title"><i class="fas fa-address-book" aria-hidden="true"></i> Kontak <span class="wh-count">{{ $supplier->contacts->count() }}</span></h2>
                    </div>
                    @if ($supplier->contacts->isEmpty())
                        <p class="wh-side-empty">Belum ada kontak yang tercatat.</p>
                    @else
                        <ul class="sp-list">
                            @foreach ($supplier->contacts as $contact)
                                <li class="sp-list__item {{ $contact->is_active ? '' : 'wh-row-inactive' }}">
                                    <div class="sp-list__top">
                                        <span class="wh-strong">{{ $contact->name ?: 'Tanpa nama' }}</span>
                                        @if ($contact->is_primary)
                                            <span class="badge badge-primary">Utama</span>
                                        @endif
                                        @unless ($contact->is_active)
                                            <span class="badge badge-muted">Nonaktif</span>
                                        @endunless
                                    </div>
                                    @if ($contact->position)
                                        <span class="ob-cell-meta">{{ $contact->position }}</span>
                                    @endif
                                    @if ($contact->phone)
                                        <span class="sp-list__line"><i class="fas fa-phone" aria-hidden="true"></i> {{ $contact->phone }}</span>
                                    @endif
                                    @if ($contact->whatsapp_phone)
                                        <span class="sp-list__line"><i class="fab fa-whatsapp" aria-hidden="true"></i> {{ $contact->whatsapp_phone }}</span>
                                    @endif
                                    @if ($contact->email)
                                        <span class="sp-list__line"><i class="fas fa-envelope" aria-hidden="true"></i> {{ $contact->email }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div class="ob-card">
                    <div class="wh-card__head">
                        <h2 class="wh-card__title"><i class="fas fa-shopping-cart" aria-hidden="true"></i> Tempat Pemesanan <span class="wh-count">{{ $supplier->orderChannels->count() }}</span></h2>
                    </div>
                    @if ($supplier->orderChannels->isEmpty())
                        <p class="wh-side-empty">Belum ada tempat pemesanan yang tercatat.</p>
                    @else
                        <ul class="sp-list">
                            @foreach ($supplier->orderChannels as $channel)
                                @php
                                    $channelUrl = $channel->url && \Illuminate\Support\Str::startsWith(strtolower($channel->url), ['http://', 'https://']) ? $channel->url : null;
                                @endphp
                                <li class="sp-list__item {{ $channel->is_active ? '' : 'wh-row-inactive' }}">
                                    <div class="sp-list__top">
                                        <span class="wh-strong">{{ $channel->channel_name ?: ($channel->channelType?->name ?? 'Channel') }}</span>
                                        @if ($channel->is_primary)
                                            <span class="badge badge-primary">Utama</span>
                                        @endif
                                        @unless ($channel->is_active)
                                            <span class="badge badge-muted">Nonaktif</span>
                                        @endunless
                                    </div>
                                    @if ($channel->channel_name && $channel->channelType)
                                        <span class="ob-cell-meta">{{ $channel->channelType->name }}</span>
                                    @endif
                                    @if ($channel->identifier)
                                        <span class="sp-list__line"><i class="fas fa-at" aria-hidden="true"></i> {{ $channel->identifier }}</span>
                                    @endif
                                    @if ($channelUrl)
                                        <a class="sp-list__line" href="{{ $channelUrl }}" target="_blank" rel="noopener noreferrer"><i class="fas fa-external-link-alt" aria-hidden="true"></i> Buka link</a>
                                    @endif
                                    @if ($channel->address)
                                        <span class="sp-list__line"><i class="fas fa-map-marker-alt" aria-hidden="true"></i> {{ $channel->address }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div class="ob-card">
                    <div class="wh-card__head">
                        <h2 class="wh-card__title"><i class="far fa-clock" aria-hidden="true"></i> Jam Operasional</h2>
                    </div>
                    @if ($supplier->operationalHours->isEmpty())
                        <p class="wh-side-empty">Jam operasional belum diatur.</p>
                    @else
                        <ul class="sp-hours">
                            @foreach ($dayNames as $day => $dayName)
                                @php $slots = $hoursByDay->get($day, collect()); @endphp
                                <li class="sp-hours__row {{ $slots->isEmpty() ? 'is-closed' : '' }}">
                                    <span>{{ $dayName }}</span>
                                    <span class="wh-num">
                                        @if ($slots->isEmpty())
                                            Tutup
                                        @else
                                            {{ $slots->map(fn ($slot) => $slot->is_24_hours
                                                ? '24 jam'
                                                : ($slot->open_time && $slot->close_time
                                                    ? substr((string) $slot->open_time, 0, 5) . '–' . substr((string) $slot->close_time, 0, 5)
                                                    : 'Jam belum diisi'))->implode(', ') }}
                                        @endif
                                    </span>
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

                $('.main-content').on('click', '.delete-raw-material', function (event) {
                    event.preventDefault();
                    const button = this;

                    Swal.fire({
                        title: 'Hapus bahan baku?',
                        text: $(button).data('name') + ' akan dihapus dari supplier ini. Histori harganya tidak lagi ditampilkan.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, hapus',
                        cancelButtonText: 'Batal'
                    }).then(function (result) {
                        if (!result.isConfirmed) return;

                        $.ajax({
                            url: button.href,
                            method: 'DELETE',
                            success: function (response) {
                                showToast(response.status || 'success', response.message || 'Bahan baku dihapus dari supplier');
                                location.reload();
                            },
                            error: function (xhr) {
                                showToast('error', xhr.responseJSON?.message || 'Gagal menghapus bahan baku');
                            }
                        });
                    });
                });
            });
        </script>
    @endpush

    @push('css')
        <style>
            .sp-crumb-sep { margin: 0 .3rem; color: var(--ob-border-strong); }
            .ob-header__eyebrow a { color: inherit; text-decoration: none; }
            .ob-header__eyebrow a:hover { color: var(--ob-primary); }

            .sp-profile-meta { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem .6rem; margin-top: .45rem; font-size: .8rem; color: var(--ob-muted); }
            .sp-actions { display: flex; flex-wrap: wrap; gap: .5rem; }

            .ob-page .ob-stats .ob-stat { margin-bottom: 0; }
            .sp-stat-sub { margin: .1rem 0 0; font-size: .76rem; color: var(--ob-muted); }

            .sp-grid { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 1.25rem; align-items: start; margin-top: 1.25rem; }
            .sp-grid .ob-card { margin-bottom: 1.25rem; }
            @media (max-width: 1199.98px) { .sp-grid { grid-template-columns: minmax(0, 1fr); } }

            .ob-page .sp-badge-inline { margin-left: .35rem; vertical-align: 1px; }
            .ob-page .sp-badge-inline::before { display: none; }

            .sp-trend { display: inline-flex; align-items: center; gap: .3rem; padding: .15rem .5rem; border-radius: 999px; font-size: .75rem; font-weight: 600; font-variant-numeric: tabular-nums; }
            .sp-trend i { font-size: .65rem; }
            /* Harga naik = biaya bertambah (merah), turun = hemat (hijau) */
            .sp-trend--up { background: var(--ob-danger-soft); color: var(--ob-danger); }
            .sp-trend--down { background: var(--ob-success-soft); color: var(--ob-success); }
            .sp-trend--flat { background: var(--ob-surface-2); color: var(--ob-muted); border: 1px solid var(--ob-border); }

            /* ----- Kolom samping ----- */
            .sp-dl { margin: 0; }
            .sp-dl__row { display: grid; grid-template-columns: 80px minmax(0, 1fr); gap: .75rem; padding: .6rem 0; border-top: 1px solid var(--ob-border); }
            .sp-dl__row:first-child { border-top: 0; padding-top: 0; }
            .sp-dl__row:last-child { padding-bottom: 0; }
            .sp-dl dt { font-size: .8rem; font-weight: 500; color: var(--ob-muted); }
            .sp-dl dd { margin: 0; font-size: .85rem; font-weight: 500; color: var(--ob-text); overflow-wrap: anywhere; }
            .sp-dl dd:empty::before { content: "—"; font-weight: 400; color: var(--ob-muted); }
            .sp-notes { white-space: pre-line; font-weight: 400 !important; }

            .sp-list { list-style: none; margin: 0; padding: 0; }
            .sp-list__item { display: flex; flex-direction: column; gap: .2rem; padding: .8rem 1.25rem; border-top: 1px solid var(--ob-border); font-size: .82rem; }
            .sp-list__item:first-child { border-top: 0; }
            .sp-list__top { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; }
            .sp-list__line { display: inline-flex; align-items: baseline; gap: .45rem; color: var(--ob-text-2); overflow-wrap: anywhere; text-decoration: none; }
            .sp-list__line i { width: .9rem; flex: none; text-align: center; font-size: .75rem; color: var(--ob-muted); }
            a.sp-list__line { color: var(--ob-primary); font-weight: 600; }

            .sp-hours { list-style: none; margin: 0; padding: .4rem 1.25rem .8rem; }
            .sp-hours__row { display: flex; justify-content: space-between; gap: 1rem; padding: .4rem 0; font-size: .82rem; color: var(--ob-text); }
            .sp-hours__row.is-closed { color: var(--ob-muted); }

            /* ----- Modal bahan baku: prefix Rp ----- */
            .ob-modal .input-group-text.sp-input-prefix { border-radius: var(--ob-radius-sm) 0 0 var(--ob-radius-sm); background: var(--ob-surface-2); color: var(--ob-muted); font-size: .85rem; }
            .ob-modal .input-group > .form-control.sp-input-prefixed { border-radius: 0 var(--ob-radius-sm) var(--ob-radius-sm) 0; }
        </style>
    @endpush
@endsection
