<x-modal addStyle="modal-lg" title="{{ isset($update) ? 'Edit Bahan Baku Supplier' : 'Tambah Bahan Baku Supplier' }}" action="{{ $action }}" method="POST">
    @if (isset($update) && $update)
        @method('put')
    @endif

    <div class="col-sm-12 mb-3">
        <div class="form-group">
            <label>Bahan Baku Master <span class="text-danger">*</span></label>
            <select name="raw_material_id" class="form-select" required>
                <option value="">Pilih bahan baku</option>
                @foreach ($rawMaterials as $material)
                    <option value="{{ $material->id }}"
                        {{ old('raw_material_id', $item->raw_material_id) == $material->id ? 'selected' : '' }}>
                        {{ $material->name }} @if ($material->baseUnit) ({{ $material->baseUnit->name }}) @endif
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="col-sm-12 mb-3">
        <div class="form-group">
            <label>Nama Bahan Supplier</label>
            <input name="supplier_material_name" value="{{ old('supplier_material_name', $item->supplier_material_name) }}" type="text"
                class="form-control" placeholder="Nama bahan sesuai supplier">
        </div>
    </div>

    <div class="col-sm-12 mb-3 row g-2">
        <div class="col-md-6">
            <div class="form-group">
                <label>SKU Supplier</label>
                <input name="supplier_sku" value="{{ old('supplier_sku', $item->supplier_sku) }}" type="text"
                    class="form-control" placeholder="Kode SKU supplier">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Unit Pembelian <span class="text-danger">*</span></label>
                <select name="purchase_unit_id" class="form-select" required>
                    <option value="">Pilih satuan</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}"
                            {{ old('purchase_unit_id', $item->purchase_unit_id) == $unit->id ? 'selected' : '' }}>
                            {{ $unit->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="col-sm-12 mb-3 row g-2">
        <div class="col-md-4">
            <div class="form-group">
                <label>MOQ</label>
                <input name="minimum_order_qty" type="number" step="0.01" min="0" class="form-control"
                    value="{{ old('minimum_order_qty', $item->minimum_order_qty ?? 0) }}" placeholder="0">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>Lead Time (hari)</label>
                <input name="lead_time_days" type="number" step="1" min="0" class="form-control"
                    value="{{ old('lead_time_days', $item->lead_time_days ?? 0) }}" placeholder="0">
            </div>
        </div>
        <div class="col-md-4">
            <div class="form-group">
                <label>Harga Saat Ini</label>
                <input name="current_price" type="number" step="0.01" min="0" class="form-control"
                    value="{{ old('current_price', $item->current_price) }}" placeholder="0.00">
            </div>
        </div>
    </div>

    <div class="col-sm-12 mb-3 row g-2">
        <div class="col-md-6">
            <div class="form-group">
                <label>Prioritas Supplier</label>
                <select name="is_preferred" class="form-select" required>
                    <option value="1" {{ old('is_preferred', $item->is_preferred ?? 0) == 1 ? 'selected' : '' }}>Ya</option>
                    <option value="0" {{ old('is_preferred', $item->is_preferred ?? 0) == 0 ? 'selected' : '' }}>Tidak</option>
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Status</label>
                <select name="is_active" class="form-select" required>
                    <option value="1" {{ old('is_active', $item->is_active ?? 1) == 1 ? 'selected' : '' }}>Aktif</option>
                    <option value="0" {{ old('is_active', $item->is_active ?? 1) == 0 ? 'selected' : '' }}>Tidak Aktif</option>
                </select>
            </div>
        </div>
    </div>

    <div class="col-sm-12 mb-3">
        <div class="form-group">
            <label>Catatan</label>
            <textarea name="notes" class="form-control" rows="3" placeholder="Catatan tambahan">{{ old('notes', $item->notes) }}</textarea>
        </div>
    </div>
</x-modal>
