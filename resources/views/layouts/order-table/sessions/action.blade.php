@if ($session->status === 'open')
    @can('close order-table/sessions')
        <a href="{{ route('order-table/sessions/close', $session->id) }}" class="btn btn-sm btn-outline-danger action-close-session" title="Tutup sesi" aria-label="Tutup sesi">
            <i class="fa fa-door-closed"></i>
        </a>
    @endcan
@else
    <span class="text-muted small">-</span>
@endif
