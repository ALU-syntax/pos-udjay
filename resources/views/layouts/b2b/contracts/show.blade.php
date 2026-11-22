@extends('layouts.app')

@section('content')
    @include('layouts.b2b.partials.assets')
    @include('layouts.b2b.partials.page-styles')
    @include('layouts.b2b.contracts.partials.styles')

    @php
        $customer = $contract->customer;
        $rupiah = fn ($value) => 'Rp '.number_format($value, 0, ',', '.');
        $variantLabel = fn ($variant) => \App\Http\Controllers\B2b\CatalogSelectorController::variantLabel($variant);
        // Satu baris per varian, tingkat qty di dalamnya. Urut outlet lalu nama produk.
        $groups = $contract->items
            ->groupBy('variant_id')
            ->map(fn ($tiers) => ['variant' => $tiers->first()->variant, 'tiers' => $tiers->sortBy('min_qty')->values()])
            ->sortBy(fn ($group) => [($group['variant']?->product?->outlet?->name ?? ''), ($group['variant'] ? $variantLabel($group['variant']) : '')])
            ->values();
        $outletCount = $groups->map(fn ($group) => $group['variant']?->product?->outlet_id)->filter()->unique()->count();
        $canTerminate = !$contract->isTerminated() && !$contract->end_date->lt(today());
    @endphp

    <div class="main-content ob-page">
        <div class="ob-header">
            <div class="b2b-profile">
                <span class="b2b-doc-icon b2b-doc-icon--lg b2b-doc-icon--{{ $contract->statusVariant() }}" aria-hidden="true"><i class="fas fa-file-signature"></i></span>
                <div>
                    <div class="ob-header__eyebrow">
                        <a href="{{ route('b2b/contracts') }}">Kontrak B2B</a><span class="b2b-crumb-sep" aria-hidden="true">/</span>Detail
                    </div>
                    <h1 class="ob-header__title b2b-mono">{{ $contract->contract_number }}</h1>
                    <div class="b2b-profile__meta">
                        <span class="badge badge-{{ $contract->statusVariant() }}">{{ $contract->statusLabel() }}</span>
                        <span>Customer <a href="{{ route('b2b/customers/show', $contract->b2b_customer_id) }}">{{ $customer?->name }}</a></span>
                        <span aria-hidden="true">·</span>
                        <span>{{ $contract->periodHint() }}</span>
                    </div>
                </div>
            </div>
            <div class="b2b-actions">
                @if ($canTerminate)
                    @can('terminate b2b/contracts')
                        <a href="{{ route('b2b/contracts/terminate', $contract->id) }}" class="btn ob-btn ob-btn-outline" data-b2b-confirm data-reason="Alasan penghentian"
                            data-title="Hentikan kontrak?" data-text="Pesanan berikutnya memakai harga normal sampai ada kontrak baru." data-confirm-text="Ya, hentikan">
                            <i class="fas fa-ban" aria-hidden="true"></i> Hentikan</a>
                    @endcan
                @endif
                @if ($successor)
                    <a href="{{ route('b2b/contracts/show', $successor->id) }}" class="btn ob-btn ob-btn-outline" title="Kontrak ini sudah diperpanjang">
                        <i class="fas fa-arrow-right" aria-hidden="true"></i> Lanjutan: {{ $successor->contract_number }}</a>
                @else
                    @can('create b2b/contracts')
                        <a href="{{ route('b2b/contracts/create', ['copy_from' => $contract->id]) }}" class="btn ob-btn ob-btn-outline"><i class="fas fa-copy" aria-hidden="true"></i> Perpanjang</a>
                    @endcan
                @endif
                @if (!$contract->isTerminated())
                    @can('update b2b/contracts')
                        <a href="{{ route('b2b/contracts/edit', $contract->id) }}" class="btn ob-btn ob-btn-primary"><i class="fas fa-pen" aria-hidden="true"></i> Edit Kontrak</a>
                    @endcan
                @endif
            </div>
        </div>

        @if ($contract->isTerminated())
            <div class="b2b-terminated mb-3" role="note">
                <i class="fas fa-ban" aria-hidden="true"></i>
                <div>
                    <strong>Kontrak dihentikan {{ $contract->terminated_at?->translatedFormat('d M Y, H:i') }}{{ $contract->terminator ? ' oleh '.$contract->terminator->name : '' }}</strong>
                    Alasan: {{ $contract->termination_reason }}. Harga di kontrak ini tidak lagi dipakai di kasir.
                </div>
            </div>
        @endif

        <div class="ob-stats">
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--primary" aria-hidden="true"><i class="fas fa-tags"></i></span>
                <div><p class="ob-stat__label">Produk di kontrak</p><p class="ob-stat__value">{{ number_format($groups->count(), 0, ',', '.') }}</p></div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--success" aria-hidden="true"><i class="fas fa-store"></i></span>
                <div><p class="ob-stat__label">Outlet</p><p class="ob-stat__value">{{ number_format($outletCount, 0, ',', '.') }}</p></div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--warning" aria-hidden="true"><i class="fas fa-shopping-bag"></i></span>
                <div><p class="ob-stat__label">Pesanan memakai kontrak</p><p class="ob-stat__value">{{ number_format($orderCount, 0, ',', '.') }}</p></div>
            </div>
            <div class="ob-card ob-stat">
                <span class="ob-stat__icon ob-stat__icon--danger" aria-hidden="true"><i class="fas fa-calendar-day"></i></span>
                <div><p class="ob-stat__label">Lama kontrak</p><p class="ob-stat__value">{{ number_format($contract->durationDays(), 0, ',', '.') }} <small>hari</small></p></div>
            </div>
        </div>

        <div class="b2b-grid-2">
            <div>
                <div class="ob-card">
                    <div class="b2b-card__head">
                        <div>
                            <h2 class="b2b-card__title"><i class="fas fa-tags" aria-hidden="true"></i> Harga Kontrak <span class="b2b-count">{{ $groups->count() }}</span></h2>
                            <p class="b2b-card__desc">Produk di luar daftar ini tetap memakai harga normal. Selisih dihitung dari harga normal saat ini.</p>
                        </div>
                    </div>
                    @if ($groups->isEmpty())
                        <div class="b2b-empty">
                            <span class="b2b-empty__icon" aria-hidden="true"><i class="fas fa-tags"></i></span>
                            <p class="b2b-empty__title">Belum ada harga produk</p>
                            <p class="b2b-empty__text">Edit kontrak untuk menambahkan harga khusus.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="b2b-table b2b-table--card b2b-price-table">
                                <thead><tr><th>Produk / varian</th><th class="text-end">Harga normal</th><th class="text-end">Harga kontrak</th></tr></thead>
                                <tbody>
                                    @foreach ($groups as $group)
                                        @php $variant = $group['variant']; $normal = $variant ? (int) $variant->harga : null; @endphp
                                        <tr>
                                            <td>
                                                <div class="b2b-price-table__product">
                                                    {{ $variant ? $variantLabel($variant) : 'Varian #'.$group['tiers']->first()->variant_id }}
                                                    @if ($variant?->trashed())<span class="badge badge-muted ms-1">dihapus</span>@endif
                                                </div>
                                                <div class="ob-cell-meta"><i class="fas fa-store me-1" aria-hidden="true"></i>{{ $variant?->product?->outlet?->name ?? 'Tanpa outlet' }}</div>
                                            </td>
                                            <td class="text-end b2b-money">{{ $normal !== null ? $rupiah($normal) : '—' }}</td>
                                            <td>
                                                <div class="b2b-tiers">
                                                    @foreach ($group['tiers'] as $tier)
                                                        @php $delta = $normal !== null ? $tier->price - $normal : null; @endphp
                                                        <div class="b2b-tier">
                                                            <span class="b2b-tier__qty">{{ $tier->min_qty > 1 ? 'qty ≥ '.number_format($tier->min_qty, 0, ',', '.') : 'Semua qty' }}</span>
                                                            <span class="b2b-tier__price">{{ $rupiah($tier->price) }}</span>
                                                            @if ($delta !== null)
                                                                <span class="b2b-delta {{ $delta < 0 ? 'is-down' : ($delta > 0 ? 'is-up' : 'is-same') }}">
                                                                    {{ $delta === 0 ? 'sama' : ($delta < 0 ? '−' : '+').number_format(abs($delta), 0, ',', '.') }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div>
                <div class="ob-card">
                    <div class="b2b-card__head">
                        <h2 class="b2b-card__title"><i class="fas fa-calendar-alt" aria-hidden="true"></i> Periode</h2>
                    </div>
                    <div class="b2b-hero-period">
                        @include('layouts.b2b.contracts.partials.period', ['contract' => $contract, 'large' => true])
                    </div>
                </div>

                <div class="ob-card">
                    <div class="b2b-card__head">
                        <h2 class="b2b-card__title"><i class="fas fa-info-circle" aria-hidden="true"></i> Detail Kontrak</h2>
                    </div>
                    <div class="b2b-card__body">
                        <dl class="b2b-dl">
                            <div class="b2b-dl__row">
                                <dt>Customer</dt>
                                <dd><a href="{{ route('b2b/customers/show', $contract->b2b_customer_id) }}">{{ $customer?->name }}</a><div class="ob-cell-meta">{{ $customer?->code }}</div></dd>
                            </div>
                            <div class="b2b-dl__row">
                                <dt>File</dt>
                                <dd>@if ($contract->attachment_path)<a href="{{ route('b2b/contracts/attachment', $contract->id) }}"><i class="fas fa-paperclip me-1" aria-hidden="true"></i>{{ $contract->attachment_name }}</a>@endif</dd>
                            </div>
                            <div class="b2b-dl__row"><dt>Catatan</dt><dd>{{ $contract->notes }}</dd></div>
                        </dl>
                    </div>
                    <div class="b2b-card__foot">
                        Dibuat {{ $contract->created_at?->translatedFormat('d M Y, H:i') }}{{ $contract->creator ? ' oleh '.$contract->creator->name : '' }}
                        @if ($contract->modified_by)
                            <br>Diubah {{ $contract->updated_at?->translatedFormat('d M Y, H:i') }}{{ $contract->modifier ? ' oleh '.$contract->modifier->name : '' }}
                        @endif
                    </div>
                </div>

                @can('delete b2b/contracts')
                    <div class="ob-card">
                        <div class="b2b-danger-zone">
                            <div>
                                <p class="b2b-danger-zone__title">Hapus kontrak</p>
                                <p class="b2b-danger-zone__text">
                                    @if ($contract->isRunning())
                                        Kontrak sedang berjalan, jadi tidak bisa dihapus. Hentikan kontrak dulu kalau memang tidak dipakai lagi.
                                    @else
                                        Kontrak disembunyikan dari daftar. Pesanan lama tetap menyimpan harganya.
                                    @endif
                                </p>
                            </div>
                            @unless ($contract->isRunning())
                                <a href="{{ route('b2b/contracts/destroy', $contract->id) }}" class="btn ob-btn ob-btn-danger ob-btn-sm" data-b2b-confirm data-method="DELETE"
                                    data-title="Hapus kontrak {{ $contract->contract_number }}?" data-text="Kontrak disembunyikan dari daftar. Pesanan lama tetap menyimpan harganya." data-confirm-text="Ya, hapus">
                                    <i class="fas fa-trash" aria-hidden="true"></i> Hapus
                                </a>
                            @endunless
                        </div>
                    </div>
                @endcan
            </div>
        </div>
    </div>
@endsection
