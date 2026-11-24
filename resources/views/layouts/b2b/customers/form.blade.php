@extends('layouts.app')

@php
    $editing = $customer->exists;
@endphp

@section('content')
    @include('layouts.b2b.partials.assets')
    @include('layouts.b2b.partials.page-styles')
    @push('css')
        <style>
            /* Dropdown wilayah (select2) disamakan dengan .form-control di form B2B */
            .b2b-region + .select2-container .select2-selection--single {
                height: 42px; border: 1px solid var(--ob-border-strong); border-radius: var(--ob-radius-sm); background: var(--ob-surface);
            }
            .b2b-region + .select2-container .select2-selection--single .select2-selection__rendered { padding-left: .85rem; line-height: 40px; font-size: .875rem; color: var(--ob-text); }
            .b2b-region + .select2-container .select2-selection--single .select2-selection__placeholder { color: #94a3b8; }
            .b2b-region + .select2-container .select2-selection--single .select2-selection__rendered { padding-right: 3.6rem; }
            .b2b-region + .select2-container .select2-selection--single .select2-selection__arrow { height: 40px; right: .5rem; }
            .b2b-region + .select2-container .select2-selection--single .select2-selection__clear {
                position: absolute; top: 50%; right: 2rem; transform: translateY(-50%); display: inline-grid; place-items: center;
                width: 24px; height: 24px; margin: 0; padding: 0; border: 0; border-radius: 6px; background: transparent; color: var(--ob-muted); font-size: 1.05rem; line-height: 1;
            }
            .b2b-region + .select2-container .select2-selection--single .select2-selection__clear:hover { color: var(--ob-danger); }
            .select2-dropdown.b2b-region-dropdown { border-color: var(--ob-border-strong); border-radius: var(--ob-radius-sm); overflow: hidden; box-shadow: 0 12px 32px rgba(15, 23, 42, .12); font-size: .85rem; }
            .select2-dropdown.b2b-region-dropdown .select2-search__field { height: 36px; border-radius: 8px; border-color: var(--ob-border-strong); }
            .select2-dropdown.b2b-region-dropdown .select2-results__option--highlighted { background: #1572e8; }
            .b2b-region + .select2-container--focus .select2-selection--single,
            .b2b-region + .select2-container--open .select2-selection--single { border-color: var(--ob-primary); }
            .b2b-region + .select2-container--disabled .select2-selection--single { background: var(--ob-surface-2, #f1f5f9); cursor: not-allowed; }
            .b2b-region.is-invalid + .select2-container .select2-selection--single { border-color: var(--ob-danger); }
            /* Baris rekening: ruang kanan untuk tombol hapus */
            .b2b-account-row { padding: 1rem 3.25rem 1rem 1rem; }
            .b2b-account-row .b2b-fields { gap: .75rem 1rem; }
        </style>
    @endpush

    @php
        $typeOptions = [
            'individual' => ['icon' => 'fa-user', 'hint' => 'Individu / usaha pribadi'],
            'pt' => ['icon' => 'fa-building', 'hint' => 'Perseroan Terbatas'],
            'cv' => ['icon' => 'fa-briefcase', 'hint' => 'Persekutuan Komanditer'],
        ];
        $reminderChannels = old('reminder_channels', $customer->reminder_channels ?? []);
        $reminderDays = array_map('intval', old('reminder_days', $customer->reminder_days ?? []));
        $accounts = $editing ? $customer->accounts->map->only(['provider', 'account_number', 'account_name', 'notes'])->all() : [];
    @endphp

    <div class="main-content ob-page">
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">
                    <a href="{{ route('b2b/customers') }}">Customer B2B</a>
                    @if ($editing)
                        <span class="b2b-crumb-sep" aria-hidden="true">/</span><a href="{{ route('b2b/customers/show', $customer->id) }}">{{ $customer->code }}</a>
                    @endif
                </div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas {{ $editing ? 'fa-pen' : 'fa-plus' }}"></i></span>
                    {{ $editing ? 'Edit '.$customer->name : 'Tambah Customer' }}
                </h1>
                <p class="ob-header__desc">
                    @if ($editing)
                        Perubahan alamat tidak mengubah pesanan dan invoice yang sudah dibuat.
                    @else
                        Kode customer dibuat otomatis setelah disimpan.
                    @endif
                </p>
            </div>
        </div>

        <form id="customer-form" class="b2b-form" action="{{ $editing ? route('b2b/customers/update', $customer->id) : route('b2b/customers/store') }}" method="POST" novalidate>
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <div class="b2b-form-grid">
                <div>
                    <div class="ob-card">
                        <div class="b2b-card__head">
                            <div>
                                <h2 class="b2b-card__title"><i class="fas fa-id-card" aria-hidden="true"></i> Data Customer</h2>
                                <p class="b2b-card__desc">Identitas yang tampil di kasir, pesanan, dan invoice.</p>
                            </div>
                        </div>
                        <div class="b2b-card__body">
                            <div class="b2b-fields">
                                <div class="b2b-field" data-error-for="type">
                                    <span class="b2b-group-label">Jenis customer <span class="b2b-required">*</span></span>
                                    <div class="b2b-types" role="radiogroup" aria-label="Jenis customer">
                                        @foreach (\App\Models\B2b\Customer::TYPES as $value => $label)
                                            <label class="b2b-type">
                                                <input type="radio" name="type" value="{{ $value }}" @checked(old('type', $customer->type) === $value)>
                                                <span class="b2b-type__box">
                                                    <span class="b2b-type__icon" aria-hidden="true"><i class="fas {{ $typeOptions[$value]['icon'] ?? 'fa-tag' }}"></i></span>
                                                    <span class="b2b-type__text"><strong>{{ $label }}</strong><small>{{ $typeOptions[$value]['hint'] ?? '' }}</small></span>
                                                    <i class="fas fa-check-circle b2b-type__check" aria-hidden="true"></i>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="b2b-col-6 b2b-field">
                                    <label for="name" class="form-label"><span id="name-label">Nama</span> <span class="b2b-required">*</span></label>
                                    <input id="name" name="name" class="form-control" value="{{ old('name', $customer->name) }}" maxlength="255" required placeholder="Tulis lengkap, contoh: PT Sumber Rejeki">
                                </div>
                                <div class="b2b-col-6 b2b-field">
                                    <label for="npwp" class="form-label">NPWP <span class="b2b-optional">(opsional)</span></label>
                                    <input id="npwp" name="npwp" class="form-control" value="{{ old('npwp', $customer->npwp) }}" maxlength="30" inputmode="numeric" placeholder="00.000.000.0-000.000">
                                </div>
                                <div class="b2b-field">
                                    <label for="notes" class="form-label">Catatan <span class="b2b-optional">(opsional)</span></label>
                                    <textarea id="notes" name="notes" rows="2" class="form-control" maxlength="2000" placeholder="Catatan internal, tidak dicetak di invoice">{{ old('notes', $customer->notes) }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ob-card">
                        <div class="b2b-card__head">
                            <div>
                                <h2 class="b2b-card__title"><i class="fas fa-user-tie" aria-hidden="true"></i> Kontak</h2>
                                <p class="b2b-card__desc">Orang yang dihubungi untuk pesanan dan penagihan. Telepon dan email juga dipakai untuk reminder.</p>
                            </div>
                        </div>
                        <div class="b2b-card__body">
                            <div class="b2b-fields">
                                <div class="b2b-field">
                                    <label for="contact_name" class="form-label">Nama PIC <span class="b2b-optional">(opsional)</span></label>
                                    <input id="contact_name" name="contact_name" class="form-control" value="{{ old('contact_name', $customer->contact_name) }}" maxlength="255" placeholder="Orang yang dihubungi, contoh: Budi (Finance)">
                                </div>
                                <div class="b2b-col-6 b2b-field">
                                    <label for="phone" class="form-label">Telepon / WhatsApp</label>
                                    <input id="phone" name="phone" class="form-control" value="{{ old('phone', $customer->phone) }}" maxlength="30" inputmode="tel" placeholder="08xxxxxxxxxx">
                                </div>
                                <div class="b2b-col-6 b2b-field">
                                    <label for="email" class="form-label">Email</label>
                                    <input id="email" name="email" type="email" class="form-control" value="{{ old('email', $customer->email) }}" maxlength="255" placeholder="finance@perusahaan.co.id">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ob-card">
                        <div class="b2b-card__head">
                            <div>
                                <h2 class="b2b-card__title"><i class="fas fa-map-marker-alt" aria-hidden="true"></i> Alamat</h2>
                                <p class="b2b-card__desc">Dicetak di invoice dan dipakai sebagai alamat kirim untuk pengiriman internal.</p>
                            </div>
                        </div>
                        <div class="b2b-card__body">
                            <div class="b2b-fields">
                                <div class="b2b-field">
                                    <label for="address" class="form-label">Alamat <span class="b2b-optional">(wajib untuk pengiriman internal)</span></label>
                                    <textarea id="address" name="address" rows="2" class="form-control" maxlength="1000" placeholder="Nama jalan, nomor, gedung, lantai">{{ old('address', $customer->address) }}</textarea>
                                </div>
                                {{-- Dropdown berjenjang: pilihan tiap tingkat dimuat ulang (selectors/regions) saat tingkat di atasnya berubah. --}}
                                @foreach ([
                                    ['province_code', 'Provinsi', 'provinces', 'Pilih provinsi'],
                                    ['regency_code', 'Kota / Kabupaten', 'regencies', 'Pilih kota/kabupaten'],
                                    ['district_code', 'Kecamatan', 'districts', 'Pilih kecamatan'],
                                    ['village_code', 'Kelurahan / Desa', 'villages', 'Pilih kelurahan/desa'],
                                ] as [$field, $label, $level, $placeholder])
                                    <div class="b2b-col-6 b2b-field">
                                        <label for="{{ $field }}" class="form-label">{{ $label }}</label>
                                        <select id="{{ $field }}" name="{{ $field }}" class="form-select b2b-region" data-level="{{ $level }}" data-placeholder="{{ $placeholder }}"
                                            @disabled($level !== 'provinces' && $regions[$level]->isEmpty())>
                                            <option value=""></option>
                                            @foreach ($regions[$level] as $region)
                                                <option value="{{ $region->code }}" @if ($region->postal_code) data-postal-code="{{ $region->postal_code }}" @endif
                                                    @selected($region->code === (string) $customer->{$field})>{{ $region->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endforeach
                                <div class="b2b-col-6 b2b-field">
                                    <label for="postal_code" class="form-label">Kode pos</label>
                                    <input id="postal_code" name="postal_code" class="form-control" value="{{ old('postal_code', $customer->postal_code) }}" maxlength="10" inputmode="numeric">
                                    <small class="b2b-help">Terisi otomatis dari kelurahan/desa, bisa diubah.</small>
                                </div>
                                <div class="b2b-field">
                                    <small class="b2b-help mt-0"><i class="fas fa-truck me-1" aria-hidden="true"></i>Untuk pengiriman internal, isi alamat dan wilayah minimal sampai kota/kabupaten.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ob-card">
                        <div class="b2b-card__head">
                            <div>
                                <h2 class="b2b-card__title"><i class="fas fa-university" aria-hidden="true"></i> Rekening &amp; E-wallet Pengirim</h2>
                                <p class="b2b-card__desc">Rekening yang biasa dipakai customer untuk transfer, untuk mengenali pembayaran di mutasi. Satu rekening hanya boleh terdaftar di satu customer.</p>
                            </div>
                            <button type="button" class="btn ob-btn ob-btn-outline ob-btn-sm" id="account-add">
                                <i class="fas fa-plus" aria-hidden="true"></i> Tambah rekening
                            </button>
                        </div>
                        <div class="b2b-card__body">
                            <div class="b2b-repeater" id="account-rows">
                                @foreach ($accounts as $account)
                                    @include('layouts.b2b.customers.partials.account-row', ['account' => $account])
                                @endforeach
                            </div>
                            <div class="b2b-repeater-empty" id="account-empty" @if (count($accounts)) hidden @endif>
                                Belum ada rekening terdaftar. Customer tetap bisa disimpan tanpa rekening.
                            </div>
                            <small class="b2b-help" id="account-limit" hidden>Maksimal {{ \App\Models\B2b\CustomerAccount::MAX_PER_CUSTOMER }} rekening per customer.</small>
                            <template id="account-template">
                                @include('layouts.b2b.customers.partials.account-row', ['account' => []])
                            </template>
                        </div>
                    </div>
                </div>

                <div>
                    <div class="ob-card">
                        <div class="b2b-card__head">
                            <h2 class="b2b-card__title"><i class="fas fa-toggle-on" aria-hidden="true"></i> Status</h2>
                        </div>
                        <div class="b2b-card__body">
                            <input type="hidden" name="is_active" value="0">
                            <label class="b2b-switch">
                                <input type="checkbox" name="is_active" value="1" @checked($customer->is_active ?? true)>
                                <span class="b2b-switch__track" aria-hidden="true"></span>
                                <span class="b2b-switch__text"><strong>Customer aktif</strong><small>Customer nonaktif tidak bisa dipilih di Kasir B2B.</small></span>
                            </label>
                        </div>
                    </div>

                    <div class="ob-card">
                        <div class="b2b-card__head">
                            <h2 class="b2b-card__title"><i class="fas fa-bell" aria-hidden="true"></i> Reminder Jatuh Tempo</h2>
                        </div>
                        <div class="b2b-card__body">
                            <input type="hidden" name="reminder_enabled" value="0">
                            <label class="b2b-switch">
                                <input type="checkbox" id="reminder_enabled" name="reminder_enabled" value="1" @checked($customer->reminder_enabled)>
                                <span class="b2b-switch__track" aria-hidden="true"></span>
                                <span class="b2b-switch__text"><strong>Kirim reminder ke customer ini</strong><small>Kalau dimatikan, tidak ada reminder yang dikirim.</small></span>
                            </label>

                            <div id="reminder-settings">
                                <hr class="b2b-divider">
                                <div class="b2b-field" data-error-for="reminder_channels">
                                    <span class="b2b-group-label">Channel</span>
                                    <div class="b2b-chips">
                                        @foreach (\App\Models\B2b\Customer::REMINDER_CHANNELS as $value => $label)
                                            <label class="b2b-chip"><input type="checkbox" name="reminder_channels[]" value="{{ $value }}" @checked(in_array($value, $reminderChannels, true))><span>{{ $label }}</span></label>
                                        @endforeach
                                    </div>
                                    <small class="b2b-help">WhatsApp terkirim setelah provider WhatsApp dipilih.</small>
                                </div>
                                <hr class="b2b-divider">
                                <div class="b2b-field" data-error-for="reminder_days">
                                    <span class="b2b-group-label">Jadwal</span>
                                    <div class="b2b-chips">
                                        @foreach (\App\Models\B2b\Customer::REMINDER_DAYS as $value => $label)
                                            <label class="b2b-chip"><input type="checkbox" name="reminder_days[]" value="{{ $value }}" @checked(in_array($value, $reminderDays, true))><span>{{ $label }}</span></label>
                                        @endforeach
                                        <input type="hidden" name="reminder_overdue_daily" value="0">
                                        <label class="b2b-chip"><input type="checkbox" name="reminder_overdue_daily" value="1" @checked($customer->reminder_overdue_daily)><span>Tiap hari setelah lewat</span></label>
                                    </div>
                                    <small class="b2b-help">H-3 = 3 hari sebelum jatuh tempo. Hari H = tanggal jatuh tempo.</small>
                                </div>
                                <hr class="b2b-divider">
                                <div class="b2b-field">
                                    <span class="b2b-group-label">Dikirim ke</span>
                                    <div class="b2b-note">
                                        <i class="fas fa-paper-plane" aria-hidden="true"></i>
                                        <div>
                                            <strong id="reminder-destination" aria-live="polite"></strong>
                                            Diambil dari telepon dan email di bagian Kontak.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <small class="b2b-help">Pengiriman otomatis aktif setelah fitur reminder dibangun.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="b2b-form-footer">
                <span class="b2b-form-footer__hint"><span class="b2b-required">*</span> wajib diisi</span>
                <a href="{{ $editing ? route('b2b/customers/show', $customer->id) : route('b2b/customers') }}" class="btn ob-btn ob-btn-ghost">Batal</a>
                <button type="submit" class="btn ob-btn ob-btn-primary"><i class="fas fa-save" aria-hidden="true"></i> Simpan Customer</button>
            </div>
        </form>
    </div>

    @push('js')
        <script>
            $(function () {
                const form = $('#customer-form');

                // Tujuan reminder mengikuti channel yang dicentang dan telepon/email yang terisi.
                function updateDestination() {
                    const channels = $('input[name="reminder_channels[]"]:checked').map((_, el) => el.value).get();
                    const targets = { email: ['Email', $('#email').val().trim()], whatsapp: ['WhatsApp', $('#phone').val().trim()] };
                    const parts = channels.map(channel => {
                        const [label, value] = targets[channel];
                        return value ? `${label}: ${value}` : `${label}: belum diisi`;
                    });
                    const missing = channels.some(channel => !targets[channel][1]);
                    $('#reminder-destination')
                        .text(parts.length ? parts.join(' · ') : 'Pilih channel dulu')
                        .toggleClass('is-empty', !parts.length || missing);
                }
                form.on('input change', '#email, #phone, input[name="reminder_channels[]"]', updateDestination);
                updateDestination();

                // Label & contoh isian nama mengikuti jenis customer. Nama tetap ditulis lengkap (tidak ditambah PT/CV otomatis).
                const typeHints = {
                    individual: { name: 'Nama lengkap', example: 'Contoh: Budi Santoso' },
                    pt: { name: 'Nama perusahaan', example: 'Tulis lengkap, contoh: PT Sumber Rejeki' },
                    cv: { name: 'Nama perusahaan', example: 'Tulis lengkap, contoh: CV Maju Bersama' },
                };
                function updateTypeHints() {
                    const hint = typeHints[$('input[name="type"]:checked').val()]
                        || { name: 'Nama', example: 'Tulis lengkap, contoh: PT Sumber Rejeki' };
                    $('#name-label').text(hint.name);
                    $('#name').attr('placeholder', hint.example);
                }
                $('input[name="type"]').on('change', updateTypeHints);
                updateTypeHints();

                // Wilayah berjenjang: mengganti satu tingkat mengosongkan tingkat di bawahnya lalu memuat pilihan tingkat berikutnya.
                const regions = $('.b2b-region');
                const regionUrl = @json(route('b2b/selectors/regions'));
                regions.each(function () {
                    $(this).select2({
                        width: '100%', placeholder: this.dataset.placeholder, allowClear: true, dropdownCssClass: 'b2b-region-dropdown',
                        language: { noResults: () => 'Tidak ditemukan' },
                    });
                });
                regions.on('change', function () {
                    // Kelurahan/desa membawa kode pos, jadi kode pos diisi otomatis (tetap bisa diubah manual).
                    const postalCode = $(this).find(':selected').data('postal-code');
                    if (postalCode) $('#postal_code').val(postalCode);

                    const index = regions.index(this);
                    const below = regions.slice(index + 1);
                    below.each(function () {
                        $(this).empty().append('<option value=""></option>').prop('disabled', true).trigger('change.select2');
                    });

                    const next = below.first();
                    if (!next.length || !this.value) return;
                    const parent = this.value;
                    $.getJSON(regionUrl, { level: next.data('level'), parent }).done(response => {
                        if (this.value !== parent) return; // pilihan sudah diganti lagi sebelum respons datang
                        (response.data || []).forEach(item => {
                            const option = new Option(item.text, item.id);
                            if (item.postal_code) option.dataset.postalCode = item.postal_code;
                            next.append(option);
                        });
                        next.prop('disabled', false).trigger('change.select2');
                    }).fail(() => showToast('error', 'Gagal memuat pilihan wilayah'));
                });

                function toggleReminder() {
                    $('#reminder-settings').toggleClass('d-none', !$('#reminder_enabled').is(':checked'));
                }
                $('#reminder_enabled').on('change', toggleReminder);
                toggleReminder();

                // Rekening pengirim: baris bisa ditambah/dihapus; nama input diurutkan ulang supaya error server (accounts.i.field) jatuh ke baris yang benar.
                const accountRows = $('#account-rows');
                const accountMax = {{ \App\Models\B2b\CustomerAccount::MAX_PER_CUSTOMER }};
                const ewallets = @json(\App\Models\AccountProvider::ewalletCodes());
                function refreshAccounts() {
                    B2b.reindex(accountRows, 'accounts');
                    const count = accountRows.children('.b2b-account-row').length;
                    $('#account-empty').prop('hidden', count > 0);
                    $('#account-add').prop('disabled', count >= accountMax);
                    $('#account-limit').prop('hidden', count < accountMax);
                }
                $('#account-add').on('click', function () {
                    const row = $($('#account-template').html());
                    accountRows.append(row);
                    refreshAccounts();
                    row.find('[data-field="provider"]').trigger('focus');
                });
                accountRows.on('click', '[data-account-remove]', function () {
                    $(this).closest('.b2b-account-row').remove();
                    refreshAccounts();
                });
                // E-wallet memakai nomor HP: label dan contoh isian ikut berganti.
                accountRows.on('change', '[data-field="provider"]', function () {
                    const ewallet = ewallets.includes(this.value);
                    const row = $(this).closest('.b2b-account-row');
                    row.find('[data-number-label]').text(ewallet ? 'Nomor HP' : 'Nomor rekening');
                    row.find('[data-field="account_number"]').attr('placeholder', ewallet ? '081234567890' : '1234567890');
                });
                refreshAccounts();

                form.on('submit', function (event) {
                    event.preventDefault();
                    refreshAccounts();
                    B2b.submit(this);
                });
            });
        </script>
    @endpush
@endsection
