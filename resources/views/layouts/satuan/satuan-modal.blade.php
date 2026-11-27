{{-- ob-page memberi token & gaya report ke modal (modal dirender di luar .main-content) --}}
<x-modal title="{{ $data->id ? 'Edit Satuan' : 'Tambah Satuan' }}" addStyle="ob-page ob-modal"
    update="{{ $data->id ? true : false }}"
    action="{{ $action }}" method="POST">
    @if ($data->id)
        @method('put')
    @endif

    @php
        $selectedStatus = (string) (int) old('is_active', $data->exists ? $data->is_active : 1);
    @endphp

    <div class="col-12">
        <p class="ob-modal__desc">Satuan dipakai sebagai satuan dasar bahan baku, satuan beli di supplier, dan di PO.</p>
    </div>

    {{-- Identitas --}}
    <div class="col-12 ob-form-heading ob-form-heading--first">
        <h3 class="ob-section-title"><i class="fas fa-balance-scale" aria-hidden="true"></i> Identitas Satuan</h3>
    </div>
    <div class="col-md-8 mb-3">
        <label class="form-label" for="name">Nama Satuan <span class="text-danger">*</span></label>
        <input id="name" name="name" value="{{ old('name', $data->name) }}" type="text" class="form-control"
            placeholder="Contoh: Kilogram, Liter, Pcs" required maxlength="255">
        <small class="text-muted">Harus unik.</small>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label" for="symbol">Simbol</label>
        <input id="symbol" name="symbol" value="{{ old('symbol', $data->symbol) }}" type="text" class="form-control"
            placeholder="kg, g, ml" maxlength="20">
        <small class="text-muted">Tampil di formula konversi.</small>
    </div>

    {{-- Pengaturan --}}
    <div class="col-12 ob-form-heading">
        <h3 class="ob-section-title"><i class="fas fa-sliders-h" aria-hidden="true"></i> Pengaturan</h3>
    </div>
    <div class="col-12 mb-3">
        <label class="form-label" for="is_active">Status <span class="text-danger">*</span></label>
        <select id="is_active" name="is_active" class="form-select" required>
            <option value="1" @selected($selectedStatus === '1')>Aktif</option>
            <option value="0" @selected($selectedStatus === '0')>Nonaktif</option>
        </select>
    </div>
</x-modal>
