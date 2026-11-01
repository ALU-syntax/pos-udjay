<div class="ob-row-actions" role="group" aria-label="Aksi modifier {{ $group->name }}">
    @if ($canUpdate)
        <a href="{{ route('library/modifiers/edit', $group->id) }}" class="ob-icon-btn ob-icon-btn--sm action"
            title="Edit modifier" aria-label="Edit modifier"><i class="fas fa-pen" aria-hidden="true"></i></a>
        <a href="{{ route('library/modifiers/getProduct', $group->id) }}" class="ob-icon-btn ob-icon-btn--sm action"
            title="Atur produk" aria-label="Atur produk"><i class="fas fa-link" aria-hidden="true"></i></a>
    @endif
    @if ($canDelete)
        <a href="{{ route('library/modifiers/destroy', $group->id) }}" class="ob-icon-btn ob-icon-btn--sm ob-icon-btn--danger delete"
            title="Hapus modifier" aria-label="Hapus modifier"><i class="fas fa-trash-alt" aria-hidden="true"></i></a>
    @endif
</div>
