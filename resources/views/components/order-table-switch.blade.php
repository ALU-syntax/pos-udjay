{{--
    Switch "Order Table" di tabel produk / modifier (halaman ob-page).
    $checked: bool, $url: route PATCH toggle, $canUpdate: bool, $note: teks peringatan opsional.
    JS penanganannya di components/order-table-switch-script (kelas .order-table-switch).
--}}
@if ($canUpdate)
    <div class="form-check form-switch d-inline-flex mb-0 ps-0" title="Tampil di Order Table">
        <input class="form-check-input ms-0 order-table-switch" type="checkbox" role="switch"
            data-url="{{ $url }}" @checked($checked) aria-label="Tampil di Order Table">
    </div>
@else
    <span class="badge {{ $checked ? 'badge-success' : 'badge-muted' }}">{{ $checked ? 'Tampil' : 'Disembunyikan' }}</span>
@endif
@if (!empty($note))
    <span class="ob-switch-note"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i> {{ $note }}</span>
@endif
