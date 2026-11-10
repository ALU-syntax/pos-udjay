<div class="ob-row-actions" role="group" aria-label="Aksi hak akses {{ $user->name }}">
    @can('update employee/hak-akses')
        <a href="{{ route('employee/hak-akses/user/edit', $user->id) }}" class="ob-icon-btn ob-icon-btn--sm"
            title="Atur akses tambahan" aria-label="Atur akses tambahan"><i class="fas fa-key" aria-hidden="true"></i></a>
    @endcan
</div>
