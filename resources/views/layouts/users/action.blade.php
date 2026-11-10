<div class="ob-row-actions" role="group" aria-label="Aksi user {{ $user->name }}">
    @can('update employee/users')
        <a href="{{ route('employee/user/edit', $user->id) }}" class="ob-icon-btn ob-icon-btn--sm"
            title="Edit user" aria-label="Edit user"><i class="fas fa-pen" aria-hidden="true"></i></a>
    @endcan
    {{-- Akun sendiri tidak bisa dihapus agar admin tidak terkunci dari back office --}}
    @if (auth()->id() !== $user->id)
        @can('delete employee/users')
            <a href="{{ route('employee/user/destroy', $user->id) }}" class="ob-icon-btn ob-icon-btn--sm ob-icon-btn--danger delete-user"
                data-name="{{ $user->name }}" title="Hapus user" aria-label="Hapus user"><i class="fas fa-trash" aria-hidden="true"></i></a>
        @endcan
    @endif
</div>
