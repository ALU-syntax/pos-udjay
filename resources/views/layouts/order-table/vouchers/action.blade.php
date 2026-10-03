<div class="order-table-actions" role="group" aria-label="Aksi voucher {{ $voucher->code }}">
    @can('update order-table/vouchers')
        <a href="{{ route('order-table/vouchers/edit', $voucher->id) }}" class="btn btn-sm btn-outline-primary action"
            title="Edit voucher" aria-label="Edit voucher"><i class="fa fa-pen"></i></a>
        <a href="{{ route('order-table/vouchers/toggle', $voucher->id) }}"
            class="btn btn-sm {{ $voucher->status ? 'btn-outline-danger' : 'btn-outline-success' }} toggle-voucher"
            title="{{ $voucher->status ? 'Nonaktifkan voucher' : 'Aktifkan voucher' }}"
            aria-label="{{ $voucher->status ? 'Nonaktifkan voucher' : 'Aktifkan voucher' }}">
            <i class="fa {{ $voucher->status ? 'fa-toggle-off' : 'fa-toggle-on' }}"></i>
        </a>
    @endcan
</div>
