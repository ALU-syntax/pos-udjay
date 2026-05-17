<x-modal addStyle="modal-lg" title="{{ isset($update) ? 'Edit Stok Bahan Baku' : 'Tambah Bahan Baku ke Inventory' }}" action="{{ $action }}" method="POST">
    @if (isset($update) && $update)
        @method('put')
    @endif

    <div class="col-sm-12 mb-3">
        <div class="form-group">
            <label>Bahan Baku <span class="text-danger">*</span></label>
            <select name="raw_material_id" class="form-select select2InsideModal" required>
                <option value="">Pilih bahan baku</option>
                @foreach ($rawMaterials as $material)
                    <option value="{{ $material->id }}"
                        {{ old('raw_material_id', $item->raw_material_id) == $material->id ? 'selected' : '' }}>
                        {{ $material->name }}
                        @if ($material->baseUnit)
                            ({{ $material->baseUnit->symbol ?: $material->baseUnit->name }})
                        @endif
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="col-sm-12 mb-3 row g-2">
        <div class="col-md-6">
            <div class="form-group">
                <label>Qty Available <span class="text-danger">*</span></label>
                <input name="qty_available" type="number" step="0.00001" min="0" class="form-control"
                    value="{{ old('qty_available', $item->qty_available ?? 0) }}" required>
                <small class="text-muted">Stok fisik tersedia di lokasi ini.</small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Qty Reserved <span class="text-danger">*</span></label>
                <input name="qty_reserved" type="number" step="0.00001" min="0" class="form-control"
                    value="{{ old('qty_reserved', $item->qty_reserved ?? 0) }}" required>
                <small class="text-muted">Tidak boleh lebih besar dari qty available.</small>
            </div>
        </div>
    </div>

    <script>
        $('.select2InsideModal').select2({
            dropdownParent: $("#modal_action"),
            width: '100%'
        });
    </script>
</x-modal>
