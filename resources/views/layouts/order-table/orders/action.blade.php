@can('read order-table/orders')
    <a href="{{ route('order-table/orders/show', $order->id) }}" class="btn btn-sm btn-outline-primary" title="Detail order" aria-label="Detail order">
        <i class="fa fa-eye"></i>
    </a>
@endcan
