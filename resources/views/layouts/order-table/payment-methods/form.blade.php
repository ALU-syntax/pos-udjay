@php
    $paymentMethod = $paymentMethod ?? $data;
    $editing = filled($paymentMethod->id);
@endphp

{{-- ob-page memberi token & gaya report ke modal (modal dirender di luar .main-content) --}}
<x-modal addStyle="modal-lg ob-page ob-modal" title="{{ $editing ? 'Edit Metode Pembayaran' : 'Tambah Metode Pembayaran' }}"
    update="{{ $editing }}"
    action="{{ $editing ? route('order-table/payment-methods/update', $paymentMethod->id) : route('order-table/payment-methods/store') }}"
    method="POST">
    @if ($editing)
        @method('put')
    @endif

    <div class="col-12">
        <p class="ob-modal__desc">Mapping menghubungkan pilihan pelanggan dengan master pembayaran POS.</p>
    </div>
    {{-- Hanya saat config mematikan Bayar di Kasir; JS menampilkannya ketika jenis itu dipilih --}}
    @unless (config('order-table.pay_at_cashier_enabled'))
        <div class="col-12 cashier-mapping-warning d-none">
            <div class="ob-notice" role="note">
                <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                <span><strong>Bayar di Kasir</strong> belum diizinkan di environment ini. Metode tetap bisa disimpan, tetapi statusnya harus Nonaktif.</span>
            </div>
        </div>
    @endunless

    {{-- Metode --}}
    <div class="col-12 ob-form-heading ob-form-heading--first">
        <h3 class="ob-section-title"><i class="fas fa-credit-card" aria-hidden="true"></i> Outlet &amp; Metode</h3>
    </div>
    <div class="col-md-6 mb-3">
        <label for="outlet_id" class="form-label">Outlet <span class="text-danger">*</span></label>
        <select id="outlet_id" name="outlet_id" class="form-select" required>
            <option value="">Pilih outlet</option>
            @foreach ($outlets as $outlet)
                <option value="{{ $outlet->id }}" @selected(old('outlet_id', $paymentMethod->outlet_id) == $outlet->id)>{{ $outlet->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6 mb-3">
        <label for="code" class="form-label">Jenis / Kode <span class="text-danger">*</span></label>
        <select id="code" name="code" class="form-select" required>
            <option value="">Pilih jenis pembayaran</option>
            <option value="qris" @selected(old('code', $paymentMethod->code) === 'qris')>QRIS</option>
            <option value="pay_at_cashier" @selected(old('code', $paymentMethod->code) === 'pay_at_cashier')>Bayar di Kasir{{ config('order-table.pay_at_cashier_enabled') ? '' : ' (belum dapat diaktifkan)' }}</option>
        </select>
    </div>
    <div class="col-md-6 mb-3">
        <label for="label" class="form-label">Label untuk Pelanggan <span class="text-danger">*</span></label>
        <input id="label" name="label" type="text" maxlength="100" class="form-control"
            value="{{ old('label', $paymentMethod->label) }}" placeholder="Bayar dengan QRIS" required>
    </div>
    <div class="col-md-6 mb-3">
        <label for="mapping_preview" class="form-label">Mapping POS</label>
        <input id="mapping_preview" class="form-control" value="Dipilih otomatis berdasarkan jenis pembayaran" readonly data-mapping-preview>
        <small class="text-muted">Mapping master dikunci server dan tidak dapat diedit manual.</small>
    </div>

    {{-- Batas waktu & status --}}
    <div class="col-12 ob-form-heading">
        <h3 class="ob-section-title"><i class="far fa-clock" aria-hidden="true"></i> Batas Waktu &amp; Status</h3>
    </div>
    <div class="col-md-6 mb-3 d-none" data-code-field="qris">
        <label for="qris_expiry_minutes" class="form-label">Masa Berlaku QRIS</label>
        <div class="input-group">
            <input id="qris_expiry_minutes" name="qris_expiry_minutes" type="number" min="1" class="form-control ob-input-suffixed"
                value="{{ old('qris_expiry_minutes', $paymentMethod->qris_expiry_minutes ?? 15) }}">
            <span class="input-group-text ob-input-suffix">menit</span>
        </div>
        <small class="text-muted">QR pembayaran akan kedaluwarsa setelah durasi ini.</small>
    </div>
    <div class="col-md-6 mb-3 d-none" data-code-field="pay_at_cashier">
        <label for="payment_due_minutes" class="form-label">Batas Bayar di Kasir</label>
        <div class="input-group">
            <input id="payment_due_minutes" name="payment_due_minutes" type="number" min="1" class="form-control ob-input-suffixed"
                value="{{ old('payment_due_minutes', $paymentMethod->payment_due_minutes ?? 60) }}">
            <span class="input-group-text ob-input-suffix">menit</span>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <label for="sort_order" class="form-label">Urutan <span class="text-danger">*</span></label>
        <input id="sort_order" name="sort_order" type="number" min="0" class="form-control"
            value="{{ old('sort_order', $paymentMethod->sort_order ?? 0) }}" required>
    </div>
    <div class="col-md-3 mb-3">
        <label for="enabled" class="form-label">Status <span class="text-danger">*</span></label>
        <select id="enabled" name="enabled" class="form-select" required>
            <option value="1" @selected(old('enabled', $paymentMethod->enabled ?? true) == 1)>Aktif</option>
            <option value="0" @selected(old('enabled', $paymentMethod->enabled ?? true) == 0)>Nonaktif</option>
        </select>
    </div>
</x-modal>
