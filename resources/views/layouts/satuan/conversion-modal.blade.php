<x-modal addStyle="modal-lg" title="{{ isset($update) ? 'Edit Konversi Bahan Baku' : 'Tambah Konversi Bahan Baku' }}" action="{{ $action }}" method="POST">
    @if (isset($update) && $update)
        @method('put')
    @endif

    <div class="col-sm-12 mb-3">
        <div class="form-group">
            <label>Bahan Baku <span class="text-danger">*</span></label>
            <select name="raw_material_id" class="form-select select2InsideModal" required>
                <option value="">Pilih bahan baku</option>
                @foreach ($rawMaterials as $material)
                    @php
                        $baseUnit = optional($material->baseUnit)->symbol ?: optional($material->baseUnit)->name;
                    @endphp
                    <option value="{{ $material->id }}"
                        {{ old('raw_material_id', $conversion->raw_material_id) == $material->id ? 'selected' : '' }}>
                        {{ $material->name }}{{ $baseUnit ? ' (' . $baseUnit . ')' : '' }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="col-sm-12 mb-3 row g-2">
        <div class="col-md-6">
            <div class="form-group">
                <label>Dari Satuan <span class="text-danger">*</span></label>
                <select name="from_unit_id" class="form-select select2InsideModal" required>
                    <option value="">Pilih satuan asal</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}"
                            {{ old('from_unit_id', $conversion->from_unit_id) == $unit->id ? 'selected' : '' }}>
                            {{ $unit->name }}{{ $unit->symbol ? ' (' . $unit->symbol . ')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Ke Satuan <span class="text-danger">*</span></label>
                <select name="to_unit_id" class="form-select select2InsideModal" required>
                    <option value="">Pilih satuan tujuan</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}"
                            {{ old('to_unit_id', $conversion->to_unit_id) == $unit->id ? 'selected' : '' }}>
                            {{ $unit->name }}{{ $unit->symbol ? ' (' . $unit->symbol . ')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="col-sm-12 mb-3">
        <div class="form-group">
            <label>Multiplier <span class="text-danger">*</span></label>
            <input name="multiplier" value="{{ old('multiplier', $conversion->multiplier) }}" type="number"
                step="0.000001" min="0.000001" class="form-control" placeholder="Contoh: 1000" required>
        </div>
    </div>

    <div class="col-sm-12 mb-3">
        <div class="form-group">
            <label>Catatan</label>
            <textarea name="notes" class="form-control" rows="3" placeholder="Contoh: 1 box telur = 15 kg">{{ old('notes', $conversion->notes) }}</textarea>
        </div>
    </div>
</x-modal>

<script>
    $('.select2InsideModal').select2({
        dropdownParent: $("#modal_action"),
        width: '100%'
    });
</script>
