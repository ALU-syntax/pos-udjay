<div class="btn-group" role="group">
    <button type="button" class="btn btn-primary dropdown-toggle btn-sm" data-bs-toggle="dropdown" aria-expanded="false">
        Action
    </button>
    <ul class="dropdown-menu">
        @can('read warehouse/inventory')
            <li>
                <a class="dropdown-item" href="{{ route('warehouse/inventory/detail', $inventoryLocation->id) }}">
                    <i class="fa fa-eye me-2"></i>Detail
                </a>
            </li>
        @endcan
        @can('update warehouse/inventory')
            <li>
                <a class="dropdown-item action" href="{{ route('warehouse/inventory/edit', $inventoryLocation->id) }}">
                    <i class="fa fa-edit me-2"></i>Edit
                </a>
            </li>
        @endcan
        @can('delete warehouse/inventory')
            <li>
                <a class="dropdown-item delete text-danger" href="{{ route('warehouse/inventory/destroy', $inventoryLocation->id) }}">
                    <i class="fa fa-archive me-2"></i>Arsipkan
                </a>
            </li>
        @endcan
    </ul>
</div>
