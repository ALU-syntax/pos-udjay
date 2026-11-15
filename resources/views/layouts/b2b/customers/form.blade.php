@extends('layouts.app')

@php
    $editing = $customer->exists;
    $contactRow = function ($contact = null) {
        return view('layouts.b2b.customers.partials.contact-row', ['contact' => $contact])->render();
    };
    $addressRow = function ($address = null) {
        return view('layouts.b2b.customers.partials.address-row', ['address' => $address])->render();
    };
@endphp

@section('content')
    @include('layouts.b2b.partials.assets')

    <div class="main-content ob-page">
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow"><a href="{{ route('b2b/customers') }}">Customer B2B</a></div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-building"></i></span>
                    {{ $editing ? 'Edit '.$customer->name : 'Tambah Customer' }}
                </h1>
                <p class="ob-header__desc">
                    @if ($editing)
                        Kode {{ $customer->code }}. Perubahan alamat tidak mengubah pesanan dan invoice yang sudah dibuat.
                    @else
                        Kode customer dibuat otomatis setelah disimpan.
                    @endif
                </p>
            </div>
        </div>

        <form id="customer-form" action="{{ $editing ? route('b2b/customers/update', $customer->id) : route('b2b/customers/store') }}" method="POST" novalidate>
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <div class="ob-card mb-3">
                <h2 class="ob-section-title"><i class="fas fa-id-card" aria-hidden="true"></i> Data Customer</h2>
                <div class="row">
                    <div class="col-12 mb-3 b2b-field" data-error-for="type">
                        <span class="form-label d-block">Jenis customer <span class="text-danger">*</span></span>
                        <div class="b2b-segment b2b-segment--3" role="radiogroup" aria-label="Jenis customer">
                            @foreach (\App\Models\B2b\Customer::TYPES as $value => $label)
                                <label><input type="radio" name="type" value="{{ $value }}" @checked(old('type', $customer->type) === $value)> <span>{{ $label }}</span></label>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-md-6 mb-3 b2b-field">
                        <label for="name" class="form-label"><span id="name-label">Nama</span> <span class="text-danger">*</span></label>
                        <input id="name" name="name" class="form-control" value="{{ old('name', $customer->name) }}" maxlength="255" required placeholder="Tulis lengkap, contoh: PT Sumber Rejeki">
                    </div>
                    <div class="col-md-6 mb-3 b2b-field">
                        <label for="npwp" class="form-label">NPWP <span class="text-muted fw-normal">(opsional)</span></label>
                        <input id="npwp" name="npwp" class="form-control" value="{{ old('npwp', $customer->npwp) }}" maxlength="30" inputmode="numeric">
                    </div>
                    <div class="col-md-6 mb-3 b2b-field">
                        <label for="phone" class="form-label">Telepon kantor</label>
                        <input id="phone" name="phone" class="form-control" value="{{ old('phone', $customer->phone) }}" maxlength="30" inputmode="tel">
                    </div>
                    <div class="col-md-6 mb-3 b2b-field">
                        <label for="email" class="form-label">Email kantor</label>
                        <input id="email" name="email" type="email" class="form-control" value="{{ old('email', $customer->email) }}" maxlength="255">
                    </div>
                    <div class="col-12 mb-3 b2b-field">
                        <label for="notes" class="form-label">Catatan</label>
                        <textarea id="notes" name="notes" rows="2" class="form-control" maxlength="2000">{{ old('notes', $customer->notes) }}</textarea>
                    </div>
                    <div class="col-12 mb-2">
                        <input type="hidden" name="is_active" value="0">
                        <label class="b2b-flag"><input type="checkbox" name="is_active" value="1" @checked($customer->is_active ?? true)> Customer aktif</label>
                    </div>
                </div>
            </div>

            <div class="ob-card mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="ob-section-title mb-0"><i class="fas fa-user-tie" aria-hidden="true"></i> Kontak (PIC)</h2>
                    <button type="button" class="btn ob-btn ob-btn-outline btn-sm" data-add-row="contacts"><i class="fas fa-plus" aria-hidden="true"></i> Tambah kontak</button>
                </div>
                <div class="b2b-repeater" id="contacts" data-prefix="contacts">
                    @foreach ($customer->contacts ?? [] as $contact)
                        {!! $contactRow($contact) !!}
                    @endforeach
                </div>
                <div class="b2b-repeater-empty" data-empty-for="contacts">Belum ada kontak. Tambahkan kontak untuk menerima reminder jatuh tempo.</div>
            </div>

            @php
                $reminderChannels = old('reminder_channels', $customer->reminder_channels ?? []);
                $reminderDays = array_map('intval', old('reminder_days', $customer->reminder_days ?? []));
            @endphp
            <div class="ob-card mb-3">
                <h2 class="ob-section-title"><i class="fas fa-bell" aria-hidden="true"></i> Reminder Jatuh Tempo</h2>
                <input type="hidden" name="reminder_enabled" value="0">
                <label class="b2b-flag mb-1"><input type="checkbox" id="reminder_enabled" name="reminder_enabled" value="1" @checked($customer->reminder_enabled)> Kirim reminder jatuh tempo ke customer ini</label>
                <small class="text-muted d-block">Kalau tidak dicentang, tidak ada reminder yang dikirim. Pengiriman otomatis aktif setelah fitur reminder dibangun.</small>

                <div id="reminder-settings" class="mt-3">
                    <div class="mb-3 b2b-field" data-error-for="reminder_channels">
                        <span class="form-label fw-bold d-block">Channel</span>
                        @foreach (\App\Models\B2b\Customer::REMINDER_CHANNELS as $value => $label)
                            <label class="b2b-flag"><input type="checkbox" name="reminder_channels[]" value="{{ $value }}" @checked(in_array($value, $reminderChannels, true))> {{ $label }}</label>
                        @endforeach
                        <small class="text-muted d-block">WhatsApp terkirim setelah provider WhatsApp dipilih.</small>
                    </div>
                    <div class="mb-3 b2b-field" data-error-for="reminder_days">
                        <span class="form-label fw-bold d-block">Jadwal</span>
                        @foreach (\App\Models\B2b\Customer::REMINDER_DAYS as $value => $label)
                            <label class="b2b-flag"><input type="checkbox" name="reminder_days[]" value="{{ $value }}" @checked(in_array($value, $reminderDays, true))> {{ $label }}</label>
                        @endforeach
                        <input type="hidden" name="reminder_overdue_daily" value="0">
                        <label class="b2b-flag"><input type="checkbox" name="reminder_overdue_daily" value="1" @checked($customer->reminder_overdue_daily)> Setiap hari setelah lewat jatuh tempo</label>
                        <small class="text-muted d-block">H-3 = 3 hari sebelum jatuh tempo. Hari H = tanggal jatuh tempo.</small>
                    </div>
                    <div class="b2b-field" data-error-for="reminder_recipients">
                        <span class="form-label fw-bold d-block">Penerima</span>
                        <small class="text-muted d-block">Kontak yang dicentang "Terima reminder" di bagian Kontak. Email dikirim ke kontak yang punya email, WhatsApp ke kontak yang punya nomor.</small>
                    </div>
                </div>
            </div>

            <div class="ob-card mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="ob-section-title mb-0"><i class="fas fa-map-marker-alt" aria-hidden="true"></i> Alamat</h2>
                    <button type="button" class="btn ob-btn ob-btn-outline btn-sm" data-add-row="addresses"><i class="fas fa-plus" aria-hidden="true"></i> Tambah alamat</button>
                </div>
                <div class="b2b-repeater" id="addresses" data-prefix="addresses">
                    @foreach ($customer->addresses ?? [] as $address)
                        {!! $addressRow($address) !!}
                    @endforeach
                </div>
                <div class="b2b-repeater-empty" data-empty-for="addresses">Belum ada alamat. Alamat penagihan dicetak di invoice, alamat pengiriman dipilih di kasir.</div>
            </div>

            <div class="b2b-form-footer">
                <a href="{{ $editing ? route('b2b/customers/show', $customer->id) : route('b2b/customers') }}" class="btn btn-light">Batal</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1" aria-hidden="true"></i> Simpan</button>
            </div>
        </form>
    </div>

    <template id="template-contacts">{!! $contactRow() !!}</template>
    <template id="template-addresses">{!! $addressRow() !!}</template>

    @push('js')
        <script>
            $(function () {
                const form = $('#customer-form');

                function refresh(prefix) {
                    const container = $('#' + prefix);
                    $(`[data-empty-for="${prefix}"]`).toggleClass('d-none', container.children().length > 0);
                    B2b.reindex(container, prefix);
                }

                $('[data-add-row]').on('click', function () {
                    const prefix = this.dataset.addRow;
                    $('#' + prefix).append($('#template-' + prefix).html());
                    refresh(prefix);
                    $('#' + prefix).children().last().find('input').first().trigger('focus');
                });

                form.on('click', '[data-remove-row]', function () {
                    const prefix = $(this).closest('.b2b-repeater').data('prefix');
                    $(this).closest('.b2b-repeater-row').remove();
                    refresh(prefix);
                });

                // Penanda tunggal (kontak utama, alamat default): mencentang satu baris melepas baris lain.
                form.on('change', '[data-single-flag]', function () {
                    if (!this.checked) return;
                    const flag = this.dataset.singleFlag;
                    $(this).closest('.b2b-repeater').find(`[data-single-flag="${flag}"]`).not(this).prop('checked', false);
                });

                // Label & contoh nama mengikuti jenis customer. Nama tetap ditulis lengkap (tidak ditambah PT/CV otomatis).
                const nameHints = {
                    individual: ['Nama lengkap', 'Contoh: Budi Santoso'],
                    pt: ['Nama perusahaan', 'Tulis lengkap, contoh: PT Sumber Rejeki'],
                    cv: ['Nama perusahaan', 'Tulis lengkap, contoh: CV Maju Bersama'],
                };
                function updateNameHint() {
                    const hint = nameHints[$('input[name="type"]:checked').val()] || ['Nama', 'Tulis lengkap, contoh: PT Sumber Rejeki'];
                    $('#name-label').text(hint[0]);
                    $('#name').attr('placeholder', hint[1]);
                }
                $('input[name="type"]').on('change', updateNameHint);
                updateNameHint();

                function toggleReminder() {
                    $('#reminder-settings').toggleClass('d-none', !$('#reminder_enabled').is(':checked'));
                }
                $('#reminder_enabled').on('change', toggleReminder);
                toggleReminder();

                form.on('submit', function (event) {
                    event.preventDefault();
                    refresh('contacts');
                    refresh('addresses');
                    B2b.submit(this);
                });

                refresh('contacts');
                refresh('addresses');
            });
        </script>
    @endpush
@endsection
