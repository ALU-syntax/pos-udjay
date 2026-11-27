<div class="ob-row-actions" role="group" aria-label="Aksi bahan baku {{ $rawMaterial->name }}">
    @can('read library/bahan-baku')
        <a href="{{ route('library/bahan-baku/detail', $rawMaterial->id) }}" class="ob-icon-btn ob-icon-btn--sm action"
            title="Detail bahan baku" aria-label="Detail bahan baku"><i class="fas fa-eye" aria-hidden="true"></i></a>
    @endcan
    @can('update library/bahan-baku')
        <a href="{{ route('library/bahan-baku/edit', $rawMaterial->id) }}" class="ob-icon-btn ob-icon-btn--sm action"
            title="Edit bahan baku" aria-label="Edit bahan baku"><i class="fas fa-pen" aria-hidden="true"></i></a>
    @endcan
    @can('delete library/bahan-baku')
        <a href="{{ route('library/bahan-baku/destroy', $rawMaterial->id) }}" class="ob-icon-btn ob-icon-btn--sm ob-icon-btn--danger archive-material"
            data-name="{{ $rawMaterial->name }}" title="Arsipkan bahan baku" aria-label="Arsipkan bahan baku"><i class="fas fa-archive" aria-hidden="true"></i></a>
    @endcan
</div>
