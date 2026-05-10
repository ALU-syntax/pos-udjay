<x-modal title="{{ $data->id ? 'Edit Kategori Bahan Baku' : 'Tambah Kategori Bahan Baku' }}" action="{{ $action }}" method="POST" update="{{ $data->id ? true : false }}">
    @if ($data->id)
        @method('put')
    @endif

    <div class="col-sm-12 mb-3">
        <div class="form-group">
            <label>Nama Kategori <span class="text-danger">*</span></label>
            <input id="name" name="name" value="{{ old('name', $data->name) }}" type="text" class="form-control"
                placeholder="Contoh: Meat, Dairy, Dry Goods, Packaging" maxlength="255" required>
            <small class="text-muted">Gunakan nama yang mudah dikenali oleh tim gudang dan pembelian.</small>
        </div>
    </div>

    <div class="col-sm-12 mb-3">
        <div class="form-group">
            <label>Status <span class="text-danger">*</span></label>
            @php
                $selectedStatus = old('is_active', $data->exists ? $data->is_active : 1);
            @endphp
            <select name="is_active" class="form-select" required>
                <option value="1" @if ((string) $selectedStatus === '1') selected @endif>Aktif</option>
                <option value="0" @if ((string) $selectedStatus === '0') selected @endif>Tidak Aktif</option>
            </select>
        </div>
    </div>

    <div class="col-sm-12">
        <div class="form-group">
            <label>Catatan</label>
            <textarea name="notes" class="form-control" rows="4" maxlength="1000"
                placeholder="Contoh: kategori untuk bahan yang wajib masuk cold storage">{{ old('notes', $data->notes) }}</textarea>
        </div>
    </div>
</x-modal>
