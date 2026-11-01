<div class="ob-row-actions" role="group" aria-label="Aksi kategori {{ $category->name }}">
    @if ($canUpdate)
        <a href="{{ route('library/category/edit', $category->id) }}" class="ob-icon-btn ob-icon-btn--sm action"
            title="Edit kategori" aria-label="Edit kategori"><i class="fas fa-pen" aria-hidden="true"></i></a>
    @endif
</div>
