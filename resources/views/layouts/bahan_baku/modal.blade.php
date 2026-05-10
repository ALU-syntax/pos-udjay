<x-modal addStyle="modal-lg" title="{{ $data->id ? 'Edit Bahan Baku' : 'Tambah Bahan Baku' }}" action="{{ $action }}" method="POST" update="{{ $data->id ? true : false }}">
    @if ($data->id)
        @method('put')
    @endif

    <div class="col-md-6 mb-3">
        <div class="form-group">
            <label>Kode Bahan</label>
            <input id="code" name="code" value="{{ old('code', $data->code) }}" type="text" class="form-control"
                placeholder="Kosongkan untuk auto-generate" maxlength="50">
        </div>
    </div>

    <div class="col-md-6 mb-3">
        <div class="form-group">
            <label>Nama Bahan <span class="text-danger">*</span></label>
            <input id="name" name="name" value="{{ old('name', $data->name) }}" type="text" class="form-control"
                placeholder="Contoh: Tepung Terigu, Gula Pasir" maxlength="255" required>
        </div>
    </div>

    <div class="col-md-6 mb-3">
        <div class="form-group">
            <label>Kategori</label>
            <select name="raw_material_category_id" class="form-select select2InsideModal">
                <option value="">Tanpa kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @if (old('raw_material_category_id', $data->raw_material_category_id) == $category->id) selected @endif>
                        {{ $category->name }}{{ $category->is_active ? '' : ' (Nonaktif)' }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="col-md-6 mb-3">
        <div class="form-group">
            <label>Satuan Dasar <span class="text-danger">*</span></label>
            <select name="base_unit_id" class="form-select select2InsideModal" required>
                <option value="">Pilih satuan dasar</option>
                @foreach ($units as $unit)
                    <option value="{{ $unit->id }}" @if (old('base_unit_id', $data->base_unit_id) == $unit->id) selected @endif>
                        {{ $unit->name }}{{ $unit->symbol ? ' (' . $unit->symbol . ')' : '' }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="col-md-6 mb-3">
        <div class="form-group">
            <label>Tipe Penyimpanan <span class="text-danger">*</span></label>
            <select name="storage_type_id" class="form-select select2InsideModal" required>
                <option value="">Pilih tipe penyimpanan</option>
                @foreach ($storageTypes as $storageType)
                    <option value="{{ $storageType->id }}" @if (old('storage_type_id', $data->storage_type_id) == $storageType->id) selected @endif>
                        {{ $storageType->name }}
                    </option>
                @endforeach
            </select>
            @if ($storageTypes->isEmpty())
                <small class="text-danger">Tipe penyimpanan belum tersedia. Jalankan seeder tipe penyimpanan terlebih dahulu.</small>
            @endif
        </div>
    </div>

    <div class="col-md-6 mb-3">
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
                placeholder="Contoh: simpan di tempat kering, cek expired saat penerimaan">{{ old('notes', $data->notes) }}</textarea>
        </div>
    </div>

    <script>
        $('.select2InsideModal').select2({
            dropdownParent: $("#modal_action"),
            width: '100%'
        });
    </script>
</x-modal>
