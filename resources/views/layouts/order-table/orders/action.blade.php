@can('read order-table/orders')
    <div class="ob-row-actions" role="group" aria-label="Aksi order {{ $order->order_no }}">
        <a href="{{ route('order-table/orders/show', $order->id) }}" class="ob-icon-btn ob-icon-btn--sm"
            title="Detail order" aria-label="Detail order {{ $order->order_no }}"><i class="fas fa-eye" aria-hidden="true"></i></a>
    </div>
@endcan
