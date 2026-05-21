<x-modal addStyle="modal-lg" title="{{ $data->id ? 'Edit Lokasi Inventory' : 'Tambah Lokasi Inventory' }}" action="{{ $action }}" method="POST" update="{{ $data->id ? true : false }}">
    @if ($data->id)
        @method('put')
    @endif

    <div class="col-md-6 mb-3">
        <div class="form-group">
            <label>Kode Lokasi</label>
            <input id="code" name="code" value="{{ old('code', $data->code) }}" type="text" class="form-control"
                maxlength="50" placeholder="Contoh: WH-CENTRAL, KITCH-01">
        </div>
    </div>

    <div class="col-md-6 mb-3">
        <div class="form-group">
            <label>Nama Lokasi <span class="text-danger">*</span></label>
            <input id="name" name="name" value="{{ old('name', $data->name) }}" type="text" class="form-control"
                maxlength="255" required placeholder="Contoh: Gudang Pusat, Kitchen Outlet A">
        </div>
    </div>

    <div class="col-md-6 mb-3">
        <div class="form-group">
            <label>Tipe Lokasi <span class="text-danger">*</span></label>
            <select name="inventory_type_id" class="form-select select2InsideModal" required>
                <option value="">Pilih tipe lokasi</option>
                @foreach ($types as $type)
                    <option value="{{ $type->id }}" @if (old('inventory_type_id', $data->inventory_type_id) == $type->id) selected @endif>
                        {{ $type->name }}{{ $type->is_active ? '' : ' (Nonaktif)' }}
                    </option>
                @endforeach
            </select>
            @if ($types->isEmpty())
                <small class="text-danger">Belum ada tipe lokasi inventory. Jalankan migration/seed tipe terlebih dahulu.</small>
            @endif
        </div>
    </div>

    <div class="col-md-6 mb-3">
        <div class="form-group">
            <label>Parent Lokasi</label>
            <select name="parent_id" class="form-select select2InsideModal">
                <option value="">Tanpa parent</option>
                @foreach ($parents as $parent)
                    <option value="{{ $parent->id }}" @if (old('parent_id', $data->parent_id) == $parent->id) selected @endif>
                        {{ $parent->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="col-md-6 mb-3">
        <div class="form-group">
            <label>Outlet Terkait</label>
            <select name="outlet_id" class="form-select select2InsideModal">
                <option value="">Semua outlet</option>
                @foreach ($outlets as $outlet)
                    <option value="{{ $outlet->id }}" @if (old('outlet_id', $data->outlet_id) == $outlet->id) selected @endif>
                        {{ $outlet->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="col-md-6 mb-3">
        <div class="form-group">
            <label>Brand Terkait</label>
            <select name="brand_id" class="form-select select2InsideModal">
                <option value="">Semua brand</option>
                @foreach ($brands as $brand)
                    <option value="{{ $brand->id }}" @if (old('brand_id', $data->brand_id) == $brand->id) selected @endif>
                        {{ $brand->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="col-sm-12">
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

    <script>
        $('.select2InsideModal').select2({
            dropdownParent: $("#modal_action"),
            width: '100%'
        });
    </script>
</x-modal>
