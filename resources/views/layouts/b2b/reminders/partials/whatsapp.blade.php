{{--
    Tab WhatsApp menu Reminder: status nomor penagihan di gateway Baileys, menautkan (QR / kode pairing), memutuskan, dan pesan uji.
    Status diambil lewat AJAX supaya halaman tetap cepat walau gateway lambat atau mati. Butuh $whatsappConfigured.
--}}
@php $canManage = auth()->user()->can('manage-whatsapp b2b/reminders'); @endphp

@if (! $whatsappConfigured)
    <div class="ob-card">
        <div class="b2b-card__body">
            <div class="b2b-empty">
                <span class="b2b-empty__icon" aria-hidden="true"><i class="fab fa-whatsapp"></i></span>
                <p class="b2b-empty__text"><strong>Gateway WhatsApp belum dikonfigurasi.</strong></p>
                <p class="b2b-muted mb-0">Isi <code>B2B_WA_GATEWAY_URL</code> dan <code>B2B_WA_GATEWAY_KEY</code> di <code>.env</code> server (lihat <code>.env.example</code>), lalu muat ulang halaman ini. Selama belum diisi, reminder hanya dikirim lewat email.</p>
            </div>
        </div>
    </div>
@else
    <div class="b2b-wa-grid" id="wa-panel"
        data-status-url="{{ route('b2b/reminders/whatsapp/status') }}"
        data-connect-url="{{ route('b2b/reminders/whatsapp/connect') }}"
        data-qr-url="{{ route('b2b/reminders/whatsapp/qr') }}"
        data-pairing-url="{{ route('b2b/reminders/whatsapp/pairing-code') }}"
        data-logout-url="{{ route('b2b/reminders/whatsapp/logout') }}"
        data-test-url="{{ route('b2b/reminders/whatsapp/test') }}"
        data-can-manage="{{ $canManage ? '1' : '0' }}">

        <div class="ob-card">
            <div class="b2b-card__head b2b-card__head--nowrap">
                <div>
                    <h2 class="b2b-card__title"><i class="fab fa-whatsapp" aria-hidden="true"></i> Nomor penagihan</h2>
                    <p class="b2b-card__desc">Nomor WhatsApp yang dipakai gateway untuk mengirim reminder. Status diperbarui otomatis.</p>
                </div>
                <button type="button" class="ob-icon-btn ob-icon-btn--sm" id="wa-refresh" title="Muat ulang status" aria-label="Muat ulang status"><i class="fas fa-sync-alt" aria-hidden="true"></i></button>
            </div>
            <div class="b2b-card__body">
                <div class="b2b-wa-status" id="wa-status" aria-live="polite">
                    <span class="b2b-wa-status__dot" aria-hidden="true"></span>
                    <div class="min-w-0">
                        <p class="b2b-wa-status__state" data-wa="state">Memuat status…</p>
                        <p class="b2b-wa-status__meta" data-wa="meta"></p>
                    </div>
                </div>
                <div class="b2b-wa-alert" data-wa="error" hidden></div>

                @if ($canManage)
                    <div class="b2b-actions mt-3">
                        <button type="button" class="btn ob-btn ob-btn-primary" id="wa-connect" hidden><i class="fas fa-link" aria-hidden="true"></i> Hubungkan</button>
                        <button type="button" class="btn ob-btn ob-btn-outline b2b-btn-danger-outline" id="wa-logout" hidden><i class="fas fa-unlink" aria-hidden="true"></i> Putuskan</button>
                    </div>
                @else
                    <p class="b2b-muted mt-3 mb-0">Menghubungkan atau memutuskan nomor butuh akses <strong>Kelola WhatsApp</strong>.</p>
                @endif
            </div>
        </div>

        @if ($canManage)
            <div class="ob-card" id="wa-pairing" hidden>
                <div class="b2b-card__head">
                    <div>
                        <h2 class="b2b-card__title"><i class="fas fa-qrcode" aria-hidden="true"></i> Tautkan nomor</h2>
                        <p class="b2b-card__desc">Dari HP nomor penagihan: WhatsApp → <strong>Perangkat tertaut</strong> → <strong>Tautkan perangkat</strong>, lalu scan QR ini. QR berganti otomatis tiap beberapa detik.</p>
                    </div>
                </div>
                <div class="b2b-card__body">
                    <div class="b2b-wa-qr">
                        <img id="wa-qr" alt="QR untuk menautkan WhatsApp" hidden>
                        <span class="b2b-muted" id="wa-qr-loading"><span class="spinner-border spinner-border-sm me-2"></span>Menyiapkan QR…</span>
                    </div>

                    <p class="b2b-subtitle mt-4">Atau pakai kode pairing</p>
                    <p class="b2b-muted">Kalau QR sulit di-scan: masukkan nomor penagihan, lalu di HP pilih <strong>Tautkan dengan nomor telepon saja</strong> dan ketik kodenya.</p>
                    <form class="b2b-wa-inline b2b-form" id="wa-pairing-form" novalidate>
                        <div class="b2b-field flex-grow-1">
                            <label for="wa-pairing-phone" class="form-label visually-hidden">Nomor WhatsApp penagihan</label>
                            <input type="tel" id="wa-pairing-phone" name="phone" class="form-control" placeholder="Nomor penagihan, mis. 0812…" autocomplete="off" inputmode="tel">
                        </div>
                        <button type="submit" class="btn ob-btn ob-btn-outline">Minta kode</button>
                    </form>
                    <p class="b2b-wa-code" id="wa-code" hidden></p>
                </div>
            </div>

            <div class="ob-card" id="wa-test" hidden>
                <div class="b2b-card__head">
                    <div>
                        <h2 class="b2b-card__title"><i class="fas fa-paper-plane" aria-hidden="true"></i> Pesan uji</h2>
                        <p class="b2b-card__desc">Kirim pesan teks singkat untuk memastikan nomor penagihan bisa mengirim. Kirim ke nomor sendiri atau rekan, jangan ke customer.</p>
                    </div>
                </div>
                <div class="b2b-card__body">
                    <form class="b2b-wa-inline b2b-form" id="wa-test-form" novalidate>
                        <div class="b2b-field flex-grow-1">
                            <label for="wa-test-phone" class="form-label visually-hidden">Nomor tujuan</label>
                            <input type="tel" id="wa-test-phone" name="phone" class="form-control" placeholder="Nomor tujuan, mis. 0813…" autocomplete="off" inputmode="tel">
                        </div>
                        <button type="submit" class="btn ob-btn ob-btn-primary">Kirim pesan uji</button>
                    </form>
                </div>
            </div>
        @endif
    </div>

    @push('css')
        <style>
            .b2b-wa-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.25rem; align-items: start; }
            .b2b-wa-grid > .ob-card:first-child { grid-row: span 2; }
            @media (max-width: 991.98px) { .b2b-wa-grid { grid-template-columns: minmax(0, 1fr); } .b2b-wa-grid > .ob-card:first-child { grid-row: auto; } }
            .b2b-wa-status { display: flex; align-items: center; gap: .85rem; padding: 1rem; border: 1px solid var(--ob-border); border-radius: var(--ob-radius-sm); background: var(--ob-surface-2); }
            .b2b-wa-status__dot { flex-shrink: 0; width: 14px; height: 14px; border-radius: 50%; background: var(--ob-muted); box-shadow: 0 0 0 4px rgba(100, 116, 139, .15); }
            .b2b-wa-status[data-tone="success"] .b2b-wa-status__dot { background: var(--ob-success); box-shadow: 0 0 0 4px rgba(22, 163, 74, .15); }
            .b2b-wa-status[data-tone="warning"] .b2b-wa-status__dot { background: #f59e0b; box-shadow: 0 0 0 4px rgba(245, 158, 11, .18); }
            .b2b-wa-status[data-tone="danger"] .b2b-wa-status__dot { background: var(--ob-danger); box-shadow: 0 0 0 4px rgba(220, 38, 38, .15); }
            .b2b-wa-status__state { margin: 0; font-weight: 700; color: var(--ob-text); }
            .b2b-wa-status__meta { margin: .15rem 0 0; font-size: .8rem; color: var(--ob-muted); }
            .b2b-wa-alert { margin-top: .75rem; padding: .7rem .9rem; border: 1px solid var(--ob-danger); border-radius: var(--ob-radius-sm); background: #fef2f2; color: var(--ob-danger); font-size: .85rem; }
            .b2b-wa-qr { display: grid; place-items: center; min-height: 260px; padding: 1rem; border: 1px dashed var(--ob-border-strong); border-radius: var(--ob-radius-sm); background: #fff; }
            .b2b-wa-qr img { width: 240px; height: 240px; image-rendering: pixelated; }
            .b2b-wa-inline { display: flex; gap: .5rem; align-items: flex-start; }
            .b2b-wa-inline .ob-btn { min-height: 42px; flex-shrink: 0; }
            .b2b-wa-code { margin: 1rem 0 0; padding: .75rem; border-radius: var(--ob-radius-sm); background: var(--ob-primary-soft); color: var(--ob-primary); font: 700 1.6rem/1.2 ui-monospace, SFMono-Regular, Menlo, monospace; letter-spacing: .25em; text-align: center; }
            .b2b-btn-danger-outline { color: var(--ob-danger) !important; }
            .b2b-card__head--nowrap { flex-wrap: nowrap; align-items: flex-start; }
            .b2b-card__head--nowrap > div { min-width: 0; }
        </style>
    @endpush

    @push('js')
        <script>
            $(function () {
                const $panel = $('#wa-panel');
                const url = name => $panel.data(`${name}-url`);
                const canManage = $panel.data('can-manage') === 1 || $panel.data('can-manage') === '1';
                const csrf = @json(csrf_token());
                const STATES = {
                    idle: ['Belum dihubungkan', 'muted'],
                    connecting: ['Menghubungkan…', 'warning'],
                    qr: ['Menunggu ditautkan dari HP', 'warning'],
                    connected: ['Terhubung', 'success'],
                    disconnected: ['Terputus, mencoba menyambung ulang', 'danger'],
                    logged_out: ['Tidak tertaut', 'muted'],
                };
                let state = null;
                let timer = null;
                let qrTimer = null;

                const number = me => {
                    const digits = String(me?.id || '').split(/[:@]/)[0];
                    return digits ? '+' + digits : '';
                };
                const time = iso => iso ? new Date(iso).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '';

                function render(status) {
                    state = status.state;
                    const [label, tone] = STATES[state] || [state, 'muted'];
                    $('#wa-status').attr('data-tone', tone);
                    $panel.find('[data-wa="state"]').text(label);
                    const meta = [];
                    if (status.me) meta.push(`${number(status.me)}${status.me.name ? ' · ' + status.me.name : ''}`);
                    if (status.last_connected_at) meta.push(`Terakhir terhubung ${time(status.last_connected_at)}`);
                    if (state !== 'connected' && status.last_disconnect?.at) meta.push(`Putus ${time(status.last_disconnect.at)}${status.last_disconnect.code ? ' (kode ' + status.last_disconnect.code + ')' : ''}`);
                    if (status.queue_pending) meta.push(`${status.queue_pending} pesan di antrean`);
                    $panel.find('[data-wa="meta"]').text(meta.join(' · '));
                    $panel.find('[data-wa="error"]').prop('hidden', true);

                    if (canManage) {
                        $('#wa-connect').prop('hidden', !['idle', 'logged_out', 'disconnected'].includes(state));
                        $('#wa-logout').prop('hidden', !['connected', 'qr', 'connecting', 'disconnected'].includes(state));
                        $('#wa-pairing').prop('hidden', state !== 'qr');
                        $('#wa-test').prop('hidden', state !== 'connected');
                        if (state === 'qr') loadQr(); else stopQr();
                    }
                    schedule(['connecting', 'qr', 'disconnected'].includes(state) ? 4000 : 30000);
                }

                function showError(message) {
                    $('#wa-status').attr('data-tone', 'danger');
                    $panel.find('[data-wa="state"]').text('Gateway tidak bisa dipakai');
                    $panel.find('[data-wa="meta"]').text('');
                    $panel.find('[data-wa="error"]').text(message).prop('hidden', false);
                    $('#wa-connect, #wa-logout, #wa-pairing, #wa-test').prop('hidden', true);
                    stopQr();
                    schedule(15000);
                }

                function schedule(ms) {
                    clearTimeout(timer);
                    timer = setTimeout(refresh, ms);
                }

                function refresh() {
                    $.getJSON(url('status'))
                        .done(response => render(response.data))
                        .fail(xhr => showError(xhr.responseJSON?.message || 'Gagal mengambil status gateway.'));
                }

                function loadQr() {
                    if (qrTimer) return;
                    const fetchQr = () => $.getJSON(url('qr')).done(response => {
                        $('#wa-qr').attr('src', response.data.qr).prop('hidden', false);
                        $('#wa-qr-loading').prop('hidden', true);
                    });
                    fetchQr();
                    qrTimer = setInterval(fetchQr, 10000);
                }

                function stopQr() {
                    clearInterval(qrTimer);
                    qrTimer = null;
                    $('#wa-qr').prop('hidden', true).removeAttr('src');
                    $('#wa-qr-loading').prop('hidden', false);
                    $('#wa-code').prop('hidden', true).text('');
                }

                const fail = xhr => {
                    const errors = xhr.responseJSON?.errors;
                    showToast('error', errors ? Object.values(errors)[0][0] : (xhr.responseJSON?.message || 'Gagal memproses'));
                };

                $('#wa-refresh').on('click', refresh);

                $('#wa-connect').on('click', function () {
                    const button = $(this).prop('disabled', true);
                    $.post(url('connect'), { _token: csrf })
                        .done(response => { showToast('success', 'Menyiapkan tautan WhatsApp…'); render(response.data); })
                        .fail(fail)
                        .always(() => button.prop('disabled', false));
                });

                $('#wa-logout').on('click', function () {
                    B2b.confirmPost(url('logout'), {
                        title: 'Putuskan nomor WhatsApp?',
                        text: 'Reminder WhatsApp tidak terkirim sampai nomor ditautkan lagi (scan QR baru). Email tetap berjalan.',
                        confirmText: 'Ya, putuskan',
                        reasonLabel: 'Alasan memutuskan',
                        onDone: refresh,
                    });
                });

                $('#wa-pairing-form').on('submit', function (event) {
                    event.preventDefault();
                    const button = $(this).find('[type="submit"]').prop('disabled', true);
                    $.post(url('pairing'), { _token: csrf, phone: $('#wa-pairing-phone').val() })
                        .done(response => {
                            const code = String(response.data.code || '');
                            $('#wa-code').text(code.length === 8 ? `${code.slice(0, 4)}-${code.slice(4)}` : code).prop('hidden', false);
                        })
                        .fail(fail)
                        .always(() => button.prop('disabled', false));
                });

                $('#wa-test-form').on('submit', function (event) {
                    event.preventDefault();
                    const button = $(this).find('[type="submit"]').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Mengirim…');
                    $.post(url('test'), { _token: csrf, phone: $('#wa-test-phone').val() })
                        .done(response => showToast('success', response.message))
                        .fail(fail)
                        .always(() => button.prop('disabled', false).text('Kirim pesan uji'));
                });

                refresh();
            });
        </script>
    @endpush
@endif
