<div class="supplier-action-group" role="group" aria-label="Aksi Supplier">
    <a class="supplier-action-btn"
        href="{{ route('warehouse/supplier/show', $supplier->id) }}"
        data-bs-toggle="tooltip"
        data-bs-placement="top"
        title="Detail supplier">
        <i class="fa fa-eye"></i>
    </a>
    <a class="supplier-action-btn action"
        href="{{ route('warehouse/supplier/edit', $supplier->id) }}"
        data-bs-toggle="tooltip"
        data-bs-placement="top"
        title="Edit supplier">
        <i class="fa fa-pen"></i>
    </a>
    <a class="supplier-action-btn danger delete"
        href="{{ route('warehouse/supplier/destroy', $supplier->id) }}"
        data-bs-toggle="tooltip"
        data-bs-placement="top"
        title="Hapus supplier">
        <i class="fa fa-trash"></i>
    </a>
</div>
