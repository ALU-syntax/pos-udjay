@can('retry order-table/bridge')
    @if (config('order-table.node.enabled'))
        <a href="{{ route('order-table/bridge/rebridge', $order->id) }}"
            class="btn btn-sm btn-outline-warning action-rebridge" title="Re-bridge" aria-label="Re-bridge">
            <i class="fa fa-rotate"></i>
        </a>
    @else
        <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Integrasi Node belum aktif" aria-label="Re-bridge dinonaktifkan">
            <i class="fa fa-rotate"></i>
        </button>
    @endif
@endcan
