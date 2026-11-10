<div class="ob-row-actions" role="group" aria-label="Aksi role {{ $role->name }}">
    @can('update employee/hak-akses')
        @unless (strtolower($role->name) === 'super admin')
            <a href="{{ route('employee/hak-akses/role/edit', $role->id) }}" class="ob-icon-btn ob-icon-btn--sm"
                title="Atur hak akses" aria-label="Atur hak akses"><i class="fas fa-key" aria-hidden="true"></i></a>
        @endunless
    @endcan
    {{-- Role sistem dipakai langsung oleh kode, jadi nama dan keberadaannya dikunci --}}
    @unless ($role->isSystemRole())
        @can('update employee/roles')
            <a href="{{ route('employee/roles/edit', $role->id) }}" class="ob-icon-btn ob-icon-btn--sm"
                title="Edit role" aria-label="Edit role"><i class="fas fa-pen" aria-hidden="true"></i></a>
        @endcan
        @can('delete employee/roles')
            @if ($role->users_count > 0)
                <span class="ob-icon-btn ob-icon-btn--sm ob-icon-btn--disabled" role="img"
                    title="Masih dipakai {{ $role->users_count }} user" aria-label="Tidak bisa dihapus, masih dipakai {{ $role->users_count }} user">
                    <i class="fas fa-trash" aria-hidden="true"></i>
                </span>
            @else
                <a href="{{ route('employee/roles/destroy', $role->id) }}" class="ob-icon-btn ob-icon-btn--sm ob-icon-btn--danger delete-role"
                    data-name="{{ $role->name }}" title="Hapus role" aria-label="Hapus role"><i class="fas fa-trash" aria-hidden="true"></i></a>
            @endif
        @endcan
    @endunless
</div>
