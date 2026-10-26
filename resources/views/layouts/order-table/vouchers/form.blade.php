@php
    $voucher = $voucher ?? $data;
    $editing = filled($voucher->id);
    $canManageGlobal = $canManageGlobal ?? false;
    $selectedScopeIds = collect(old('scope_ids', $voucher->scope_ids ?? []))->map(fn ($id) => (string) $id)->all();
    $targetOptions = collect($targetOptions ?? []);
@endphp

{{-- ob-page memberi token & gaya report ke modal (modal dirender di luar .main-content) --}}
<x-modal addStyle="modal-xl ob-page ob-modal" title="{{ $editing ? 'Edit Voucher' : 'Tambah Voucher' }}"
    update="{{ $editing }}"
    action="{{ $editing ? route('order-table/vouchers/update', $voucher->id) : route('order-table/vouchers/store') }}"
    method="POST">
    @if ($editing)
        @method('put')
    @endif

    <div class="col-12">
        <p class="ob-modal__desc">Pelanggan memasukkan kode voucher di halaman checkout Order Table.</p>
    </div>
    <div class="col-12 global-impact-warning d-none">
        <div class="ob-notice" role="note">
            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
            <span>Voucher <strong>global</strong> berlaku di seluruh outlet aktif.</span>
        </div>
    </div>

    {{-- Identitas --}}
    <div class="col-12 ob-form-heading ob-form-heading--first">
        <h3 class="ob-section-title"><i class="fas fa-store" aria-hidden="true"></i> Outlet &amp; Kode</h3>
    </div>
    <div class="col-md-6 mb-3">
        <label for="outlet_id" class="form-label">Outlet <span class="text-danger">*</span></label>
        <select id="outlet_id" name="outlet_id" class="form-select" @required(!$canManageGlobal)>
            @if ($canManageGlobal)
                <option value="" @selected(old('outlet_id', $voucher->outlet_id) === null || old('outlet_id') === '')>Global - semua outlet</option>
            @else
                <option value="">Pilih outlet</option>
            @endif
            @foreach ($outlets as $outlet)
                <option value="{{ $outlet->id }}" @selected((string) old('outlet_id', $voucher->outlet_id) === (string) $outlet->id)>{{ $outlet->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6 mb-3">
        <label for="code" class="form-label">Kode Voucher <span class="text-danger">*</span></label>
        <input id="code" name="code" type="text" maxlength="50" class="form-control text-uppercase"
            value="{{ old('code', $voucher->code) }}" autocomplete="off" placeholder="Contoh: HEMAT10" required>
        <small class="text-muted">Otomatis huruf kapital. Kode ini yang diketik pelanggan.</small>
    </div>

    {{-- Nilai --}}
    <div class="col-12 ob-form-heading">
        <h3 class="ob-section-title"><i class="fas fa-percent" aria-hidden="true"></i> Nilai Diskon</h3>
    </div>
    <div class="col-md-3 mb-3">
        <label for="type" class="form-label">Tipe <span class="text-danger">*</span></label>
        <select id="type" name="type" class="form-select" required>
            <option value="percent" @selected(old('type', $voucher->type ?? 'percent') === 'percent')>Persen</option>
            <option value="fixed" @selected(old('type', $voucher->type) === 'fixed')>Nominal tetap</option>
        </select>
    </div>
    <div class="col-md-3 mb-3">
        <label for="value" class="form-label">Nilai <span class="text-danger">*</span></label>
        <div class="input-group"><span class="input-group-text value-prefix">%</span><input id="value" name="value" type="number" min="1" class="form-control" value="{{ old('value', $voucher->value) }}" required></div>
    </div>
    <div class="col-md-3 mb-3 max-discount-field">
        <label for="max_discount" class="form-label">Maksimum Diskon</label>
        <div class="input-group"><span class="input-group-text">Rp</span><input id="max_discount" name="max_discount" type="number" min="0" class="form-control" value="{{ old('max_discount', $voucher->max_discount) }}" placeholder="Tanpa batas"></div>
    </div>
    <div class="col-md-3 mb-3">
        <label for="min_spend" class="form-label">Minimum Belanja</label>
        <div class="input-group"><span class="input-group-text">Rp</span><input id="min_spend" name="min_spend" type="number" min="0" class="form-control" value="{{ old('min_spend', $voucher->min_spend ?? 0) }}"></div>
    </div>

    {{-- Cakupan --}}
    <div class="col-12 ob-form-heading">
        <h3 class="ob-section-title"><i class="fas fa-tags" aria-hidden="true"></i> Cakupan Produk</h3>
    </div>
    <div class="col-md-4 mb-3">
        <label for="scope" class="form-label">Cakupan <span class="text-danger">*</span></label>
        <select id="scope" name="scope" class="form-select" required>
            <option value="all" @selected(old('scope', $voucher->scope ?? 'all') === 'all')>Semua produk</option>
            <option value="category" @selected(old('scope', $voucher->scope) === 'category')>Kategori tertentu</option>
            <option value="product" @selected(old('scope', $voucher->scope) === 'product')>Produk tertentu</option>
        </select>
        <small class="text-muted">Voucher global hanya bisa untuk semua produk.</small>
    </div>
    <div class="col-md-8 mb-3 scope-target-field">
        <label for="scope_ids" class="form-label">Target Cakupan</label>
        <select id="scope_ids" name="scope_ids[]" class="form-select" multiple>
            @foreach ($targetOptions as $option)
                <option value="{{ data_get($option, 'id') }}" @selected(in_array((string) data_get($option, 'id'), $selectedScopeIds, true))>{{ data_get($option, 'text', data_get($option, 'name')) }}</option>
            @endforeach
        </select>
        <small class="text-muted">Pilihan dimuat berdasarkan outlet dan jenis cakupan.</small>
    </div>

    {{-- Kuota --}}
    <div class="col-12 ob-form-heading">
        <h3 class="ob-section-title"><i class="fas fa-layer-group" aria-hidden="true"></i> Kuota Pemakaian</h3>
    </div>
    <div class="col-md-4 mb-3">
        <label for="quota_total" class="form-label">Kuota Total</label>
        <input id="quota_total" name="quota_total" type="number" min="1" class="form-control" value="{{ old('quota_total', $voucher->quota_total) }}" placeholder="Tanpa batas">
        <small class="text-muted">Jumlah pemakaian dari semua pelanggan. Kosongkan untuk tanpa batas.</small>
    </div>
    {{-- Batas per perangkat dijaga UNIQUE(voucher_id, device_id), jadi selalu 1x dan tidak bisa diatur --}}
    <div class="col-md-4 mb-3">
        <label for="quota_per_device_info" class="form-label">Kuota / Perangkat</label>
        <input id="quota_per_device_info" type="text" class="form-control" value="1× per perangkat" readonly>
        <small class="text-muted">Ditetapkan sistem agar voucher terbagi ke lebih banyak pelanggan.</small>
    </div>
    @if ($editing)
        <div class="col-md-4 mb-3">
            <label for="quota_used_readonly" class="form-label">Kuota Terpakai</label>
            <input id="quota_used_readonly" type="text" class="form-control" value="{{ number_format($voucher->quota_used, 0, ',', '.') }}" readonly>
        </div>
    @endif

    {{-- Periode & status --}}
    <div class="col-12 ob-form-heading">
        <h3 class="ob-section-title"><i class="far fa-calendar-alt" aria-hidden="true"></i> Periode &amp; Status</h3>
    </div>
    <div class="col-md-4 mb-3">
        <label for="valid_from" class="form-label">Berlaku Mulai</label>
        <input id="valid_from" name="valid_from" type="datetime-local" class="form-control" value="{{ old('valid_from', $voucher->valid_from?->format('Y-m-d\TH:i')) }}">
    </div>
    <div class="col-md-4 mb-3">
        <label for="valid_to" class="form-label">Berlaku Sampai</label>
        <input id="valid_to" name="valid_to" type="datetime-local" class="form-control" value="{{ old('valid_to', $voucher->valid_to?->format('Y-m-d\TH:i')) }}">
        <small class="text-muted">Kosongkan keduanya untuk berlaku tanpa batas.</small>
    </div>
    @if (!$editing)
        <div class="col-md-4 mb-3">
            <label for="status" class="form-label">Status</label>
            <select id="status" name="status" class="form-select">
                <option value="1" @selected(old('status', true) == 1)>Aktif</option>
                <option value="0" @selected(old('status', true) == 0)>Nonaktif</option>
            </select>
        </div>
    @else
        <div class="col-md-4 mb-3">
            <label for="status_readonly" class="form-label">Status</label>
            <input id="status_readonly" class="form-control" value="{{ $voucher->status ? 'Aktif' : 'Nonaktif' }}" readonly>
            <small class="text-muted">Ubah lewat tombol aktif/nonaktif di daftar voucher.</small>
        </div>
    @endif
</x-modal>
