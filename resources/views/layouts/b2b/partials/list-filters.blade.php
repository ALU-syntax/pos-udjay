{{--
    Filter daftar B2B (Pesanan, Invoice): kartu ringkasan sebagai filter cepat, baris filter, rentang tanggal, tombol hapus filter.
    Markup:
      - Kartu: <button class="ob-card ob-stat b2b-stat-filter" data-filter="status" data-value="overdue" aria-pressed="false"> → mengubah #status-filter.
      - Baris: <div class="b2b-filter-row"> berisi .ob-field dengan <select id="...-filter" class="form-select" data-default="...">.
      - Rentang: <div class="ob-field b2b-form"><div class="b2b-range"><i></i><input readonly><button class="b2b-range__clear"></button></div>
        + dua <input type="hidden"> untuk tanggal awal/akhir (Y-m-d).
      - Tombol hapus filter: <button id="filter-reset" hidden>.
    JS: B2b.listFilters({ table: 'id-datatable', selects: '#a-filter, #b-filter', range: { input, from, to, clear, ranges: 'past'|'due' } }).
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
            .b2b-filter-row--3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            @media (max-width: 1199.98px) { .b2b-filter-row { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
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
                const $input = range ? $(range.input) : $();
                const $from = range ? $(range.from) : $();
                const $to = range ? $(range.to) : $();

                function syncState() {
                    $selects.each(function () { $(this).toggleClass('is-set', this.value !== initial(this)); });
                    $input.toggleClass('is-set', $from.val() !== '');
                    if (range) $(range.clear).prop('hidden', !$from.val());
                    $('.b2b-stat-filter').each(function () {
                        $(this).attr('aria-pressed', $(`#${this.dataset.filter}-filter`).val() === this.dataset.value);
                    });
                    const dirty = $selects.toArray().some(el => el.value !== initial(el)) || !!$from.val();
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

                const shortMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                const label = date => `${date.format('DD')} ${shortMonths[date.month()]} ${date.year()}`;
                function setRange(start, end) {
                    $input.val(start ? (start.isSame(end, 'day') ? label(start) : `${label(start)}  –  ${label(end)}`) : '');
                    $from.val(start ? start.format('YYYY-MM-DD') : '');
                    $to.val(start ? end.format('YYYY-MM-DD') : '');
                    reload();
                }

                if (range && $.fn.daterangepicker) {
                    const today = moment().startOf('day');
                    // 'past' untuk tanggal transaksi (tidak bisa ke depan), 'due' untuk jatuh tempo (bisa ke depan).
                    const presets = range.ranges === 'due'
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
                    $input.daterangepicker({
                        autoUpdateInput: false,
                        drops: 'auto',
                        opens: 'left',
                        showDropdowns: true,
                        linkedCalendars: false,
                        alwaysShowCalendars: true,
                        maxDate: range.ranges === 'due' ? undefined : today.clone().endOf('day'),
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
                            box.html(`<strong>${label(picker.startDate)}  –  …</strong><small>Pilih tanggal akhir</small>`);
                            return;
                        }
                        const days = picker.endDate.clone().startOf('day').diff(picker.startDate.clone().startOf('day'), 'days') + 1;
                        box.html(`<strong>${label(picker.startDate)}  –  ${label(picker.endDate)}</strong><small class="is-days">${days.toLocaleString('id-ID')} hari</small>`);
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
                        .on('apply.daterangepicker', (event, picker) => setRange(picker.startDate, picker.endDate));
                }

                if (range) $(range.clear).on('click', () => setRange(null));
                $('#filter-reset').on('click', function () {
                    $selects.each(function () { this.value = initial(this); });
                    if (range) setRange(null); else reload();
                });

                // Kartu ringkasan dimuat ulang setelah pelunasan (payment-modal); pasang lagi status tombolnya.
                $(document).on('b2b:stats-refreshed', syncState);

                syncState();
            };
        </script>
    @endpush
@endonce
