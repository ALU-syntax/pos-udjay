<div class="ob-row-actions" role="group" aria-label="Aksi lokasi {{ $inventory->name }}">
    @can('read warehouse/inventory')
        <a href="{{ route('warehouse/inventory/detail', $inventory->id) }}" class="ob-icon-btn ob-icon-btn--sm"
            title="Detail lokasi" aria-label="Detail lokasi"><i class="fas fa-eye" aria-hidden="true"></i></a>
    @endcan
    @can('update warehouse/inventory')
        <a href="{{ route('warehouse/inventory/edit', $inventory->id) }}" class="ob-icon-btn ob-icon-btn--sm action"
            title="Edit lokasi" aria-label="Edit lokasi"><i class="fas fa-pen" aria-hidden="true"></i></a>
    @endcan
    @can('delete warehouse/inventory')
        <a href="{{ route('warehouse/inventory/destroy', $inventory->id) }}" class="ob-icon-btn ob-icon-btn--sm ob-icon-btn--danger archive-location"
            data-name="{{ $inventory->name }}" title="Arsipkan lokasi" aria-label="Arsipkan lokasi"><i class="fas fa-archive" aria-hidden="true"></i></a>
    @endcan
</div>
