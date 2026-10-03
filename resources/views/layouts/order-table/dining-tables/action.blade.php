<div class="order-table-actions" role="group" aria-label="Aksi meja {{ $table->code }}">
    @can('read order-table/dining-tables')
        <a href="{{ route('order-table/dining-tables/qr', $table->id) }}" class="btn btn-sm btn-outline-primary qr-preview"
            title="Preview QR" aria-label="Preview QR"><i class="fa fa-eye"></i></a>
    @endcan
    @can('download order-table/dining-tables')
        <a href="{{ route('order-table/dining-tables/qr/download', $table->id) }}" class="btn btn-sm btn-outline-secondary"
            title="Download QR" aria-label="Download QR"><i class="fa fa-download"></i></a>
    @endcan
    @can('print order-table/dining-tables')
        <a href="{{ route('order-table/dining-tables/qr/print', $table->id) }}" target="_blank" rel="noopener"
            class="btn btn-sm btn-outline-secondary" title="Cetak QR" aria-label="Cetak QR"><i class="fa fa-print"></i></a>
    @endcan
    @can('rotate order-table/dining-tables')
        <a href="{{ route('order-table/dining-tables/rotate-token', $table->id) }}"
            class="btn btn-sm btn-outline-warning sensitive-action" data-action="rotate"
            title="Rotasi token QR" aria-label="Rotasi token QR"><i class="fa fa-sync"></i></a>
    @endcan
    @can('update order-table/dining-tables')
        <a href="{{ route('order-table/dining-tables/toggle-qr', $table->id) }}"
            class="btn btn-sm {{ $table->qr_active ? 'btn-outline-danger' : 'btn-outline-success' }} sensitive-action" data-action="toggle"
            title="{{ $table->qr_active ? 'Nonaktifkan QR' : 'Aktifkan QR' }}"
            aria-label="{{ $table->qr_active ? 'Nonaktifkan QR' : 'Aktifkan QR' }}">
            <i class="fa {{ $table->qr_active ? 'fa-ban' : 'fa-check' }}"></i>
        </a>
        <a href="{{ route('order-table/dining-tables/edit', $table->id) }}" class="btn btn-sm btn-outline-primary action"
            title="Edit meja" aria-label="Edit meja"><i class="fa fa-pen"></i></a>
    @endcan
    @can('delete order-table/dining-tables')
        <a href="{{ route('order-table/dining-tables/destroy', $table->id) }}" class="btn btn-sm btn-outline-danger delete"
            title="Hapus meja" aria-label="Hapus meja"><i class="fa fa-trash"></i></a>
    @endcan
</div>
