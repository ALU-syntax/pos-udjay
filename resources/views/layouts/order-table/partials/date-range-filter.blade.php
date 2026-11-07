{{--
    Filter rentang tanggal untuk toolbar gaya report.
    Nilai ke server ada di #date-from-filter dan #date-to-filter (YYYY-MM-DD); kosong berarti semua tanggal.
    Halaman memanggil initDateRangeFilter(onChange) setelah DataTable siap.
--}}
<div class="ob-field">
    <label for="date-range-filter"><i class="far fa-calendar-alt" aria-hidden="true"></i> Rentang Tanggal</label>
    <div class="ob-daterange">
        <input id="date-range-filter" type="text" class="form-control" placeholder="Semua tanggal" autocomplete="off" readonly>
        <button type="button" id="date-range-clear" class="ob-daterange__btn d-none" title="Hapus filter tanggal" aria-label="Hapus filter tanggal">
            <i class="fas fa-times" aria-hidden="true"></i>
        </button>
    </div>
    <input id="date-from-filter" type="hidden">
    <input id="date-to-filter" type="hidden">
</div>

@push('js')
    <script>
        window.initDateRangeFilter = function (onChange) {
            const range = $('#date-range-filter');
            const from = $('#date-from-filter');
            const to = $('#date-to-filter');
            const clear = $('#date-range-clear');

            function setRange(start, end) {
                range.val(start ? start.format('DD/MM/YYYY') + ' - ' + end.format('DD/MM/YYYY') : '');
                from.val(start ? start.format('YYYY-MM-DD') : '');
                to.val(end ? end.format('YYYY-MM-DD') : '');
                clear.toggleClass('d-none', !start);
                onChange();
            }

            range.daterangepicker({
                autoUpdateInput: false,
                linkedCalendars: false,
                maxDate: moment().endOf('day'),
                drops: 'auto',
                buttonClasses: 'btn btn-sm',
                applyButtonClasses: 'btn-primary',
                cancelButtonClasses: 'btn-light',
                ranges: {
                    'Hari ini': [moment(), moment()],
                    'Kemarin': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    '7 hari terakhir': [moment().subtract(6, 'days'), moment()],
                    '30 hari terakhir': [moment().subtract(29, 'days'), moment()],
                    'Bulan ini': [moment().startOf('month'), moment().endOf('month')],
                    'Bulan lalu': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                },
                locale: {
                    format: 'DD/MM/YYYY',
                    applyLabel: 'Terapkan',
                    cancelLabel: 'Batal',
                    customRangeLabel: 'Pilih tanggal',
                    daysOfWeek: ['Mg', 'Sn', 'Sl', 'Rb', 'Km', 'Jm', 'Sb'],
                    monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
                    firstDay: 1,
                },
            });
            range.on('apply.daterangepicker', (event, picker) => setRange(picker.startDate, picker.endDate));
            clear.on('click', () => setRange(null, null));
        };
    </script>
@endpush
