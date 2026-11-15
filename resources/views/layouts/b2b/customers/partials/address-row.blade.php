{{-- Baris alamat. Nama input diberikan B2b.reindex() sesuai urutan baris (addresses[i][field]). --}}
<div class="b2b-repeater-row">
    <button type="button" class="ob-icon-btn ob-icon-btn--sm ob-icon-btn--danger b2b-repeater-row__remove" data-remove-row title="Hapus alamat" aria-label="Hapus alamat">
        <i class="fas fa-trash" aria-hidden="true"></i>
    </button>
    <input type="hidden" data-field="id" value="{{ $address?->id }}">
    <div class="row">
        <div class="col-md-3 mb-3 b2b-field">
            <label class="form-label">Label <span class="text-danger">*</span></label>
            <input data-field="label" class="form-control" value="{{ $address?->label }}" maxlength="100" placeholder="Kantor, Gudang, ...">
        </div>
        <div class="col-md-5 mb-3 b2b-field">
            <label class="form-label">Nama penerima</label>
            <input data-field="recipient_name" class="form-control" value="{{ $address?->recipient_name }}" maxlength="255">
        </div>
        <div class="col-md-4 mb-3 b2b-field">
            <label class="form-label">Telepon penerima</label>
            <input data-field="phone" class="form-control" value="{{ $address?->phone }}" maxlength="30" inputmode="tel">
        </div>
        <div class="col-12 mb-3 b2b-field">
            <label class="form-label">Alamat <span class="text-danger">*</span></label>
            <textarea data-field="address" rows="2" class="form-control" maxlength="1000">{{ $address?->address }}</textarea>
        </div>
        <div class="col-md-4 mb-3 b2b-field">
            <label class="form-label">Kota/Kabupaten</label>
            <input data-field="city" class="form-control" value="{{ $address?->city }}" maxlength="100">
        </div>
        <div class="col-md-4 mb-3 b2b-field">
            <label class="form-label">Provinsi</label>
            <input data-field="province" class="form-control" value="{{ $address?->province }}" maxlength="100">
        </div>
        <div class="col-md-4 mb-3 b2b-field">
            <label class="form-label">Kode pos</label>
            <input data-field="postal_code" class="form-control" value="{{ $address?->postal_code }}" maxlength="10" inputmode="numeric">
        </div>
        <div class="col-12 mb-2">
            <label class="b2b-flag"><input type="checkbox" data-field="is_default_billing" data-single-flag="is_default_billing" value="1" @checked($address?->is_default_billing)> Alamat penagihan (dicetak di invoice)</label>
            <label class="b2b-flag"><input type="checkbox" data-field="is_default_shipping" data-single-flag="is_default_shipping" value="1" @checked($address?->is_default_shipping)> Alamat pengiriman default</label>
        </div>
    </div>
</div>
