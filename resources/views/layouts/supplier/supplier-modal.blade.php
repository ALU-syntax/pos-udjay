{{-- ob-page memberi token & gaya report ke modal (modal dirender di luar .main-content) --}}
<x-modal title="{{ $data->id ? 'Edit Supplier' : 'Tambah Supplier' }}" addStyle="ob-page ob-modal"
    update="{{ $data->id ? true : false }}"
    action="{{ $action }}" method="POST">
    @if ($data->id)
        @method('put')
    @endif

    @php
        $selectedProcurementMode = (string) old('procurement_mode', $data->procurement_mode ?? \App\Enums\ProcurementMode::BOTH->value);
        $selectedStatus = (string) (int) old('is_active', $data->is_active ?? true);
    @endphp

    <div class="col-12">
        <p class="ob-modal__desc">Kontak, channel pemesanan, dan bahan baku diatur dari halaman detail supplier.</p>
    </div>

    {{-- Identitas --}}
    <div class="col-12 ob-form-heading ob-form-heading--first">
        <h3 class="ob-section-title"><i class="fas fa-truck" aria-hidden="true"></i> Identitas Supplier</h3>
    </div>
    <div class="col-md-8 mb-3">
        <label class="form-label" for="name">Nama Supplier <span class="text-danger">*</span></label>
        <input id="name" name="name" value="{{ old('name', $data->name) }}" type="text" class="form-control"
            placeholder="Contoh: CV Sumber Pangan" required maxlength="255">
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label" for="code">Kode</label>
        <input id="code" name="code" value="{{ old('code', $data->code) }}" type="text" class="form-control"
            placeholder="SUP-001" maxlength="50">
        <small class="text-muted">Opsional, harus unik.</small>
    </div>

    {{-- Pengaturan --}}
    <div class="col-12 ob-form-heading">
        <h3 class="ob-section-title"><i class="fas fa-sliders-h" aria-hidden="true"></i> Pengaturan</h3>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label" for="procurement_mode">Mode Pembelian <span class="text-danger">*</span></label>
        <select id="procurement_mode" name="procurement_mode" class="form-select" required>
            @foreach (\App\Enums\ProcurementMode::options() as $value => $label)
                <option value="{{ $value }}" @selected($selectedProcurementMode === (string) $value)>{{ $label }}</option>
            @endforeach
        </select>
        <small class="text-muted">Cara bahan dibeli dari supplier ini.</small>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label" for="is_active">Status <span class="text-danger">*</span></label>
        <select id="is_active" name="is_active" class="form-select" required>
            <option value="1" @selected($selectedStatus === '1')>Aktif</option>
            <option value="0" @selected($selectedStatus === '0')>Nonaktif</option>
        </select>
        <small class="text-muted">Supplier nonaktif tidak bisa dipilih di procurement plan.</small>
    </div>
    <div class="col-12 mb-3">
        <label class="form-label" for="notes">Catatan</label>
        <textarea id="notes" name="notes" class="form-control" rows="3"
            placeholder="Contoh: pembayaran tempo 14 hari, kirim hanya pagi hari">{{ old('notes', $data->notes) }}</textarea>
    </div>
</x-modal>
