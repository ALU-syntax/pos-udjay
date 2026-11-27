<div class="ob-row-actions" role="group" aria-label="Aksi satuan {{ $satuan->name }}">
    @can('update library/satuan')
        <a href="{{ route('library/satuan/edit', $satuan->id) }}" class="ob-icon-btn ob-icon-btn--sm action"
            title="Edit satuan" aria-label="Edit satuan"><i class="fas fa-pen" aria-hidden="true"></i></a>
    @endcan
</div>
