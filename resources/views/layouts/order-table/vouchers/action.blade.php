<div class="ob-row-actions" role="group" aria-label="Aksi voucher {{ $voucher->code }}">
    @can('update order-table/vouchers')
        <a href="{{ route('order-table/vouchers/edit', $voucher->id) }}" class="ob-icon-btn ob-icon-btn--sm action"
            title="Edit voucher" aria-label="Edit voucher"><i class="fas fa-pen" aria-hidden="true"></i></a>
        <a href="{{ route('order-table/vouchers/toggle', $voucher->id) }}"
            class="ob-icon-btn ob-icon-btn--sm toggle-voucher {{ $voucher->status ? 'ob-icon-btn--danger' : 'ob-icon-btn--success' }}"
            title="{{ $voucher->status ? 'Nonaktifkan voucher' : 'Aktifkan voucher' }}"
            aria-label="{{ $voucher->status ? 'Nonaktifkan voucher' : 'Aktifkan voucher' }}">
            <i class="fas {{ $voucher->status ? 'fa-eye-slash' : 'fa-eye' }}" aria-hidden="true"></i>
        </a>
    @endcan
</div>
