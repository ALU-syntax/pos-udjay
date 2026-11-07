<div class="ob-row-actions" role="group" aria-label="Aksi metode {{ $method->label }}">
    @can('update order-table/payment-methods')
        <a href="{{ route('order-table/payment-methods/edit', $method->id) }}" class="ob-icon-btn ob-icon-btn--sm action"
            title="Edit metode" aria-label="Edit metode"><i class="fas fa-pen" aria-hidden="true"></i></a>
        <a href="{{ route('order-table/payment-methods/toggle', $method->id) }}"
            class="ob-icon-btn ob-icon-btn--sm toggle-payment-method {{ $method->enabled ? 'ob-icon-btn--danger' : 'ob-icon-btn--success' }}"
            title="{{ $method->enabled ? 'Nonaktifkan metode' : 'Aktifkan metode' }}"
            aria-label="{{ $method->enabled ? 'Nonaktifkan metode' : 'Aktifkan metode' }}">
            <i class="fas fa-power-off" aria-hidden="true"></i>
        </a>
    @endcan
</div>
