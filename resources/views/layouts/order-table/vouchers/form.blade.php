@php
    $voucher = $voucher ?? $data;
    $editing = filled($voucher->id);
    $canManageGlobal = $canManageGlobal ?? false;
    $selectedScopeIds = collect(old('scope_ids', $voucher->scope_ids ?? []))->map(fn ($id) => (string) $id)->all();
    $targetOptions = collect($targetOptions ?? []);
@endphp

<x-modal addStyle="modal-xl" title="{{ $editing ? 'Edit Voucher' : 'Tambah Voucher' }}"
    description="Atur nilai, kuota, periode, dan cakupan voucher Order Table."
    update="{{ $editing }}"
    action="{{ $editing ? route('order-table/vouchers/update', $voucher->id) : route('order-table/vouchers/store') }}"
    method="POST">
    @if ($editing)
        @method('put')
    @endif

    <div class="col-12 mb-3 global-impact-warning d-none">
        <div class="alert alert-warning mb-0"><strong>Perhatian:</strong> voucher global berdampak pada seluruh outlet aktif.</div>
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
        <label for="code" class="form-label">Kode <span class="text-danger">*</span></label>
        <input id="code" name="code" type="text" maxlength="50" class="form-control text-uppercase"
            value="{{ old('code', $voucher->code) }}" autocomplete="off" required>
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
    <div class="col-md-3 mb-3">
        <label for="min_spend" class="form-label">Minimum Belanja</label>
        <input id="min_spend" name="min_spend" type="number" min="0" class="form-control" value="{{ old('min_spend', $voucher->min_spend ?? 0) }}">
    </div>
    <div class="col-md-3 mb-3 max-discount-field">
        <label for="max_discount" class="form-label">Maksimum Diskon</label>
        <input id="max_discount" name="max_discount" type="number" min="0" class="form-control" value="{{ old('max_discount', $voucher->max_discount) }}">
    </div>
    <div class="col-md-3 mb-3">
        <label for="quota_total" class="form-label">Kuota Total</label>
        <input id="quota_total" name="quota_total" type="number" min="1" class="form-control" value="{{ old('quota_total', $voucher->quota_total) }}" placeholder="Tanpa batas">
    </div>
    <div class="col-md-3 mb-3">
        <label for="quota_per_device" class="form-label">Kuota / Perangkat</label>
        <input id="quota_per_device" name="quota_per_device" type="number" min="1" max="1" class="form-control" value="{{ old('quota_per_device', $voucher->quota_per_device) }}" placeholder="Tanpa batas">
    </div>
    <div class="col-md-3 mb-3">
        <label for="quota_per_session" class="form-label">Kuota / Sesi</label>
        <input id="quota_per_session" name="quota_per_session" type="number" min="1" max="1" class="form-control" value="{{ old('quota_per_session', $voucher->quota_per_session) }}" placeholder="Tanpa batas">
    </div>
    @if ($editing)
        <div class="col-md-3 mb-3">
            <label class="form-label">Kuota Terpakai</label>
            <input type="text" class="form-control bg-light" value="{{ number_format($voucher->quota_used) }}" readonly>
        </div>
    @endif
    <div class="col-md-4 mb-3">
        <label for="valid_from" class="form-label">Berlaku Mulai</label>
        <input id="valid_from" name="valid_from" type="datetime-local" class="form-control" value="{{ old('valid_from', $voucher->valid_from?->format('Y-m-d\TH:i')) }}">
    </div>
    <div class="col-md-4 mb-3">
        <label for="valid_to" class="form-label">Berlaku Sampai</label>
        <input id="valid_to" name="valid_to" type="datetime-local" class="form-control" value="{{ old('valid_to', $voucher->valid_to?->format('Y-m-d\TH:i')) }}">
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
        <div class="col-md-4 mb-3"><label class="form-label">Status</label><input class="form-control bg-light" value="{{ $voucher->status ? 'Aktif' : 'Nonaktif' }}" readonly></div>
    @endif
    <div class="col-md-4 mb-3">
        <label for="scope" class="form-label">Cakupan <span class="text-danger">*</span></label>
        <select id="scope" name="scope" class="form-select" required>
            <option value="all" @selected(old('scope', $voucher->scope ?? 'all') === 'all')>Semua produk</option>
            <option value="category" @selected(old('scope', $voucher->scope) === 'category')>Kategori tertentu</option>
            <option value="product" @selected(old('scope', $voucher->scope) === 'product')>Produk tertentu</option>
        </select>
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
</x-modal>
