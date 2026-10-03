<div class="order-table-actions" role="group" aria-label="Aksi metode {{ $method->label }}">
    @can('update order-table/payment-methods')
        <a href="{{ route('order-table/payment-methods/edit', $method->id) }}" class="btn btn-sm btn-outline-primary action"
            title="Edit metode" aria-label="Edit metode"><i class="fa fa-pen"></i></a>
        <a href="{{ route('order-table/payment-methods/toggle', $method->id) }}"
            class="btn btn-sm {{ $method->enabled ? 'btn-outline-danger' : 'btn-outline-success' }} toggle-payment-method"
            title="{{ $method->enabled ? 'Nonaktifkan metode' : 'Aktifkan metode' }}"
            aria-label="{{ $method->enabled ? 'Nonaktifkan metode' : 'Aktifkan metode' }}">
            <i class="fa {{ $method->enabled ? 'fa-toggle-off' : 'fa-toggle-on' }}"></i>
        </a>
    @endcan
</div>
