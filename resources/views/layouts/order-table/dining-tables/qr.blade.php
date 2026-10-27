{{-- ob-page memberi token & gaya report ke modal (modal dirender di luar .main-content) --}}
<div class="modal-dialog modal-dialog-centered ob-page ob-modal" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">QR Meja {{ $diningTable->code }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body pb-3">
            @if (!$diningTable->qr_active)
                <div class="ob-notice" role="note">
                    <i class="fas fa-ban" aria-hidden="true"></i>
                    <span>QR meja sedang <strong>nonaktif</strong>. Pelanggan tidak bisa memesan dari QR ini.</span>
                </div>
            @endif

            <div class="ob-qr-frame {{ $diningTable->qr_active ? '' : 'is-inactive' }}">
                <div class="ob-qr-frame__code">{!! $qrSvg !!}</div>
            </div>

            <dl class="ob-meta">
                <div class="ob-meta__item">
                    <dt>Meja</dt>
                    <dd>{{ $diningTable->name }}</dd>
                </div>
                <div class="ob-meta__item">
                    <dt>Outlet</dt>
                    <dd>{{ $diningTable->outlet?->name }}</dd>
                </div>
                <div class="ob-meta__item">
                    <dt>Kapasitas</dt>
                    <dd>{{ $diningTable->capacity ? $diningTable->capacity.' orang' : '' }}</dd>
                </div>
                <div class="ob-meta__item">
                    <dt>Status QR</dt>
                    <dd>
                        <span class="badge {{ $diningTable->qr_active ? 'badge-success' : 'badge-muted' }}">
                            {{ $diningTable->qr_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </dd>
                </div>
            </dl>

            <h3 class="ob-section-title"><i class="fas fa-link" aria-hidden="true"></i> Tautan QR</h3>
            <code class="ob-qr-url">{{ $qrUrl }}</code>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Tutup</button>
            @can('download order-table/dining-tables')
                <a href="{{ route('order-table/dining-tables/qr/download', $diningTable->id) }}" class="btn ob-btn ob-btn-outline">
                    <i class="fas fa-download" aria-hidden="true"></i> Download
                </a>
            @endcan
            @can('print order-table/dining-tables')
                <a href="{{ route('order-table/dining-tables/qr/print', $diningTable->id) }}" target="_blank" rel="noopener" class="btn btn-primary">
                    <i class="fas fa-print me-1" aria-hidden="true"></i> Cetak
                </a>
            @endcan
        </div>
    </div>
</div>
