{{-- ob-page memberi token & gaya report ke modal (modal dirender di luar .main-content) --}}
<x-modal title="{{ $data->id ? 'Edit Kategori' : 'Tambah Kategori' }}" addStyle="ob-page ob-modal"
    update="{{ $data->id ? true : false }}"
    action="{{ $action }}" method="POST">
    @if ($data->id)
        @method('put')
    @endif

    <div class="col-12">
        <p class="ob-modal__desc">Kategori berlaku untuk semua outlet.</p>
    </div>

    {{-- Informasi kategori --}}
    <div class="col-12 ob-form-heading ob-form-heading--first">
        <h3 class="ob-section-title"><i class="fas fa-tag" aria-hidden="true"></i> Informasi Kategori</h3>
    </div>
    <div class="col-12 mb-3">
        <label class="form-label" for="name">Nama Kategori <span class="text-danger">*</span></label>
        <input id="name" name="name" value="{{ old('name', $data->name ?? '') }}" type="text"
            class="form-control @error('name') is-invalid @enderror"
            placeholder="Contoh: Coffee, Non Coffee, Pastry" required maxlength="100">
        @error('name')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
        <small class="text-muted">Maksimal 100 karakter.</small>
    </div>

    {{-- Pengaturan --}}
    <div class="col-12 ob-form-heading">
        <h3 class="ob-section-title"><i class="fas fa-sliders-h" aria-hidden="true"></i> Pengaturan</h3>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label" for="status">Status <span class="text-danger">*</span></label>
        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
            <option value="">Pilih status</option>
            <option value="1" @if (old('status', $data->status ?? '') == 1) selected @endif>Aktif</option>
            <option value="0" @if (old('status', $data->status ?? '') == 0) selected @endif>Tidak Aktif</option>
        </select>
        @error('status')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label" for="reward_categories">Kategori Reward <span class="text-danger">*</span></label>
        <select name="reward_categories" id="reward_categories"
            class="form-select @error('reward_categories') is-invalid @enderror" required>
            <option value="">Pilih jenis kategori</option>
            <option value="1" @if (old('reward_categories', $data->reward_categories ?? '') == 1) selected @endif>Iya, kategori reward</option>
            <option value="0" @if (old('reward_categories', $data->reward_categories ?? '') == 0) selected @endif>Tidak, kategori biasa</option>
        </select>
        @error('reward_categories')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
        <small class="text-muted">Kategori reward dipakai untuk program loyalitas.</small>
    </div>
    <div class="col-12 mb-3">
        <label class="form-label" for="show_in_order_table">Tampil di Order Table <span class="text-danger">*</span></label>
        <select name="show_in_order_table" id="show_in_order_table"
            class="form-select @error('show_in_order_table') is-invalid @enderror" required>
            <option value="1" @if (old('show_in_order_table', $data->show_in_order_table ?? 1) == 1) selected @endif>
                Iya, tampilkan di menu pelanggan
            </option>
            <option value="0" @if (old('show_in_order_table', $data->show_in_order_table ?? 1) == 0) selected @endif>
                Tidak, khusus kasir
            </option>
        </select>
        @error('show_in_order_table')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
        <small class="text-muted">Matikan untuk kategori internal seperti Waste, Kalibrasi, atau Compliment.</small>
    </div>
</x-modal>
