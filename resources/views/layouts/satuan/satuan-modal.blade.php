<x-modal title="{{ $data->id ? 'Edit Satuan' : 'Tambah Satuan Baru' }}" action="{{ $action }}" method="POST">
    @if ($data->id)
        @method('put')
    @endif

    <div class="col-sm-12">
        <div class="form-group">
            <label>Nama Satuan <span class="text-danger">*</span></label>
            <input id="name" name="name" value="{{ $data->name }}" type="text" class="form-control"
                placeholder="Contoh: Pcs, Kilogram, Liter" required>
        </div>
    </div>

    <div class="col-sm-12">
        <div class="form-group">
            <label>Status</label>
            <select name="is_active" class="form-select" required>
                <option value="1" @if ($data->is_active == 1) selected @endif>Aktif</option>
                <option value="0" @if ($data->is_active == 0) selected @endif>Tidak Aktif</option>
            </select>
        </div>
    </div>
</x-modal>
