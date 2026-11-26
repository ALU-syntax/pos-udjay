<div class="ob-row-actions" role="group" aria-label="Aksi supplier {{ $supplier->name }}">
    @can('read warehouse/supplier')
        <a href="{{ route('warehouse/supplier/show', $supplier->id) }}" class="ob-icon-btn ob-icon-btn--sm"
            title="Detail supplier" aria-label="Detail supplier"><i class="fas fa-eye" aria-hidden="true"></i></a>
    @endcan
    @can('update warehouse/supplier')
        <a href="{{ route('warehouse/supplier/edit', $supplier->id) }}" class="ob-icon-btn ob-icon-btn--sm action"
            title="Edit supplier" aria-label="Edit supplier"><i class="fas fa-pen" aria-hidden="true"></i></a>
    @endcan
    @can('delete warehouse/supplier')
        <a href="{{ route('warehouse/supplier/destroy', $supplier->id) }}" class="ob-icon-btn ob-icon-btn--sm ob-icon-btn--danger delete-supplier"
            data-name="{{ $supplier->name }}" title="Hapus supplier" aria-label="Hapus supplier"><i class="fas fa-trash" aria-hidden="true"></i></a>
    @endcan
</div>
