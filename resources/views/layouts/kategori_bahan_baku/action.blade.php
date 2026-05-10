<div class="btn-group" role="group">
    <button type="button" class="btn btn-primary dropdown-toggle btn-sm" data-bs-toggle="dropdown" aria-expanded="false">
        Action
    </button>
    <ul class="dropdown-menu">
        @can('read library/category-bahan-baku')
            <li>
                <a class="dropdown-item action" href="{{ route('library/category-bahan-baku/detail', $category->id) }}">
                    <i class="fa fa-eye me-2"></i>Detail Bahan
                </a>
            </li>
        @endcan
        @can('update library/category-bahan-baku')
            <li>
                <a class="dropdown-item action" href="{{ route('library/category-bahan-baku/edit', $category->id) }}">
                    <i class="fa fa-edit me-2"></i>Edit
                </a>
            </li>
        @endcan
    </ul>
</div>
