<div class="btn-group" role="group">
    <button type="button" class="btn btn-primary dropdown-toggle btn-sm" data-bs-toggle="dropdown" aria-expanded="false">
        Action
    </button>
    <ul class="dropdown-menu">
        @can('read library/bahan-baku')
            <li>
                <a class="dropdown-item action" href="{{ route('library/bahan-baku/detail', $rawMaterial->id) }}">
                    <i class="fa fa-eye me-2"></i>Detail
                </a>
            </li>
        @endcan
        @can('update library/bahan-baku')
            <li>
                <a class="dropdown-item action" href="{{ route('library/bahan-baku/edit', $rawMaterial->id) }}">
                    <i class="fa fa-edit me-2"></i>Edit
                </a>
            </li>
        @endcan
        @can('delete library/bahan-baku')
            <li>
                <a class="dropdown-item delete text-danger" href="{{ route('library/bahan-baku/destroy', $rawMaterial->id) }}">
                    <i class="fa fa-archive me-2"></i>Arsipkan
                </a>
            </li>
        @endcan
    </ul>
</div>
