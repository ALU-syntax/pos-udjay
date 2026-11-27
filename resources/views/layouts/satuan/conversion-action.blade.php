<div class="ob-row-actions" role="group" aria-label="Aksi konversi">
    @can('update library/satuan')
        <a href="{{ route('library/satuan/conversions/edit', $conversion->id) }}" class="ob-icon-btn ob-icon-btn--sm action"
            title="Edit konversi" aria-label="Edit konversi"><i class="fas fa-pen" aria-hidden="true"></i></a>
    @endcan
    @can('delete library/satuan')
        <a href="{{ route('library/satuan/conversions/destroy', $conversion->id) }}" class="ob-icon-btn ob-icon-btn--sm ob-icon-btn--danger delete-conversion"
            data-name="{{ $conversion->raw_material_name }}" title="Hapus konversi" aria-label="Hapus konversi"><i class="fas fa-trash" aria-hidden="true"></i></a>
    @endcan
</div>
