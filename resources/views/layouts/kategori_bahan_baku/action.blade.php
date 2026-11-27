<div class="ob-row-actions" role="group" aria-label="Aksi kategori {{ $category->name }}">
    @can('read library/category-bahan-baku')
        <a href="{{ route('library/category-bahan-baku/detail', $category->id) }}" class="ob-icon-btn ob-icon-btn--sm action"
            title="Detail bahan baku" aria-label="Detail bahan baku"><i class="fas fa-eye" aria-hidden="true"></i></a>
    @endcan
    @can('update library/category-bahan-baku')
        <a href="{{ route('library/category-bahan-baku/edit', $category->id) }}" class="ob-icon-btn ob-icon-btn--sm action"
            title="Edit kategori" aria-label="Edit kategori"><i class="fas fa-pen" aria-hidden="true"></i></a>
    @endcan
</div>
