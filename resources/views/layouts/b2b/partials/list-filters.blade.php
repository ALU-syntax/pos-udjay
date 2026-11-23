{{--
    Filter daftar B2B (Pesanan, Invoice): kartu ringkasan sebagai filter cepat, baris filter, rentang tanggal, tombol hapus filter.
    Markup:
      - Kartu: <button class="ob-card ob-stat b2b-stat-filter" data-filter="status" data-value="overdue" aria-pressed="false"> → mengubah #status-filter.
      - Baris: <div class="b2b-filter-row"> berisi .ob-field dengan <select id="...-filter" class="form-select" data-default="...">.
        Select dengan data-select2-url jadi select2 ber-ajax (mis. customer); beri <option value=""></option> kosong.
        Tambah atribut multiple untuk multi-pilih (tanpa option kosong); nilainya dikirim sebagai array (mis. customer_ids[]).
      - Rentang: <div class="ob-field b2b-form"><div class="b2b-range"><i></i><input readonly><button class="b2b-range__clear"></button></div>
        + dua <input type="hidden"> untuk tanggal awal/akhir (Y-m-d).
      - Tombol hapus filter: <button id="filter-reset" hidden>.
    JS: B2b.listFilters({ table: 'id-datatable', selects: '#a-filter, #b-filter', range: { input, from, to, clear, ranges: 'past'|'due' } }).
    Helper yang juga dipakai Laporan B2B: B2b.ajaxSelect(el), B2b.rangePicker($input, { ranges, onApply }), B2b.rangeLabel(start, end).
--}}
@include('layouts.b2b.partials.datepicker')

@once
    @push('css')
        <style>
            /* ui-styles dicetak inline di body (setelah stack css di head), jadi selektor diberi .ob-page supaya menang */
            .ob-page .b2b-stat-filter { width: 100%; border: 1px solid var(--ob-border); text-align: left; cursor: pointer; transition: border-color .15s ease, box-shadow .15s ease; }
            .ob-page .b2b-stat-filter:hover { border-color: var(--ob-border-strong); }
            .ob-page .b2b-stat-filter:focus-visible { outline: 2px solid var(--ob-primary); outline-offset: 2px; }
            .ob-page .b2b-stat-filter[aria-pressed="true"] { border-color: var(--ob-primary); box-shadow: 0 0 0 1px var(--ob-primary), var(--ob-shadow); }

            .b2b-filter-row {
                display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem;
                padding: 1rem 1.25rem; border-bottom: 1px solid var(--ob-border); background: var(--ob-surface-2);
            }
            .b2b-filter-row--5 { grid-template-columns: repeat(5, minmax(0, 1fr)); }
            @media (max-width: 1399.98px) { .b2b-filter-row--5 { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
            @media (max-width: 1199.98px) { .b2b-filter-row, .b2b-filter-row--5 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
            @media (max-width: 575.98px) { .b2b-filter-row { grid-template-columns: minmax(0, 1fr); } }
            .b2b-filter-row .ob-field label { display: block; margin-bottom: .3rem; font-size: .72rem !important; font-weight: 600; color: var(--ob-muted) !important; }
            .b2b-filter-row .form-select {
                min-height: 42px; padding: 0 2.25rem 0 .85rem; border: 1px solid var(--ob-border-strong); border-radius: var(--ob-radius-sm);
                background-color: var(--ob-surface); font-size: .875rem; color: var(--ob-text);
            }
            .b2b-filter-row .form-select.is-set,
            .b2b-form .b2b-range .form-control[readonly].is-set { border-color: var(--ob-primary) !important; background-color: var(--ob-primary-soft) !important; }
            .b2b-filter-row .b2b-range .form-control[readonly] { padding-right: 2.5rem; border-color: var(--ob-border-strong) !important; font-weight: 500; }
            .b2b-filter-row .b2b-range .form-control[readonly]::placeholder { color: var(--ob-muted); font-weight: 500; }
            /* select2 di baris filter (customer), disamakan dengan .form-select */
            .b2b-filter-row .select2-container { width: 100% !important; }
            .b2b-filter-row .select2-container--default .select2-selection--single {
                height: 42px; border: 1px solid var(--ob-border-strong); border-radius: var(--ob-radius-sm); background: var(--ob-surface);
            }
            .b2b-filter-row .select2-container--default .select2-selection--single .select2-selection__rendered { padding-left: .85rem; padding-right: 3.6rem; line-height: 40px; font-size: .875rem; color: var(--ob-text); }
            .b2b-filter-row .select2-container--default .select2-selection--single .select2-selection__placeholder { color: var(--ob-text); }
            .b2b-filter-row .select2-container--default .select2-selection--single .select2-selection__arrow { height: 40px; right: .5rem; }
            .b2b-filter-row .select2-container--default .select2-selection--single .select2-selection__clear {
                position: absolute; top: 50%; right: 2rem; transform: translateY(-50%); display: inline-grid; place-items: center;
                width: 24px; height: 24px; margin: 0; padding: 0; border: 0; border-radius: 6px; background: transparent; color: var(--ob-muted); font-size: 1.05rem; line-height: 1;
            }
            .b2b-filter-row .select2-container--default .select2-selection--single .select2-selection__clear:hover { background: var(--ob-surface); color: var(--ob-danger); }
            .b2b-filter-row .select2-container--default.select2-container--focus .select2-selection--single,
            .b2b-filter-row .select2-container--open .select2-selection--single { border-color: var(--ob-primary); }
            .b2b-filter-row .select2-container.is-set .select2-selection--single { border-color: var(--ob-primary); background: var(--ob-primary-soft); }
            /* select2 multi-pilih (customer di Invoice): chip nama, tinggi minimum sama dengan .form-select */
            .b2b-filter-row .select2-container--default .select2-selection--multiple {
                display: flex; flex-wrap: wrap; align-items: center; gap: 4px;
                min-height: 42px; padding: 4px 2rem 4px .5rem; border: 1px solid var(--ob-border-strong); border-radius: var(--ob-radius-sm); background: var(--ob-surface);
                /* maks ±3 baris chip, sisanya di-scroll supaya tabel tidak terdorong jauh */
                max-height: 6.6rem; overflow-y: auto;
            }
            /* select2 4.1: chip di ul.__rendered, kotak ketik di span.select2-search--inline (saudara ul) */
            .b2b-filter-row .select2-container--default .select2-selection--multiple .select2-selection__rendered { display: contents; }
            .b2b-filter-row .select2-container--default .select2-selection--multiple .select2-selection__choice__display { padding: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .b2b-filter-row .select2-container--default .select2-selection--multiple .select2-selection__choice {
                display: inline-flex; align-items: center; gap: .3rem; max-width: 100%; margin: 0; padding: 2px .5rem 2px .35rem;
                border: 1px solid var(--ob-border-strong); border-radius: 6px; background: var(--ob-surface); font-size: .8rem; color: var(--ob-text);
            }
            .b2b-filter-row .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
                position: static; margin: 0; padding: 0 .15rem; border: 0; background: transparent; color: var(--ob-muted); font-size: .95rem; line-height: 1;
            }
            .b2b-filter-row .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover { color: var(--ob-danger); background: transparent; }
            .b2b-filter-row .select2-container--default .select2-selection--multiple .select2-selection__clear {
                position: absolute; top: 50%; right: .5rem; transform: translateY(-50%); margin: 0; padding: 0; border: 0; background: transparent; color: var(--ob-muted); font-size: 1.05rem;
            }
            .b2b-filter-row .select2-container--default .select2-selection--multiple .select2-search--inline { flex: 1 1 2.5rem; }
            .b2b-filter-row .select2-container--default .select2-selection--multiple .select2-search__field {
                width: 100% !important; height: 32px; margin: 0; padding: 0 0 0 .35rem; font-size: .875rem; font-family: inherit; line-height: 32px; vertical-align: middle; resize: none; overflow: hidden;
            }
            .b2b-filter-row .select2-container--default .select2-selection--multiple .select2-search__field::placeholder { color: var(--ob-text); }
            .b2b-filter-row .select2-container--default.select2-container--focus .select2-selection--multiple { border-color: var(--ob-primary); }
            .b2b-filter-row .select2-container.is-set .select2-selection--multiple { border-color: var(--ob-primary); background: var(--ob-primary-soft); }
            .select2-dropdown.b2b-filter-dropdown { border-color: var(--ob-border-strong); border-radius: var(--ob-radius-sm); overflow: hidden; box-shadow: 0 12px 32px rgba(15, 23, 42, .12); font-size: .85rem; }
            .select2-dropdown.b2b-filter-dropdown .select2-search__field { height: 36px; border-radius: 8px; border-color: var(--ob-border-strong); }
            .select2-dropdown.b2b-filter-dropdown .select2-results__option--highlighted { background: #1572e8; }

            .b2b-range__clear {
                position: absolute; top: 50%; right: .45rem; transform: translateY(-50%); display: inline-grid; place-items: center;
                width: 28px; height: 28px; border: 0; border-radius: 8px; background: transparent; color: var(--ob-muted); font-size: .75rem;
            }
            .b2b-range__clear:hover { background: var(--ob-surface); color: var(--ob-danger); }

            /* Tabel daftar */
            .b2b-list-table td { vertical-align: middle; }
            .b2b-list-table .ob-cell-meta .fas { font-size: .68rem; }
            .b2b-list-table tbody tr { transition: background-color .12s ease; }
            .b2b-list-table tbody tr:hover { background: var(--ob-surface-2); }
        </style>
    @endpush

    @push('js')
        <script>
            window.B2b = window.B2b || {};
            B2b.listFilters = function ({ table, selects, range = null }) {
                const $selects = $(selects);
                const datatable = () => window.LaravelDataTables[table];
                const initial = el => el.dataset.default ?? '';
                // Select multi-pilih (mis. customer di Invoice) dibandingkan sebagai daftar nilai; kosong = tanpa filter.
                const current = el => el.multiple ? ($(el).val() || []).join(',') : (el.value || '');
                const $input = range ? $(range.input) : $();
                const $from = range ? $(range.from) : $();
                const $to = range ? $(range.to) : $();

                // Select pencarian (mis. customer) memakai select2 ber-ajax.
                $selects.filter('[data-select2-url]').each(function () { B2b.ajaxSelect(this); });

                function syncState() {
                    $selects.each(function () {
                        const set = current(this) !== initial(this);
                        $(this).toggleClass('is-set', set).next('.select2-container').toggleClass('is-set', set);
                    });
                    $input.toggleClass('is-set', $from.val() !== '');
                    if (range) $(range.clear).prop('hidden', !$from.val());
                    $('.b2b-stat-filter').each(function () {
                        $(this).attr('aria-pressed', $(`#${this.dataset.filter}-filter`).val() === this.dataset.value);
                    });
                    const dirty = $selects.toArray().some(el => current(el) !== initial(el)) || !!$from.val();
                    $('#filter-reset').prop('hidden', !dirty);
                }
                const reload = () => { syncState(); datatable().ajax.reload(); };

                $selects.on('change', reload);

                // Kartu ringkasan = filter cepat; klik lagi untuk melepas.
                $(document).on('click', '.b2b-stat-filter', function () {
                    const select = $(`#${this.dataset.filter}-filter`);
                    select.val(select.val() === this.dataset.value ? '' : this.dataset.value);
                    reload();
                });

                function setRange(start, end) {
                    $input.val(start ? B2b.rangeLabel(start, end) : '');
                    $from.val(start ? start.format('YYYY-MM-DD') : '');
                    $to.val(start ? end.format('YYYY-MM-DD') : '');
                    reload();
                }

                if (range) B2b.rangePicker($input, { ranges: range.ranges, onApply: setRange });

                if (range) $(range.clear).on('click', () => setRange(null));
                $('#filter-reset').on('click', function () {
                    $selects.each(function () { $(this).val(this.multiple ? [] : initial(this)).trigger('change.select2'); });
                    if (range) setRange(null); else reload();
                });

                // Kartu ringkasan dimuat ulang setelah pelunasan (payment-modal); pasang lagi status tombolnya.
                $(document).on('b2b:stats-refreshed', syncState);

                syncState();
            };

            /** Select2 ber-ajax untuk filter (mis. customer): <select data-select2-url="..." data-placeholder="...">. */
            B2b.ajaxSelect = function (el) {
                $(el).select2({
                    width: '100%',
                    placeholder: el.dataset.placeholder || 'Semua',
                    allowClear: true,
                    dropdownCssClass: 'b2b-filter-dropdown',
                    // Kotak terpilih cukup nama; daftar pencarian tetap menampilkan kode.
                    templateSelection: item => item.name || item.text,
                    language: { searching: () => 'Mencari…', noResults: () => 'Tidak ditemukan', inputTooShort: () => 'Ketik untuk mencari' },
                    ajax: {
                        url: el.dataset.select2Url, dataType: 'json', delay: 250,
                        data: params => ({ q: params.term }),
                        processResults: response => ({ results: response.data || [] }),
                    },
                });
                return $(el);
            };

            B2b.shortMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            B2b.dateLabel = date => `${date.format('DD')} ${B2b.shortMonths[date.month()]} ${date.year()}`;
            B2b.rangeLabel = (start, end) => start.isSame(end, 'day') ? B2b.dateLabel(start) : `${B2b.dateLabel(start)}  –  ${B2b.dateLabel(end)}`;

            /**
             * Date range picker B2B (tema .b2b-drp) dengan preset bahasa Indonesia; dipakai filter daftar dan Laporan B2B.
             * ranges: 'past' untuk tanggal transaksi (tidak bisa ke depan), 'due' untuk jatuh tempo (bisa ke depan),
             * 'report' = 'past' + rentang panjang untuk laporan. opens: arah popup ('left' | 'right').
             * onApply(start, end) menerima moment saat Terapkan atau preset dipilih.
             */
            B2b.rangePicker = function ($input, { ranges = 'past', opens = 'left', onApply }) {
                if (!$.fn.daterangepicker) return null;
                const today = moment().startOf('day');
                const presets = ranges === 'due'
                    ? {
                        'Hari ini': [today.clone(), today.clone()],
                        '7 hari ke depan': [today.clone(), today.clone().add(7, 'days')],
                        'Minggu ini': [today.clone().startOf('isoWeek'), today.clone().endOf('isoWeek')],
                        'Bulan ini': [today.clone().startOf('month'), today.clone().endOf('month')],
                    }
                    : {
                        'Hari ini': [today.clone(), today.clone()],
                        '7 hari terakhir': [today.clone().subtract(6, 'days'), today.clone()],
                        'Bulan ini': [today.clone().startOf('month'), today.clone()],
                        'Bulan lalu': [today.clone().subtract(1, 'month').startOf('month'), today.clone().subtract(1, 'month').endOf('month')],
                    };
                if (ranges === 'report') {
                    presets['3 bulan terakhir'] = [today.clone().subtract(2, 'months').startOf('month'), today.clone()];
                    presets['Tahun ini'] = [today.clone().startOf('year'), today.clone()];
                }
                $input.daterangepicker({
                    autoUpdateInput: false,
                    drops: 'auto',
                    opens,
                    showDropdowns: true,
                    linkedCalendars: false,
                    alwaysShowCalendars: true,
                    maxDate: ranges === 'due' ? undefined : today.clone().endOf('day'),
                    ranges: presets,
                    buttonClasses: 'btn btn-sm',
                    applyButtonClasses: 'btn-primary',
                    cancelButtonClasses: 'btn-light',
                    locale: {
                        format: 'DD/MM/YYYY',
                        separator: ' – ',
                        applyLabel: 'Terapkan',
                        cancelLabel: 'Batal',
                        customRangeLabel: 'Pilih sendiri',
                        daysOfWeek: ['Mg', 'Sn', 'Sl', 'Rb', 'Km', 'Jm', 'Sb'],
                        monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                        firstDay: 1,
                    },
                });
                const picker = $input.data('daterangepicker');
                picker.container.addClass('b2b-drp');

                // Ringkasan di footer popup dalam bahasa Indonesia, bukan format bawaan DD/MM/YYYY.
                function renderSelected() {
                    const box = picker.container.find('.drp-selected');
                    if (!picker.endDate) {
                        box.html(`<strong>${B2b.dateLabel(picker.startDate)}  –  …</strong><small>Pilih tanggal akhir</small>`);
                        return;
                    }
                    const days = picker.endDate.clone().startOf('day').diff(picker.startDate.clone().startOf('day'), 'days') + 1;
                    box.html(`<strong>${B2b.dateLabel(picker.startDate)}  –  ${B2b.dateLabel(picker.endDate)}</strong><small class="is-days">${days.toLocaleString('id-ID')} hari</small>`);
                }
                ['updateView', 'setEndDate'].forEach(name => {
                    const original = picker[name];
                    picker[name] = function () {
                        const result = original.apply(this, arguments);
                        renderSelected();
                        return result;
                    };
                });

                $input.on('show.daterangepicker', () => { $input.addClass('is-open'); renderSelected(); })
                    .on('hide.daterangepicker', () => $input.removeClass('is-open'))
                    .on('apply.daterangepicker', (event, picker) => onApply(picker.startDate, picker.endDate));

                return picker;
            };
        </script>
    @endpush
@endonce
