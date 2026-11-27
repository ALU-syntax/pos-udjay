{{-- ob-page memberi token & gaya report ke modal (modal dirender di luar .main-content) --}}
<x-modal addStyle="ob-page ob-modal" :title="isset($update) ? 'Edit Stok Bahan Baku' : 'Tambah Bahan Baku ke ' . $inventory->name"
    update="{{ isset($update) && $update }}" action="{{ $action }}" method="POST">
    @if (isset($update) && $update)
        @method('put')
    @endif

    @php
        $selectedId = old('raw_material_id', $item->raw_material_id);
        $selected = $rawMaterials->firstWhere('id', $selectedId);
        $unitOf = fn ($material) => $material?->baseUnit?->symbol ?: $material?->baseUnit?->name ?: '';
        // Qty tanpa nol di belakang koma agar input tidak tampil "25000.00000"
        $plain = fn ($value) => rtrim(rtrim(number_format((float) $value, 5, '.', ''), '0'), '.');
    @endphp

    <div class="col-12">
        <p class="ob-modal__desc">Saldo stok bahan di lokasi ini. Qty dicatat dalam satuan dasar bahan.</p>
    </div>

    {{-- Bahan --}}
    <div class="col-12 ob-form-heading ob-form-heading--first">
        <h3 class="ob-section-title"><i class="fas fa-cube" aria-hidden="true"></i> Bahan Baku</h3>
    </div>
    <div class="col-12 mb-3">
        <label class="form-label" for="raw_material_id">Bahan Baku <span class="text-danger">*</span></label>
        <select id="raw_material_id" name="raw_material_id" class="form-select select2InsideModal" required>
            <option value="">Pilih bahan baku</option>
            @foreach ($rawMaterials as $material)
                <option value="{{ $material->id }}" data-unit="{{ $unitOf($material) }}" @selected($selectedId == $material->id)>
                    {{ $material->name }}{{ $material->code ? ' · ' . $material->code : '' }}{{ $unitOf($material) ? ' (' . $unitOf($material) . ')' : '' }}{{ $material->is_active ? '' : ' (Nonaktif)' }}
                </option>
            @endforeach
        </select>
        @if ($rawMaterials->isEmpty())
            <small class="text-danger">Semua bahan baku aktif sudah ada di lokasi ini.</small>
        @else
            <small class="text-muted">Hanya bahan aktif yang belum tercatat di lokasi ini.</small>
        @endif
    </div>

    {{-- Qty --}}
    <div class="col-12 ob-form-heading">
        <h3 class="ob-section-title"><i class="fas fa-balance-scale" aria-hidden="true"></i> Jumlah Stok</h3>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label" for="qty_available">Tersedia <span class="text-danger">*</span></label>
        <div class="input-group">
            <input id="qty_available" name="qty_available" type="number" step="0.00001" min="0" class="form-control ob-input-suffixed"
                value="{{ old('qty_available', $plain($item->qty_available ?? 0)) }}" required>
            <span class="input-group-text ob-input-suffix stock-unit">{{ $unitOf($selected) ?: 'satuan' }}</span>
        </div>
        <small class="text-muted">Stok fisik yang ada di lokasi ini.</small>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label" for="qty_reserved">Dipesan <span class="text-danger">*</span></label>
        <div class="input-group">
            <input id="qty_reserved" name="qty_reserved" type="number" step="0.00001" min="0" class="form-control ob-input-suffixed"
                value="{{ old('qty_reserved', $plain($item->qty_reserved ?? 0)) }}" required>
            <span class="input-group-text ob-input-suffix stock-unit">{{ $unitOf($selected) ?: 'satuan' }}</span>
        </div>
        <small class="text-muted">Sudah dialokasikan, belum keluar. Tidak boleh lebih dari Tersedia.</small>
    </div>

    <script>
        $('.select2InsideModal').select2({
            dropdownParent: $("#modal_action"),
            width: '100%'
        });

        // Satuan di samping input mengikuti bahan yang dipilih
        $('#raw_material_id').on('change', function () {
            const unit = $(this).find(':selected').data('unit');
            $('#modal_action .stock-unit').text(unit || 'satuan');
        });
    </script>
</x-modal>
