<div class="ob-row-actions" role="group" aria-label="Aksi meja {{ $table->code }}">
    @can('read order-table/dining-tables')
        <a href="{{ route('order-table/dining-tables/qr', $table->id) }}" class="ob-icon-btn ob-icon-btn--sm qr-preview"
            title="Lihat QR" aria-label="Lihat QR"><i class="fas fa-qrcode" aria-hidden="true"></i></a>
    @endcan
    @can('download order-table/dining-tables')
        <a href="{{ route('order-table/dining-tables/qr/download', $table->id) }}" class="ob-icon-btn ob-icon-btn--sm"
            title="Download QR" aria-label="Download QR"><i class="fas fa-download" aria-hidden="true"></i></a>
    @endcan
    @can('print order-table/dining-tables')
        <a href="{{ route('order-table/dining-tables/qr/print', $table->id) }}" target="_blank" rel="noopener"
            class="ob-icon-btn ob-icon-btn--sm" title="Cetak QR" aria-label="Cetak QR"><i class="fas fa-print" aria-hidden="true"></i></a>
    @endcan
    @can('update order-table/dining-tables')
        <a href="{{ route('order-table/dining-tables/edit', $table->id) }}" class="ob-icon-btn ob-icon-btn--sm action"
            title="Edit meja" aria-label="Edit meja"><i class="fas fa-pen" aria-hidden="true"></i></a>
        <a href="{{ route('order-table/dining-tables/toggle-qr', $table->id) }}"
            class="ob-icon-btn ob-icon-btn--sm toggle-qr {{ $table->qr_active ? 'ob-icon-btn--danger' : 'ob-icon-btn--success' }}"
            data-active="{{ $table->qr_active ? 1 : 0 }}"
            title="{{ $table->qr_active ? 'Nonaktifkan QR' : 'Aktifkan QR' }}"
            aria-label="{{ $table->qr_active ? 'Nonaktifkan QR' : 'Aktifkan QR' }}">
            <i class="fas fa-power-off" aria-hidden="true"></i>
        </a>
    @endcan
    @can('delete order-table/dining-tables')
        <a href="{{ route('order-table/dining-tables/destroy', $table->id) }}" class="ob-icon-btn ob-icon-btn--sm ob-icon-btn--danger delete"
            title="Hapus meja" aria-label="Hapus meja"><i class="fas fa-trash-alt" aria-hidden="true"></i></a>
    @endcan
</div>
