{{-- ob-page memberi token & gaya report ke modal (modal dirender di luar .main-content) --}}
<x-modal title="{{ $data->id ? 'Edit Kategori Bahan Baku' : 'Tambah Kategori Bahan Baku' }}" addStyle="ob-page ob-modal"
    update="{{ $data->id ? true : false }}"
    action="{{ $action }}" method="POST">
    @if ($data->id)
        @method('put')
    @endif

    @php
        $selectedStatus = (string) (int) old('is_active', $data->exists ? $data->is_active : 1);
    @endphp

    <div class="col-12">
        <p class="ob-modal__desc">Kategori mengelompokkan bahan baku agar pencarian, pembelian, dan stok lebih rapi.</p>
    </div>

    {{-- Identitas --}}
    <div class="col-12 ob-form-heading ob-form-heading--first">
        <h3 class="ob-section-title"><i class="fas fa-layer-group" aria-hidden="true"></i> Identitas Kategori</h3>
    </div>
    <div class="col-12 mb-3">
        <label class="form-label" for="name">Nama Kategori <span class="text-danger">*</span></label>
        <input id="name" name="name" value="{{ old('name', $data->name) }}" type="text" class="form-control"
            placeholder="Contoh: Daging, Susu & Olahan, Bahan Kering, Kemasan" maxlength="255" required>
        <small class="text-muted">Harus unik. Gunakan nama yang mudah dikenali tim gudang dan pembelian.</small>
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

    {{-- Catatan --}}
    <div class="col-12 ob-form-heading">
        <h3 class="ob-section-title"><i class="fas fa-sticky-note" aria-hidden="true"></i> Catatan</h3>
    </div>
    <div class="col-12 mb-3">
        <label class="form-label" for="notes">Catatan</label>
        <textarea id="notes" name="notes" class="form-control" rows="3" maxlength="1000"
            placeholder="Contoh: kategori untuk bahan yang wajib masuk cold storage">{{ old('notes', $data->notes) }}</textarea>
        <small class="text-muted">Maksimal 1.000 karakter. Tampil di bawah nama kategori pada daftar.</small>
    </div>
</x-modal>
