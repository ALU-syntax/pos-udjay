<div class="ob-row-actions" role="group" aria-label="Aksi produk {{ $product->name }}">
    @if ($canUpdate)
        <a href="{{ route('library/product/edit', $product->id) }}" class="ob-icon-btn ob-icon-btn--sm action"
            title="Edit produk" aria-label="Edit produk"><i class="fas fa-pen" aria-hidden="true"></i></a>
    @endif
    @if ($canDelete)
        <a href="{{ route('library/product/destroy', $product->id) }}" class="ob-icon-btn ob-icon-btn--sm ob-icon-btn--danger delete"
            title="Hapus produk" aria-label="Hapus produk"><i class="fas fa-trash-alt" aria-hidden="true"></i></a>
    @endif
</div>
