{{-- Re-bridge hanya untuk bridge yang gagal (roadmap Node: proses ulang status failed, idempoten via reference_id) --}}
@if ($order->pos_bridge_status === 'failed')
    @can('retry order-table/bridge')
        <div class="ob-row-actions" role="group" aria-label="Aksi bridge {{ $order->order_no }}">
            @if ($nodeEnabled)
                <a href="{{ route('order-table/bridge/rebridge', $order->id) }}" class="ob-icon-btn ob-icon-btn--sm action-rebridge"
                    title="Re-bridge" aria-label="Re-bridge order {{ $order->order_no }}"><i class="fas fa-redo" aria-hidden="true"></i></a>
            @else
                <button type="button" class="ob-icon-btn ob-icon-btn--sm" disabled
                    title="Integrasi Node belum aktif" aria-label="Re-bridge dinonaktifkan"><i class="fas fa-redo" aria-hidden="true"></i></button>
            @endif
        </div>
    @endcan
@endif
