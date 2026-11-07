@if ($session->status === 'open')
    @can('close order-table/sessions')
        <div class="ob-row-actions" role="group" aria-label="Aksi sesi #{{ $session->id }}">
            <a href="{{ route('order-table/sessions/close', $session->id) }}" class="ob-icon-btn ob-icon-btn--sm ob-icon-btn--danger action-close-session"
                title="Tutup sesi" aria-label="Tutup sesi #{{ $session->id }}"><i class="fas fa-door-closed" aria-hidden="true"></i></a>
        </div>
    @endcan
@endif
