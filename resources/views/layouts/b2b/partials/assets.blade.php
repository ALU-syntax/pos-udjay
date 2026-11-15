{{-- Gaya & helper bersama modul B2B. Include sekali per halaman setelah @section('content'). --}}
@include('layouts.reports.partials.ui-styles')

@push('css')
    <style>
        .b2b-grid-2 { display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); gap: 1.25rem; align-items: start; }
        @media (max-width: 991.98px) { .b2b-grid-2 { grid-template-columns: minmax(0, 1fr); } }

        .b2b-repeater { display: flex; flex-direction: column; gap: .75rem; }
        .b2b-repeater-row {
            position: relative;
            padding: 1rem 1rem .25rem;
            border: 1px solid var(--ob-border);
            border-radius: var(--ob-radius-sm);
            background: var(--ob-surface-2, #f8fafc);
        }
        .b2b-repeater-row__remove { position: absolute; top: .5rem; right: .5rem; }
        .b2b-repeater-empty {
            padding: 1rem;
            border: 1px dashed var(--ob-border-strong);
            border-radius: var(--ob-radius-sm);
            color: var(--ob-muted);
            text-align: center;
            font-size: .875rem;
        }
        .b2b-flag { display: inline-flex; align-items: center; gap: .4rem; margin-right: 1rem; font-size: .85rem; cursor: pointer; }
        .b2b-flag input { width: 1.05rem; height: 1.05rem; }

        .b2b-actions { display: flex; flex-wrap: wrap; gap: .5rem; }
        .b2b-list { list-style: none; padding: 0; margin: 0; }
        .b2b-list li { padding: .7rem 0; border-top: 1px dashed var(--ob-border); }
        .b2b-list li:first-child { border-top: 0; padding-top: 0; }
        .b2b-muted { color: var(--ob-muted); font-size: .82rem; }
        .b2b-money { font-variant-numeric: tabular-nums; white-space: nowrap; }
        .b2b-table { width: 100%; font-size: .875rem; }
        .b2b-table th { font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: var(--ob-muted); font-weight: 700; padding: .5rem .6rem; border-bottom: 1px solid var(--ob-border); white-space: nowrap; }
        .b2b-table td { padding: .65rem .6rem; border-bottom: 1px dashed var(--ob-border); vertical-align: top; }
        .b2b-table tr:last-child td { border-bottom: 0; }
        .b2b-segment { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; }
        .b2b-segment--3 { grid-template-columns: repeat(3, 1fr); }
        .b2b-segment label { margin: 0; }
        .b2b-segment input { position: absolute; opacity: 0; }
        .b2b-segment span {
            display: flex; align-items: center; justify-content: center; gap: .4rem; min-height: 46px;
            border: 1px solid var(--ob-border-strong); border-radius: var(--ob-radius-sm); font-weight: 600; cursor: pointer;
        }
        .b2b-segment input:checked + span { border-color: var(--ob-primary); background: var(--ob-primary-soft); color: var(--ob-primary); }
        .b2b-segment input:focus-visible + span { outline: 2px solid var(--ob-primary); outline-offset: 2px; }
        .b2b-segment.is-invalid span { border-color: var(--ob-danger); }
        .b2b-form-footer {
            position: sticky; bottom: 0; z-index: 5;
            display: flex; justify-content: flex-end; gap: .5rem;
            padding: .85rem 1rem; margin-top: 1rem;
            border: 1px solid var(--ob-border); border-radius: var(--ob-radius-sm);
            background: var(--ob-surface); box-shadow: 0 -4px 16px rgba(15, 23, 42, .06);
        }
        .b2b-form-footer .btn { min-height: 44px; }
    </style>
@endpush

@push('js')
    <script>
        window.B2b = window.B2b || {
            rupiah(value) {
                return 'Rp ' + Math.round(Number(value) || 0).toLocaleString('id-ID');
            },

            escape(text) {
                return $('<div>').text(text == null ? '' : String(text)).html();
            },

            /** Beri nama input baris repeater sesuai urutan: prefix[0][field], prefix[1][field], ... */
            reindex(container, prefix) {
                $(container).find('.b2b-repeater-row').each(function (index) {
                    $(this).find('[data-field]').each(function () {
                        this.name = `${prefix}[${index}][${this.dataset.field}]`;
                    });
                });
            },

            clearErrors(form) {
                $(form).find('.is-invalid').removeClass('is-invalid');
                $(form).find('.invalid-feedback.b2b-error').remove();
            },

            showErrors(form, errors) {
                let first = null;
                Object.entries(errors || {}).forEach(([key, messages]) => {
                    const name = key.split('.').reduce((acc, part, i) => i === 0 ? part : `${acc}[${part}]`, '');
                    let field = $(form).find(`[name="${name}"]`);
                    if (!field.length) field = $(form).find(`[data-error-for="${key}"]`);
                    if (!field.length) return;
                    field.addClass('is-invalid');
                    const holder = field.closest('.b2b-field').length ? field.closest('.b2b-field') : field.parent();
                    holder.append(`<div class="invalid-feedback b2b-error d-block">${B2b.escape([].concat(messages)[0])}</div>`);
                    first = first || field;
                });
                if (first) first[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            },

            /** Kirim form lewat AJAX (FormData). Sukses → toast lalu redirect ke data.redirect kalau ada. */
            submit(form, { beforeSend, onSuccess } = {}) {
                const $form = $(form);
                const button = $form.find('[type="submit"]');
                const label = button.html();
                beforeSend && beforeSend($form);
                B2b.clearErrors($form);
                button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...');

                return $.ajax({ url: $form.attr('action'), method: 'POST', data: new FormData($form[0]), contentType: false, processData: false })
                    .done(response => {
                        showToast('success', response.message || 'Tersimpan');
                        if (onSuccess) return onSuccess(response);
                        if (response.data && response.data.redirect) window.location.href = response.data.redirect;
                    })
                    .fail(xhr => {
                        B2b.showErrors($form, xhr.responseJSON?.errors);
                        showToast('error', xhr.responseJSON?.message || 'Gagal menyimpan');
                    })
                    .always(() => button.prop('disabled', false).html(label));
            },

            /** Konfirmasi lalu POST. Dengan reasonLabel, user wajib mengisi alasan (dikirim sebagai `reason`). */
            confirmPost(url, { title, text, confirmText = 'Ya, lanjutkan', icon = 'warning', reasonLabel = null, method = 'POST', onDone } = {}) {
                Swal.fire({
                    title, text, icon,
                    input: reasonLabel ? 'text' : undefined,
                    inputLabel: reasonLabel || undefined,
                    inputValidator: reasonLabel ? (value => !value || !value.trim() ? 'Alasan wajib diisi' : undefined) : undefined,
                    showCancelButton: true,
                    confirmButtonText: confirmText,
                    cancelButtonText: 'Batal',
                }).then(result => {
                    if (!result.isConfirmed) return;
                    const data = { _token: @json(csrf_token()) };
                    if (reasonLabel) data.reason = result.value;
                    if (method !== 'POST') data._method = method;
                    $.post(url, data)
                        .done(response => {
                            showToast('success', response.message || 'Berhasil');
                            if (onDone) return onDone(response);
                            if (response.data && response.data.redirect) window.location.href = response.data.redirect;
                            else window.location.reload();
                        })
                        .fail(xhr => {
                            const errors = xhr.responseJSON?.errors;
                            const message = errors ? Object.values(errors)[0][0] : (xhr.responseJSON?.message || 'Gagal memproses');
                            showToast('error', message);
                        });
                });
            },
        };

        $(document).on('click', '[data-b2b-confirm]', function (event) {
            event.preventDefault();
            const el = this.dataset;
            B2b.confirmPost(this.getAttribute('href') || el.url, {
                title: el.title, text: el.text, confirmText: el.confirmText, icon: el.icon || 'warning',
                reasonLabel: el.reason || null, method: el.method || 'POST',
                onDone: el.table ? (() => window.LaravelDataTables[el.table].ajax.reload(null, false)) : undefined,
            });
        });
    </script>
@endpush
