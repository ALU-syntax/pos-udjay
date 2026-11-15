{{-- Baris kontak. Nama input diberikan B2b.reindex() sesuai urutan baris (contacts[i][field]). --}}
<div class="b2b-repeater-row">
    <button type="button" class="ob-icon-btn ob-icon-btn--sm ob-icon-btn--danger b2b-repeater-row__remove" data-remove-row title="Hapus kontak" aria-label="Hapus kontak">
        <i class="fas fa-trash" aria-hidden="true"></i>
    </button>
    <input type="hidden" data-field="id" value="{{ $contact?->id }}">
    <div class="row">
        <div class="col-md-4 mb-3 b2b-field">
            <label class="form-label">Nama <span class="text-danger">*</span></label>
            <input data-field="name" class="form-control" value="{{ $contact?->name }}" maxlength="255">
        </div>
        <div class="col-md-3 mb-3 b2b-field">
            <label class="form-label">Jabatan</label>
            <input data-field="position" class="form-control" value="{{ $contact?->position }}" maxlength="100" placeholder="Contoh: Purchasing">
        </div>
        <div class="col-md-2 mb-3 b2b-field">
            <label class="form-label">Telepon/WA</label>
            <input data-field="phone" class="form-control" value="{{ $contact?->phone }}" maxlength="30" inputmode="tel">
        </div>
        <div class="col-md-3 mb-3 b2b-field">
            <label class="form-label">Email</label>
            <input data-field="email" type="email" class="form-control" value="{{ $contact?->email }}" maxlength="255">
        </div>
        <div class="col-12 mb-2">
            <label class="b2b-flag"><input type="checkbox" data-field="is_primary" data-single-flag="is_primary" value="1" @checked($contact?->is_primary)> Kontak utama</label>
            <label class="b2b-flag"><input type="checkbox" data-field="receive_reminder" value="1" @checked($contact?->receive_reminder)> Terima reminder</label>
        </div>
    </div>
</div>
