@extends('layouts.app')

@section('content')
    @include('layouts.b2b.partials.assets')

    <div class="main-content ob-page">
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow"><a href="{{ route('b2b/contracts') }}">Kontrak B2B</a></div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-file-signature"></i></span>
                    {{ $contract->contract_number }}
                    <span class="badge badge-{{ $contract->statusVariant() }} ms-2">{{ $contract->statusLabel() }}</span>
                </h1>
                <p class="ob-header__desc">
                    <a href="{{ route('b2b/customers/show', $contract->b2b_customer_id) }}">{{ $contract->customer?->name }}</a>
                    · {{ $contract->start_date->translatedFormat('d M Y') }} – {{ $contract->end_date->translatedFormat('d M Y') }}
                </p>
            </div>
            <div class="b2b-actions">
                @if ($contract->isDraft())
                    @can('delete b2b/contracts')
                        <a href="{{ route('b2b/contracts/destroy', $contract->id) }}" class="btn ob-btn ob-btn-danger" data-b2b-confirm data-method="DELETE"
                            data-title="Hapus kontrak draft?" data-confirm-text="Ya, hapus"><i class="fas fa-trash" aria-hidden="true"></i> Hapus</a>
                    @endcan
                    @can('update b2b/contracts')
                        <a href="{{ route('b2b/contracts/edit', $contract->id) }}" class="btn ob-btn ob-btn-outline"><i class="fas fa-pen" aria-hidden="true"></i> Edit</a>
                    @endcan
                    @can('activate b2b/contracts')
                        <a href="{{ route('b2b/contracts/activate', $contract->id) }}" class="btn ob-btn ob-btn-primary" data-b2b-confirm
                            data-title="Aktifkan kontrak?" data-text="Setelah aktif, diskon kontrak tidak bisa diedit." data-confirm-text="Ya, aktifkan">
                            <i class="fas fa-check" aria-hidden="true"></i> Aktifkan</a>
                    @endcan
                @endif
                @if ($contract->isActive())
                    @can('terminate b2b/contracts')
                        <a href="{{ route('b2b/contracts/terminate', $contract->id) }}" class="btn ob-btn ob-btn-danger" data-b2b-confirm data-reason="Alasan penghentian"
                            data-title="Hentikan kontrak?" data-text="Pesanan berikutnya memakai harga normal sampai ada kontrak baru." data-confirm-text="Ya, hentikan">
                            <i class="fas fa-ban" aria-hidden="true"></i> Hentikan</a>
                    @endcan
                @endif
                @if (!$contract->isDraft())
                    @can('create b2b/contracts')
                        <a href="{{ route('b2b/contracts/create', ['copy_from' => $contract->id]) }}" class="btn ob-btn ob-btn-outline"><i class="fas fa-copy" aria-hidden="true"></i> Perpanjang / Salin</a>
                    @endcan
                @endif
            </div>
        </div>

        <div class="b2b-grid-2">
            <div class="ob-card">
                <h2 class="ob-section-title"><i class="fas fa-percent" aria-hidden="true"></i> Aturan Diskon ({{ $contract->rules->count() }})</h2>
                @if ($contract->rules->isEmpty())
                    <p class="b2b-muted mb-0">Tanpa aturan diskon.</p>
                @else
                    <div class="table-responsive">
                        <table class="b2b-table">
                            <thead><tr><th>Cakupan</th><th>Target</th><th>Outlet</th><th>Mulai qty</th><th>Diskon</th></tr></thead>
                            <tbody>
                                @foreach ($contract->rules->sortBy(fn ($rule) => [$rule->specificity(), $rule->min_qty]) as $rule)
                                    <tr>
                                        <td>{{ ['all' => 'Semua produk', 'category' => 'Kategori', 'product' => 'Produk', 'variant' => 'Varian'][$rule->scope] }}</td>
                                        <td>{{ $rule->targetLabel() }}</td>
                                        <td>{{ $rule->outlet?->name ?? 'Semua outlet' }}</td>
                                        <td>{{ number_format($rule->min_qty, 0, ',', '.') }}</td>
                                        <td class="fw-bold">{{ $rule->discountLabel() }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="ob-card">
                <h2 class="ob-section-title"><i class="fas fa-info-circle" aria-hidden="true"></i> Detail</h2>
                <dl class="ob-meta" style="grid-template-columns: 1fr;">
                    <div class="ob-meta__item"><dt>Pesanan memakai kontrak ini</dt><dd>{{ number_format($orderCount, 0, ',', '.') }}</dd></div>
                    <div class="ob-meta__item">
                        <dt>File kontrak</dt>
                        <dd>
                            @if ($contract->attachment_path)
                                <a href="{{ route('b2b/contracts/attachment', $contract->id) }}"><i class="fas fa-paperclip" aria-hidden="true"></i> {{ $contract->attachment_name }}</a>
                            @endif
                        </dd>
                    </div>
                    <div class="ob-meta__item"><dt>Catatan</dt><dd>{{ $contract->notes }}</dd></div>
                </dl>
                <ul class="b2b-list b2b-muted">
                    <li>Dibuat {{ $contract->created_at?->translatedFormat('d M Y H:i') }}{{ $contract->creator ? ' oleh '.$contract->creator->name : '' }}</li>
                    @if ($contract->activated_at)
                        <li>Diaktifkan {{ $contract->activated_at->translatedFormat('d M Y H:i') }}{{ $contract->activator ? ' oleh '.$contract->activator->name : '' }}</li>
                    @endif
                    @if ($contract->terminated_at)
                        <li>Dihentikan {{ $contract->terminated_at->translatedFormat('d M Y H:i') }}{{ $contract->terminator ? ' oleh '.$contract->terminator->name : '' }}: {{ $contract->termination_reason }}</li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
@endsection
