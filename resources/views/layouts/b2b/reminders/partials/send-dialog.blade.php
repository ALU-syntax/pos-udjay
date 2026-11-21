{{--
    Dialog "Kirim reminder" dengan pilihan channel (Email / WhatsApp). Tombol: <button data-send-reminder ...> dari
    App\Support\B2b\ReminderButton::attributes(). Include sekali di halaman yang punya tombolnya (setelah partials.assets).
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
        </style>
    @endpush
    @push('js')
        <script>
            $(document).on('click', '[data-send-reminder]', function (event) {
                event.preventDefault();
                const el = this.dataset;
                const channels = JSON.parse(el.channels || '[]');
                const options = channels.map(channel => `
                    <label class="b2b-send-channel ${channel.blocked ? 'is-blocked' : ''}">
                        <input type="checkbox" name="b2b-send-channel" value="${channel.value}" ${channel.checked && !channel.blocked ? 'checked' : ''} ${channel.blocked ? 'disabled' : ''}>
                        <span>
                            <strong>${B2b.escape(channel.label)}</strong>
                            ${channel.blocked
                                ? `<small class="is-blocked">${B2b.escape(channel.blocked)}</small>`
                                : `<small>${B2b.escape(channel.contact)}</small>`}
                        </span>
                    </label>`).join('');

                Swal.fire({
                    title: 'Kirim reminder sekarang?',
                    html: `<p class="mb-1">Reminder <strong>${B2b.escape(el.number)}</strong> beserta PDF invoice dikirim lewat:</p><div class="b2b-send-channels">${options}</div>`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, kirim',
                    cancelButtonText: 'Batal',
                    preConfirm: () => {
                        const picked = $(Swal.getHtmlContainer()).find('input[name="b2b-send-channel"]:checked').map((i, input) => input.value).get();
                        if (!picked.length) Swal.showValidationMessage('Pilih minimal satu channel yang bisa dipakai.');
                        return picked;
                    },
                }).then(result => {
                    if (!result.isConfirmed) return;
                    $.post(el.url, { _token: @json(csrf_token()), channels: result.value })
                        .done(response => {
                            showToast('success', response.message || 'Reminder dikirim');
                            if (el.table && window.LaravelDataTables?.[el.table]) window.LaravelDataTables[el.table].ajax.reload(null, false);
                            else window.location.reload();
                        })
                        .fail(xhr => {
                            const errors = xhr.responseJSON?.errors;
                            showToast('error', errors ? Object.values(errors)[0][0] : (xhr.responseJSON?.message || 'Gagal mengirim reminder'));
                        });
                });
            });
        </script>
    @endpush
@endonce
