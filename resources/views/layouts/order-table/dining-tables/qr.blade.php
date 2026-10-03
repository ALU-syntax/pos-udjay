<div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
        <div class="modal-header border-0">
            <div>
                <h5 class="modal-title">QR Meja {{ $diningTable->code }}</h5>
                <small class="text-muted">{{ $diningTable->outlet?->name }} - {{ $diningTable->name }}</small>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body text-center pt-0">
            <div class="border rounded p-4 bg-white d-inline-block mb-3">{!! $qrSvg !!}</div>
            <p class="small text-muted mb-3 text-break">{{ $qrUrl }}</p>
            @if (!$diningTable->qr_active)
                <div class="alert alert-warning text-start"><i class="fa fa-ban me-2"></i>QR meja sedang nonaktif.</div>
            @endif
        </div>
        <div class="modal-footer border-0">
            @can('download order-table/dining-tables')
                <a href="{{ route('order-table/dining-tables/qr/download', $diningTable->id) }}" class="btn btn-outline-primary">
                    <i class="fa fa-download me-1"></i>Download
                </a>
            @endcan
            @can('print order-table/dining-tables')
                <a href="{{ route('order-table/dining-tables/qr/print', $diningTable->id) }}" target="_blank" rel="noopener" class="btn btn-primary">
                    <i class="fa fa-print me-1"></i>Cetak
                </a>
            @endcan
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
        </div>
    </div>
</div>
