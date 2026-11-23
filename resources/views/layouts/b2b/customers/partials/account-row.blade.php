{{-- Satu baris rekening di form customer. Nama input diberi B2b.reindex(): accounts[i][field]. --}}
@php
    $provider = $account['provider'] ?? '';
    $isEwallet = \App\Models\B2b\CustomerAccount::typeOf($provider) === \App\Models\B2b\CustomerAccount::TYPE_EWALLET;
@endphp
<div class="b2b-repeater-row b2b-account-row">
    <button type="button" class="ob-icon-btn ob-icon-btn--sm ob-icon-btn--danger b2b-repeater-row__remove" data-account-remove title="Hapus rekening" aria-label="Hapus rekening">
        <i class="fas fa-trash-alt" aria-hidden="true"></i>
    </button>
    <div class="b2b-fields">
        <div class="b2b-col-4 b2b-field">
            <label class="form-label">Bank / E-wallet <span class="b2b-required">*</span></label>
            <select class="form-select" data-field="provider" aria-label="Bank / E-wallet">
                <option value="">Pilih</option>
                @foreach (\App\Models\B2b\CustomerAccount::providerOptions() as $group => $options)
                    <optgroup label="{{ $group }}">
                        @foreach ($options as $code => $label)
                            <option value="{{ $code }}" @selected($code === $provider)>{{ $label }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>
        <div class="b2b-col-4 b2b-field">
            <label class="form-label"><span data-number-label>{{ $isEwallet ? 'Nomor HP' : 'Nomor rekening' }}</span> <span class="b2b-required">*</span></label>
            <input class="form-control b2b-mono" data-field="account_number" value="{{ $account['account_number'] ?? '' }}" maxlength="30" inputmode="numeric"
                placeholder="{{ $isEwallet ? '081234567890' : '1234567890' }}" aria-label="Nomor rekening / HP">
        </div>
        <div class="b2b-col-4 b2b-field">
            <label class="form-label">Atas nama <span class="b2b-required">*</span></label>
            <input class="form-control" data-field="account_name" value="{{ $account['account_name'] ?? '' }}" maxlength="255" placeholder="Sesuai nama di rekening" aria-label="Atas nama">
        </div>
        <div class="b2b-field">
            <label class="form-label">Catatan <span class="b2b-optional">(opsional)</span></label>
            <input class="form-control" data-field="notes" value="{{ $account['notes'] ?? '' }}" maxlength="255" placeholder="Contoh: rekening pribadi owner" aria-label="Catatan rekening">
        </div>
    </div>
</div>
