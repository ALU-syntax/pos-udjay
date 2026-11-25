{{--
    Dialog "Kirim ke customer": pilih jenis (Invoice / Reminder untuk tagihan tempo terbuka; Kwitansi untuk invoice lunas)
    lalu channel (Email / WhatsApp). Tombol: <button data-send-reminder ...> dari App\Support\B2b\ReminderButton::attributes().
    Include sekali di halaman yang punya tombolnya (setelah partials.assets).
--}}
@once
    @push('css')
        <style>
            .b2b-send-channels { display: flex; flex-direction: column; gap: .5rem; margin: .5rem 0 0; text-align: left; }
            .b2b-send-channel { display: flex; align-items: flex-start; gap: .65rem; padding: .7rem .85rem; border: 1px solid #d1d5db; border-radius: 10px; cursor: pointer; }
            .b2b-send-channel:has(input:checked) { border-color: #1572e8; background: #eef5ff; }
            .b2b-send-channel.is-blocked { cursor: not-allowed; opacity: .6; }
            .b2b-send-channel input { width: 1.1rem; height: 1.1rem; margin-top: .15rem; }
            .b2b-send-channel strong { display: block; font-size: .92rem; color: #111827; }
            .b2b-send-channel small { display: block; font-size: .78rem; color: #6b7280; }
            .b2b-send-channel small.is-blocked { color: #b91c1c; }
            .b2b-send-label { margin: .9rem 0 0; font-size: .82rem; font-weight: 700; color: #374151; text-align: left; }
        </style>
    @endpush
    @push('js')
        <script>
            $(document).on('click', '[data-send-reminder]', function (event) {
                event.preventDefault();
                const el = this.dataset;
                const channels = JSON.parse(el.channels || '[]');
                const kinds = JSON.parse(el.kinds || '[]');
                if (!kinds.length) return;
                // Reminder: channel reminder customer; invoice/kwitansi: semua channel yang bisa dipakai.
                const defaultChecked = (channel, kind) => !channel.blocked && (kind === 'reminder' ? channel.reminder : true);
                const kindOptions = kinds.length > 1 ? kinds.map((kind, index) => `
                    <label class="b2b-send-channel">
                        <input type="radio" name="b2b-send-kind" value="${kind.value}" ${index === 0 ? 'checked' : ''}>
                        <span><strong>${B2b.escape(kind.label)}</strong><small>${B2b.escape(kind.hint)}</small></span>
                    </label>`).join('') : '';
                const options = channels.map(channel => `
                    <label class="b2b-send-channel ${channel.blocked ? 'is-blocked' : ''}">
                        <input type="checkbox" name="b2b-send-channel" value="${channel.value}" ${defaultChecked(channel, kinds[0].value) ? 'checked' : ''} ${channel.blocked ? 'disabled' : ''}>
                        <span>
                            <strong>${B2b.escape(channel.label)}</strong>
                            ${channel.blocked
                                ? `<small class="is-blocked">${B2b.escape(channel.blocked)}</small>`
                                : `<small>${B2b.escape(channel.contact)}</small>`}
                        </span>
                    </label>`).join('');

                const intro = kinds.length > 1
                    ? `<p class="mb-1">Invoice <strong>${B2b.escape(el.number)}</strong></p><p class="b2b-send-label">Jenis kiriman</p><div class="b2b-send-channels">${kindOptions}</div><p class="b2b-send-label">Kirim lewat</p>`
                    : `<p class="mb-1">${B2b.escape(kinds[0].hint)} untuk invoice <strong>${B2b.escape(el.number)}</strong> dikirim lewat:</p>`;

                Swal.fire({
                    title: kinds.length > 1 ? 'Kirim ke customer?' : `Kirim ${kinds[0].label.toLowerCase()}?`,
                    html: `${intro}<div class="b2b-send-channels">${options}</div>`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, kirim',
                    cancelButtonText: 'Batal',
                    didOpen: popup => {
                        // Ganti jenis: centang channel kembali ke default jenis tersebut.
                        $(popup).on('change', 'input[name="b2b-send-kind"]', event => {
                            $(popup).find('input[name="b2b-send-channel"]').each((i, input) => {
                                input.checked = defaultChecked(channels.find(channel => channel.value === input.value), event.target.value);
                            });
                        });
                    },
                    preConfirm: () => {
                        const container = $(Swal.getHtmlContainer());
                        const picked = container.find('input[name="b2b-send-channel"]:checked').map((i, input) => input.value).get();
                        if (!picked.length) Swal.showValidationMessage('Pilih minimal satu channel yang bisa dipakai.');
                        return { kind: container.find('input[name="b2b-send-kind"]:checked').val() || kinds[0].value, channels: picked };
                    },
                }).then(result => {
                    if (!result.isConfirmed) return;
                    $.post(el.url, { _token: @json(csrf_token()), kind: result.value.kind, channels: result.value.channels })
                        .done(response => {
                            showToast('success', response.message || 'Kiriman masuk antrean');
                            if (el.table && window.LaravelDataTables?.[el.table]) window.LaravelDataTables[el.table].ajax.reload(null, false);
                            else window.location.reload();
                        })
                        .fail(xhr => {
                            const errors = xhr.responseJSON?.errors;
                            showToast('error', errors ? Object.values(errors)[0][0] : (xhr.responseJSON?.message || 'Gagal mengirim'));
                        });
                });
            });
        </script>
    @endpush
@endonce
