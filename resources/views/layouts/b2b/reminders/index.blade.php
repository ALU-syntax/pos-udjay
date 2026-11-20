@extends('layouts.app')

@php
    $number = fn ($value) => number_format((int) $value, 0, ',', '.');
    $statCards = [
        'sent' => ['Terkirim', 'fa-paper-plane', 'success'],
        'failed' => ['Gagal', 'fa-exclamation-triangle', 'danger'],
        'skipped' => ['Dilewati', 'fa-forward', 'warning'],
        'queued' => ['Masih antre', 'fa-hourglass-half', 'primary'],
    ];
@endphp

@section('content')
    @include('layouts.b2b.partials.assets')
    @include('layouts.b2b.partials.page-styles')
    @include('layouts.b2b.partials.list-filters')

    <div class="main-content ob-page">
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">Kasir B2B</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-bell"></i></span>
                    Reminder Tagihan
                </h1>
                <p class="ob-header__desc">Pantau reminder tagihan yang dikirim ke customer. Reminder otomatis dikirim mulai {{ $sendAt }} WIB sesuai jadwal di data customer; kiriman yang gagal dicoba ulang sampai {{ $retryUntil }} WIB.</p>
            </div>
        </div>

        <div class="ob-stats">
            @foreach ($statCards as $status => [$label, $icon, $variant])
                <div class="ob-card ob-stat">
                    <span class="ob-stat__icon ob-stat__icon--{{ $variant }}" aria-hidden="true"><i class="fas {{ $icon }}"></i></span>
                    <div class="min-w-0">
                        <p class="ob-stat__label">{{ $label }}</p>
                        <p class="ob-stat__value {{ $status === 'failed' && $stats[$status] ? 'text-danger' : '' }}">{{ $number($stats[$status]) }}</p>
                        <p class="b2b-stat__sub">kiriman hari ini</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="ob-card b2b-switch-card">
            <div class="b2b-card__head">
                <div>
                    <h2 class="b2b-card__title"><i class="fas fa-power-off" aria-hidden="true"></i> Saklar darurat</h2>
                    <p class="b2b-card__desc">Hentikan sementara semua kiriman satu channel, misalnya saat nomor WhatsApp bermasalah. Jadwal di data customer tidak berubah.</p>
                </div>
            </div>
            <div class="b2b-switches">
                @foreach ($switches as $channel => $switch)
                    <div class="b2b-switch {{ $switch['enabled'] ? '' : 'is-off' }}">
                        <span class="b2b-switch__icon" aria-hidden="true"><i class="{{ $switch['icon'] }}"></i></span>
                        <div class="min-w-0">
                            <p class="b2b-switch__name">
                                {{ $switch['label'] }}
                                <span class="badge badge-{{ $switch['enabled'] ? 'success' : 'danger' }}">{{ $switch['enabled'] ? 'Aktif' : 'Dihentikan' }}</span>
                            </p>
                            <p class="b2b-switch__note">
                                @if (! $switch['supported'])
                                    Pengirim {{ $switch['label'] }} belum dipasang; belum ada kiriman lewat channel ini.
                                @elseif ($switch['enabled'])
                                    Reminder {{ $switch['label'] }} dikirim sesuai jadwal.
                                @else
                                    Tidak ada reminder {{ $switch['label'] }} yang dikirim, termasuk kiriman yang sudah antre.
                                @endif
                            </p>
                        </div>
                        @can('toggle b2b/reminders')
                            @if ($switch['enabled'])
                                <a href="{{ route('b2b/reminders/toggle', [$channel, 'off']) }}" class="btn ob-btn ob-btn-outline ob-btn-sm b2b-btn-danger-outline"
                                    data-b2b-confirm data-reason="Alasan menghentikan" data-title="Hentikan kiriman {{ $switch['label'] }}?"
                                    data-text="Semua reminder {{ $switch['label'] }} berhenti, termasuk yang sudah antre, sampai diaktifkan lagi." data-confirm-text="Ya, hentikan">
                                    <i class="fas fa-pause" aria-hidden="true"></i> Hentikan</a>
                            @else
                                <a href="{{ route('b2b/reminders/toggle', [$channel, 'on']) }}" class="btn ob-btn ob-btn-primary ob-btn-sm"
                                    data-b2b-confirm data-icon="question" data-title="Aktifkan lagi kiriman {{ $switch['label'] }}?"
                                    data-text="Reminder berikutnya dikirim sesuai jadwal. Kiriman yang terlewat selama dihentikan tidak dikirim otomatis." data-confirm-text="Ya, aktifkan">
                                    <i class="fas fa-play" aria-hidden="true"></i> Aktifkan</a>
                            @endif
                        @endcan
                    </div>
                @endforeach
            </div>
        </div>

        <nav class="nav ob-tabs b2b-reminder-tabs" aria-label="Bagian menu Reminder">
            <a href="{{ route('b2b/reminders') }}" class="nav-link {{ $tab === 'activity' ? 'active' : '' }}" @if ($tab === 'activity') aria-current="page" @endif>
                <i class="fas fa-stream" aria-hidden="true"></i> Aktivitas</a>
            <a href="{{ route('b2b/reminders', ['tab' => 'customers']) }}" class="nav-link {{ $tab === 'customers' ? 'active' : '' }}" @if ($tab === 'customers') aria-current="page" @endif>
                <i class="fas fa-building" aria-hidden="true"></i> Customer</a>
        </nav>

        @if ($tab === 'customers')
            @include('layouts.b2b.reminders.partials.customers')
        @else
        <div class="ob-card">
            <div class="b2b-card__head">
                <div>
                    <h2 class="b2b-card__title"><i class="fas fa-stream" aria-hidden="true"></i> Aktivitas kiriman</h2>
                    <p class="b2b-card__desc">Satu baris per kiriman: satu email gabungan per customer per hari. Klik <i class="fas fa-eye" aria-hidden="true"></i> untuk melihat isi email dan lampirannya.</p>
                </div>
                <div class="b2b-actions">
                    <button type="button" class="btn ob-btn ob-btn-outline ob-btn-sm" id="filter-reset" hidden>
                        <i class="fas fa-undo" aria-hidden="true"></i> Reset filter
                    </button>
                </div>
            </div>

            <div class="b2b-filter-row b2b-filter-row--5">
                <div class="ob-field b2b-form">
                    <label for="date-filter"><i class="far fa-calendar" aria-hidden="true"></i> Tanggal</label>
                    <div class="b2b-range">
                        <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                        <input id="date-filter" type="text" class="form-control" readonly placeholder="Semua tanggal" autocomplete="off">
                        <button type="button" class="b2b-range__clear" id="date-clear" aria-label="Hapus filter tanggal" hidden><i class="fas fa-times" aria-hidden="true"></i></button>
                    </div>
                    <input type="hidden" id="date-from">
                    <input type="hidden" id="date-to">
                </div>
                <div class="ob-field">
                    <label for="status-filter"><i class="fas fa-filter" aria-hidden="true"></i> Status</label>
                    <select id="status-filter" class="form-select">
                        <option value="">Semua status</option>
                        @foreach (\App\Models\B2b\ReminderLog::STATUS_LABELS as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ob-field">
                    <label for="channel-filter"><i class="fas fa-paper-plane" aria-hidden="true"></i> Channel</label>
                    <select id="channel-filter" class="form-select">
                        <option value="">Semua channel</option>
                        @foreach (\App\Models\B2b\ReminderLog::CHANNEL_LABELS as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ob-field">
                    <label for="trigger-filter"><i class="fas fa-bolt" aria-hidden="true"></i> Pemicu</label>
                    <select id="trigger-filter" class="form-select">
                        <option value="">Otomatis & manual</option>
                        @foreach (\App\Models\B2b\ReminderLog::TRIGGER_LABELS as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="ob-field">
                    <label for="customer-filter"><i class="fas fa-building" aria-hidden="true"></i> Customer</label>
                    <select id="customer-filter" class="form-select" data-select2-url="{{ route('b2b/selectors/customers') }}" data-placeholder="Semua customer">
                        <option value=""></option>
                    </select>
                </div>
            </div>

            <div class="ob-table-wrap">
                {!! $dataTable->table(['class' => 'table w-100 b2b-list-table']) !!}
            </div>
        </div>
        @endif
    </div>

    {{-- Detail kiriman (log email). Dipindah ke <body> saat halaman dimuat. --}}
    <div class="modal fade b2b-modal" id="reminder-detail" tabindex="-1" aria-labelledby="reminder-detail-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content ob-page">
                <div class="modal-header">
                    <div>
                        <h2 class="b2b-modal__title" id="reminder-detail-title">Detail kiriman</h2>
                        <p class="b2b-modal__sub" data-detail="headline"></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="b2b-detail-loading text-center py-4" data-detail-loading><span class="spinner-border spinner-border-sm me-2"></span>Memuat…</div>
                    <div data-detail-content hidden>
                        <dl class="ob-meta">
                            <div class="ob-meta__item"><dt>Status</dt><dd data-detail="status"></dd></div>
                            <div class="ob-meta__item"><dt>Tujuan</dt><dd data-detail="recipient"></dd></div>
                            <div class="ob-meta__item"><dt>Pemicu</dt><dd data-detail="trigger"></dd></div>
                            <div class="ob-meta__item"><dt>Percobaan</dt><dd data-detail="attempts"></dd></div>
                            <div class="ob-meta__item"><dt>Dibuat</dt><dd data-detail="created_at"></dd></div>
                            <div class="ob-meta__item"><dt>Terkirim</dt><dd data-detail="sent_at"></dd></div>
                            <div class="ob-meta__item ob-meta__item--wide" data-detail-error hidden><dt>Error</dt><dd class="text-danger" data-detail="error"></dd></div>
                            <div class="ob-meta__item ob-meta__item--wide"><dt>Subjek</dt><dd data-detail="subject"></dd></div>
                        </dl>

                        <p class="b2b-subtitle mt-3">Invoice di kiriman ini</p>
                        <ul class="b2b-list b2b-payments" data-detail="invoices"></ul>

                        <div data-detail-attachments hidden>
                            <p class="b2b-subtitle">Lampiran</p>
                            <ul class="b2b-attachments" data-detail="attachments"></ul>
                        </div>

                        <p class="b2b-subtitle">Isi pesan</p>
                        <p class="b2b-muted" data-detail-nobody hidden>Isi pesan belum tersimpan (kiriman belum diproses atau dilewati sebelum dikirim).</p>
                        {{-- sandbox tanpa izin apa pun: isi email tidak bisa menjalankan script atau membuka link di halaman ini --}}
                        <iframe class="b2b-mail-preview" title="Pratinjau isi email" sandbox="" data-detail-body></iframe>
                        {{-- Pesan WhatsApp berupa teks biasa --}}
                        <pre class="b2b-wa-preview" data-detail-text hidden></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('css')
        <style>
            .ob-page .ob-stat__value { font-size: 1.15rem; }
            .b2b-switch-card { margin-bottom: 1.25rem; }
            .b2b-reminder-tabs { margin-bottom: 1rem; }
            .b2b-switches { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; padding: 0 1.25rem 1.25rem; }
            @media (max-width: 767.98px) { .b2b-switches { grid-template-columns: minmax(0, 1fr); } }
            .b2b-switch { display: flex; align-items: center; gap: .85rem; padding: .85rem 1rem; border: 1px solid var(--ob-border); border-radius: var(--ob-radius-sm); background: var(--ob-surface-2); }
            .b2b-switch.is-off { border-color: var(--ob-danger); background: #fef2f2; }
            .b2b-switch > .min-w-0 { flex: 1; }
            .b2b-switch__icon { display: inline-grid; place-items: center; flex-shrink: 0; width: 40px; height: 40px; border-radius: 10px; background: var(--ob-surface); color: var(--ob-text-2); font-size: 1.1rem; }
            .b2b-switch__name { display: flex; align-items: center; gap: .5rem; margin: 0; font-weight: 700; color: var(--ob-text); }
            .b2b-switch__note { margin: .15rem 0 0; font-size: .78rem; color: var(--ob-muted); }
            .b2b-switch .ob-btn { flex-shrink: 0; min-height: 40px; }
            .b2b-btn-danger-outline { color: var(--ob-danger) !important; }
            .b2b-due { font-size: .74rem; font-weight: 600; }
            .b2b-due--late { color: var(--ob-danger); }
            .b2b-due--soon { color: #b45309; }
            .b2b-reminder-error { max-width: 260px; color: var(--ob-danger); white-space: normal; }
            .b2b-attachments { display: flex; flex-wrap: wrap; gap: .4rem; margin: 0 0 1rem; padding: 0; list-style: none; }
            .b2b-attachments li { display: inline-flex; align-items: center; gap: .35rem; padding: .3rem .65rem; border: 1px solid var(--ob-border); border-radius: 999px; font-size: .78rem; }
            .b2b-mail-preview { width: 100%; height: 520px; border: 1px solid var(--ob-border); border-radius: var(--ob-radius-sm); background: #f3f4f6; }
            .b2b-wa-preview { margin: 0; padding: 1rem; border: 1px solid var(--ob-border); border-radius: var(--ob-radius-sm); background: #e7f8ee; font: .85rem/1.5 inherit; white-space: pre-wrap; word-break: break-word; color: var(--ob-text); }
        </style>
    @endpush

    @push('js')
        @if ($tab === 'activity')
        {!! $dataTable->scripts() !!}
        @endif
        <script>
            $(function () {
                if (document.getElementById('b2b-reminder-table')) {
                    B2b.listFilters({
                        table: 'b2b-reminder-table',
                        selects: '#status-filter, #channel-filter, #trigger-filter, #customer-filter',
                        range: { input: '#date-filter', from: '#date-from', to: '#date-to', clear: '#date-clear', ranges: 'past' },
                    });
                }

                const $modal = $('#reminder-detail').appendTo('body');
                const field = name => $modal.find(`[data-detail="${name}"]`);

                $(document).on('click', '[data-reminder-detail]', function () {
                    $modal.find('[data-detail-loading]').prop('hidden', false);
                    $modal.find('[data-detail-content]').prop('hidden', true);
                    bootstrap.Modal.getOrCreateInstance($modal[0]).show();

                    $.getJSON(this.dataset.reminderDetail).done(({ data }) => {
                        field('headline').text(`${data.customer || '-'} · ${data.channel} · ${data.reminder_date}`);
                        field('status').html(`<span class="badge badge-${data.status_variant}">${B2b.escape(data.status)}</span>`);
                        field('recipient').html(data.recipient ? `<i class="${data.channel_icon} me-1" aria-hidden="true"></i>${B2b.escape(data.recipient)}` : '');
                        field('trigger').text(data.sender ? `${data.trigger} · ${data.sender}` : data.trigger);
                        field('attempts').text(data.attempts);
                        field('created_at').text(data.created_at);
                        field('sent_at').text(data.sent_at || '');
                        field('subject').text(data.subject || '');
                        field('error').text(data.error || '');
                        $modal.find('[data-detail-error]').prop('hidden', !data.error);
                        field('invoices').html(data.invoices.map(invoice => `<li><div class="b2b-payments__row">
                            <a href="${invoice.url}" target="_blank" class="b2b-mono">${B2b.escape(invoice.number)}</a>
                            <span class="b2b-muted">Jatuh tempo ${B2b.escape(invoice.due_date)} · ${B2b.escape(invoice.offset)}</span></div></li>`).join(''));
                        field('attachments').html(data.attachments.map(name => `<li><i class="fas fa-file-pdf text-danger" aria-hidden="true"></i>${B2b.escape(name)}</li>`).join(''));
                        $modal.find('[data-detail-attachments]').prop('hidden', !data.attachments.length);
                        const isText = data.channel_key === 'whatsapp';
                        $modal.find('[data-detail-body]').prop('hidden', !data.body || isText).attr('srcdoc', isText ? '' : (data.body || ''));
                        $modal.find('[data-detail-text]').prop('hidden', !data.body || !isText).text(isText ? (data.body || '') : '');
                        $modal.find('[data-detail-nobody]').prop('hidden', !!data.body);
                        $modal.find('[data-detail-loading]').prop('hidden', true);
                        $modal.find('[data-detail-content]').prop('hidden', false);
                    }).fail(() => {
                        bootstrap.Modal.getOrCreateInstance($modal[0]).hide();
                        showToast('error', 'Gagal memuat detail kiriman.');
                    });
                });
            });
        </script>
    @endpush
@endsection
