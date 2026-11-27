{{-- ob-page memberi token & gaya report ke modal (modal dirender di luar .main-content) --}}
<x-modal addStyle="modal-lg ob-page ob-modal" title="{{ isset($update) ? 'Edit Konversi Bahan Baku' : 'Tambah Konversi Bahan Baku' }}"
    update="{{ isset($update) ? true : false }}"
    action="{{ $action }}" method="POST">
    @if (isset($update) && $update)
        @method('put')
    @endif

    @php
        $unitLabel = fn ($unit) => $unit->symbol ?: $unit->name;
    @endphp

    <div class="col-12">
        <p class="ob-modal__desc">Konversi berlaku khusus untuk satu bahan baku, misalnya 1 box telur = 15 kg.</p>
    </div>

    {{-- Bahan baku --}}
    <div class="col-12 ob-form-heading ob-form-heading--first">
        <h3 class="ob-section-title"><i class="fas fa-cubes" aria-hidden="true"></i> Bahan Baku</h3>
    </div>
    <div class="col-12 mb-3">
        <label class="form-label" for="raw_material_id">Bahan Baku <span class="text-danger">*</span></label>
        <select id="raw_material_id" name="raw_material_id" class="form-select select2InsideModal" required>
            <option value="">Pilih bahan baku</option>
            @foreach ($rawMaterials as $material)
                @php
                    $baseUnit = $material->baseUnit ? $unitLabel($material->baseUnit) : null;
                @endphp
                <option value="{{ $material->id }}" data-base-unit="{{ $baseUnit }}"
                    @selected((string) old('raw_material_id', $conversion->raw_material_id) === (string) $material->id)>
                    {{ $material->name }}{{ $baseUnit ? ' (' . $baseUnit . ')' : '' }}
                </option>
            @endforeach
        </select>
        <small class="text-muted" id="conversion-base-hint">Satuan dasar bahan baku tampil setelah bahan dipilih.</small>
    </div>

    {{-- Formula --}}
    <div class="col-12 ob-form-heading">
        <h3 class="ob-section-title"><i class="fas fa-random" aria-hidden="true"></i> Formula</h3>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label" for="from_unit_id">Dari Satuan <span class="text-danger">*</span></label>
        <select id="from_unit_id" name="from_unit_id" class="form-select select2InsideModal" required>
            <option value="">Pilih satuan asal</option>
            @foreach ($units as $unit)
                <option value="{{ $unit->id }}" data-label="{{ $unitLabel($unit) }}"
                    @selected((string) old('from_unit_id', $conversion->from_unit_id) === (string) $unit->id)>
                    {{ $unit->name }}{{ $unit->symbol ? ' (' . $unit->symbol . ')' : '' }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label" for="multiplier">Isi per 1 Satuan Asal <span class="text-danger">*</span></label>
        <input id="multiplier" name="multiplier" value="{{ old('multiplier', $conversion->multiplier ? (float) $conversion->multiplier : null) }}"
            type="number" step="0.000001" min="0.000001" class="form-control" placeholder="Contoh: 1000" required>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label" for="to_unit_id">Ke Satuan <span class="text-danger">*</span></label>
        <select id="to_unit_id" name="to_unit_id" class="form-select select2InsideModal" required>
            <option value="">Pilih satuan tujuan</option>
            @foreach ($units as $unit)
                <option value="{{ $unit->id }}" data-label="{{ $unitLabel($unit) }}"
                    @selected((string) old('to_unit_id', $conversion->to_unit_id) === (string) $unit->id)>
                    {{ $unit->name }}{{ $unit->symbol ? ' (' . $unit->symbol . ')' : '' }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-12 mb-3">
        <div class="ob-notice sat-formula-preview" aria-live="polite">
            <i class="fas fa-equals" aria-hidden="true"></i>
            <span id="conversion-preview">Lengkapi satuan dan isi untuk melihat formula.</span>
        </div>
    </div>

    {{-- Catatan --}}
    <div class="col-12 ob-form-heading">
        <h3 class="ob-section-title"><i class="fas fa-sticky-note" aria-hidden="true"></i> Catatan</h3>
    </div>
    <div class="col-12 mb-3">
        <label class="form-label" for="notes">Catatan</label>
        <textarea id="notes" name="notes" class="form-control" rows="3"
            placeholder="Contoh: berat bersih per box dari supplier">{{ old('notes', $conversion->notes) }}</textarea>
    </div>
</x-modal>

<script>
    $('.select2InsideModal').select2({
        dropdownParent: $("#modal_action"),
        width: '100%'
    });

    (function () {
        const modal = $('#modal_action');
        const label = (selector) => modal.find(selector + ' option:selected').data('label');

        function renderPreview() {
            const from = label('#from_unit_id');
            const to = label('#to_unit_id');
            const multiplier = parseFloat(modal.find('#multiplier').val());
            const preview = modal.find('#conversion-preview');

            if (!from || !to || !(multiplier > 0)) {
                preview.text('Lengkapi satuan dan isi untuk melihat formula.');
                return;
            }

            const amount = multiplier.toLocaleString('id-ID', { maximumFractionDigits: 6 });
            preview.text('1 ' + from + ' = ' + amount + ' ' + to);
        }

        function renderBaseHint() {
            const baseUnit = modal.find('#raw_material_id option:selected').data('base-unit');
            modal.find('#conversion-base-hint').text(baseUnit
                ? 'Satuan dasar bahan ini: ' + baseUnit + '.'
                : 'Satuan dasar bahan baku tampil setelah bahan dipilih.');
        }

        modal.find('#from_unit_id, #to_unit_id').on('change', renderPreview);
        modal.find('#multiplier').on('input', renderPreview);
        modal.find('#raw_material_id').on('change', renderBaseHint);
        renderPreview();
        renderBaseHint();
    })();
</script>
