<x-modal title="{{ $data->id ? 'Edit Supplier' : 'Tambah Supplier Baru' }}" action="{{ $action }}" method="POST">
    @if ($data->id)
        @method('put')
    @endif

    <div class="col-sm-12 mb-3">
        <div class="form-group">
            <label>Kode Supplier</label>
            <input id="code" name="code" value="{{ old('code', $data->code) }}" type="text" class="form-control"
                placeholder="Contoh: SUP-001">
        </div>
    </div>

    <div class="col-sm-12 mb-3">
        <div class="form-group">
            <label>Nama Supplier <span class="text-danger">*</span></label>
            <input id="name" name="name" value="{{ old('name', $data->name) }}" type="text" class="form-control"
                placeholder="Nama supplier" required>
        </div>
    </div>

    <div class="col-sm-12 mb-3">
        <div class="form-group">
            <label>Mode Procurement</label>
            <select name="procurement_mode" class="form-select" required>
                <option value="online" {{ old('procurement_mode', $data->procurement_mode) === 'online' ? 'selected' : '' }}>Online</option>
                <option value="offline" {{ old('procurement_mode', $data->procurement_mode) === 'offline' ? 'selected' : '' }}>Offline</option>
                <option value="both" {{ old('procurement_mode', $data->procurement_mode) === 'both' ? 'selected' : '' }}>Keduanya</option>
            </select>
        </div>
    </div>

    <div class="col-sm-12 mb-3">
        <div class="form-group">
            <label>Status</label>
            <select name="is_active" class="form-select" required>
                <option value="1" {{ old('is_active', $data->is_active ?? true) == 1 ? 'selected' : '' }}>Aktif</option>
                <option value="0" {{ old('is_active', $data->is_active ?? true) == 0 ? 'selected' : '' }}>Tidak Aktif</option>
            </select>
        </div>
    </div>

    <div class="col-sm-12 mb-3">
        <div class="form-group">
            <label>Catatan</label>
            <textarea name="notes" class="form-control" rows="4" placeholder="Catatan tambahan untuk supplier">{{ old('notes', $data->notes) }}</textarea>
        </div>
    </div>
</x-modal>
