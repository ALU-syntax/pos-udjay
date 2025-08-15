@extends('layouts.app')
@section('content')
    <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-1">
        <div>
            <h3 class="fw-bold mb-1">Dashboard</h3>
            {{-- <h6 class="op-7 mb-2">Free Bootstrap 5 Admin Dashboard</h6> --}}
        </div>
        <div class="ms-md-auto py-2 py-md-0">
            {{-- <a href="#" class="btn btn-label-info btn-round me-2">Manage</a>
            <a href="#" class="btn btn-primary btn-round">Add Customer</a> --}}
        </div>
    </div>

    <hr>

    <div class="card mt-4">
        <div class="card-header ">
            <div class="row">
                <div class="col-12">
                    <ul class="nav nav-tabs">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="pill" role="tab" aria-current="page"
                                href="#summary-sales">Summary</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="pill" role="tab" aria-current="page"
                                href="#outlet-compare">Outlet Comparison</a>
                        </li>

                    </ul>
                </div>
            </div>

            <div class="row mt-2 d-flex">
                <div class="col-8 align-self-center ">
                    <button id="select-all-option" class="btn btn-primary ">Select All</button>
                    <select id="filter-outlet" class="form-control select2 w-75" multiple>
                        @foreach ($outlets as $outlet)
                            <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-4 align-self-end just d-flex">
                    <div class="input-group mb-3 mt-3">
                        <div class="input-group-prepend">
                            <button class="btn btn-outline-secondary" type="button" id="prevDate">-</button>
                        </div>
                        <input type="text" id="date_range_transaction" name="date_range_transaction"
                            class="form-control">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button" id="nextDate">+</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <div class="card-body">

        </div>
    </div>

    <div class="tab-content" id="v-pills-tabContent">
        <div class="tab-pane show active" id="summary-sales" role="tabpanel">
            <div class="row">
                <div class="col-sm-6 col-md-3">
                    <div class="card card-stats card-round">
                        <div class="card-body ">
                            <div class="row align-items-center">
                                <div class="col-icon">
                                    <div class="icon-big text-center icon-primary bubble-shadow-small">
                                        <i class="fas fa-shopping-cart"></i>
                                    </div>
                                </div>
                                <div class="col col-stats ms-3 ms-sm-0">
                                    <div class="numbers">
                                        <p class="card-category">Gross Sales</p>
                                        <h4 id="gross-sales" class="card-title">
                                            {{ formatRupiah(strval($grossSales), 'Rp. ') }}
                                        </h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="card card-stats card-round">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-icon">
                                    <div class="icon-big text-center icon-info bubble-shadow-small">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                </div>
                                <div class="col col-stats ms-3 ms-sm-0">
                                    <div class="numbers">
                                        <p class="card-category">Net Sales</p>
                                        <h4 id="net-sales" class="card-title">{{ formatRupiah(strval($netSales), 'Rp. ') }}
                                        </h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="card card-stats card-round">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-icon">
                                    <div class="icon-big text-center icon-success bubble-shadow-small">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                </div>
                                <div class="col col-stats ms-3 ms-sm-0">
                                    <div class="numbers">
                                        <p class="card-category">Gross Profit</p>
                                        <h4 id="gross-profit" class="card-title">
                                            {{ formatRupiah(strval($netSales), 'Rp. ') }}
                                        </h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="card card-stats card-round">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-icon">
                                    <div class="icon-big text-center icon-secondary bubble-shadow-small">
                                        <i class="fas fa-file-invoice-dollar"></i>
                                    </div>
                                </div>
                                <div class="col col-stats ms-3 ms-sm-0">
                                    <div class="numbers">
                                        <p class="card-category">Transactions</p>
                                        <h4 id="transactions" class="card-title">{{ $transactions }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-round">
                        <div class="card-header">
                            <div class="card-head-row">
                                <div class="card-title">Hourly Gross Sales Amount</div>
                                <div class="card-tools">
                                    {{-- <a href="#" class="btn btn-label-success btn-round btn-sm me-2">
                                        <span class="btn-label">
                                            <i class="fa fa-pencil"></i>
                                        </span>
                                        Export
                                    </a>
                                    <a href="#" class="btn btn-label-info btn-round btn-sm">
                                        <span class="btn-label">
                                            <i class="fa fa-print"></i>
                                        </span>
                                        Print
                                    </a> --}}
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="min-height: 375px">
                                <canvas id="statisticsChart"></canvas>
                            </div>
                            <div id="myChartLegend"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane" id="outlet-compare" role="tabpanel">
            <div class="row" id="list-outlet">
                <h4>TABLE SUMMARY</h4>
                <hr>

                <div class="col-3">
                    <div class="card px-0" style="width: 18rem;">
                        <div class="card-header rounded-top border-bottom border-primary mx-0"
                            style="background-color: #d2d8fa; border-bottom-color:#0923b6 !important;">
                            <h5>Sales Summary</h5>
                        </div>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item py-3 ">Gross Sales</li>
                            <li class="list-group-item py-3">Net Sales</li>
                            <li class="list-group-item py-3">Gross Profit</li>
                            <li class="list-group-item py-3">Transaction</li>
                            <li class="list-group-item py-3">Average Sales Per Transaction</li>
                            <li class="list-group-item py-3">Gross Margin</li>
                        </ul>
                        <div class="card-footer  border-bottom border-primary mx-0"
                            style="background-color: #d2d8fa; border-bottom-color:#0923b6 !important; border-top-color:#0923b6 !important;">
                            <h5>Items</h5>
                        </div>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item py-3">Top Three Item</li>
                            <li class="list-group-item py-3">Down Three Item</li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </div>



    @push('js')
        <script>
            //Chart
            var ctx = document.getElementById('statisticsChart').getContext('2d');

            // Flag untuk mencegah loop tak berujung saat meng-set nilai kembali
            var isRestoring = false;

            const hours = @json($hours);
            const hourlyGrossSalesPerOutlet = @json($hourlyGrossSalesPerOutlet);
            const outlets = @json($outlets);

            var outlet = $('#filter-outlet');
            var date = $('#date_range_transaction');
            var startDate = moment().startOf('day');
            var endDate = moment().endOf('day');

            function getDataSummary() {
                $.ajax({
                    url: '{{ route('getDataSummary') }}',
                    method: 'GET',
                    data: {
                        date: date.val(),
                        outlet: outlet.val()
                    },
                    beforeSend: function() {
                        showLoader();
                    },
                    complete: function() {
                        showLoader(false);
                    },
                    success: function(data) {
                        $('#gross-sales').text(formatRupiah(data.grossSales.toString(), 'Rp. '));
                        $('#net-sales').text(formatRupiah(data.netSales.toString(), "Rp. "));
                        $('#gross-profit').text(formatRupiah(data.netSales.toString(), "Rp. "));
                        $('#transactions').text(data.transactions);

                        initChart(data);
                    },
                    error: function(xhr) {
                        console.error(xhr);
                    }
                });
            }

            // Fungsi generate warna hex random
            function getRandomColor() {
                const letters = '0123456789ABCDEF';
                let color = '#';
                for (let i = 0; i < 6; i++) {
                    color += letters[Math.floor(Math.random() * 16)];
                }
                return color;
            }

            function initChart(data) {
                const ctx = document.getElementById('statisticsChart').getContext('2d');

                // Jika chart sudah ada, destroy dulu
                if (window.statisticsChart) {
                    window.statisticsChart.destroy();
                }

                // Buat datasets dari data.outlets dan data.hourlyGrossSalesPerOutlet
                const datasets = data.outlets.map(outlet => {
                    const borderColor = getRandomColor();
                    return {
                        label: outlet.name,
                        borderColor: borderColor,
                        pointBackgroundColor: borderColor,
                        pointRadius: 3,
                        backgroundColor: borderColor + '33', // transparansi ~20%
                        fill: true,
                        borderWidth: 2,
                        data: data.hourlyGrossSalesPerOutlet[outlet.id] || Array(24).fill(0)
                    };
                });

                // Buat chart baru
                window.statisticsChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.hours,
                        datasets: datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        legend: {
                            display: false
                        },
                        tooltips: {
                            bodySpacing: 4,
                            mode: "nearest",
                            intersect: 0,
                            position: "nearest",
                            xPadding: 10,
                            yPadding: 10,
                            caretPadding: 10
                        },
                        layout: {
                            padding: {
                                left: 5,
                                right: 5,
                                top: 15,
                                bottom: 15
                            }
                        },
                        scales: {
                            yAxes: [{
                                ticks: {
                                    fontStyle: "500",
                                    beginAtZero: false,
                                    maxTicksLimit: 5,
                                    padding: 10
                                },
                                gridLines: {
                                    drawTicks: false,
                                    display: false
                                }
                            }],
                            xAxes: [{
                                gridLines: {
                                    zeroLineColor: "transparent"
                                },
                                ticks: {
                                    padding: 10,
                                    fontStyle: "500"
                                }
                            }]
                        }
                    }
                });
            }

            function generateOutletCard(data) {
                data.data.forEach(function(dataOutlet) {
                    var cardCol = $('<div>').addClass('col-3');
                    var card = $('<div>').addClass('card px-0').css('width', '18rem');
                    var header = $('<div>').addClass('card-header rounded-top border-bottom border-primary mx-0').css({
                        'background-color': '#d2d8fa',
                        'border-bottom-color': '#0923b6'
                    }).append($('<h5>').text(dataOutlet.outlet));
                    var list1 = $('<ul>').addClass('list-group list-group-flush');
                    [formatRupiah(dataOutlet.grossSales.toString(), "Rp. "), formatRupiah(dataOutlet.netSales
                            .toString(), "Rp. "), formatRupiah(dataOutlet.netSales.toString(), "Rp. "), dataOutlet
                        .transactions, formatRupiah(dataOutlet.averageSales.toString(), "Rp. "), dataOutlet
                        .grossMargin + "%"
                    ]
                    .forEach(function(text) {
                        list1.append($('<li>').addClass('list-group-item py-3').text(text));
                    });
                    var footer = $('<div>').addClass('card-footer border-bottom border-primary mx-0').css({
                        'background-color': '#d2d8fa',
                        'border-bottom-color': '#0923b6',
                        'border-top-color': '#0923b6'
                    }).append($('<h5>').text('Items'));

                    // TOP THREE ITEM
                    var itemsOuterUl = $('<ul>').addClass('list-group list-group-flush');
                    var itemsOuterLi = $('<li>').addClass(
                        'list-group-item py-3 pe-2 d-block'); // pe-0 opsional agar badge mepet kanan
                    var innerList = $('<ul>').addClass('list-unstyled mb-0'); // daftar di dalam tanpa border

                    if (Array.isArray(dataOutlet.topThreeItem) && dataOutlet.topThreeItem.length) {
                        dataOutlet.topThreeItem.forEach(function(e) {
                            // nama item
                            var name;
                            if (!e.variant) {
                                name = e.product || 'Tanpa nama';
                            } else if (e.product === e.variant) {
                                name = e.variant;
                            } else {
                                name = e.product + ' - ' + e.variant;
                            }

                            // === BARIS ITEM: badge di ujung kanan ===
                            var row = $('<li>').addClass('d-flex align-items-center w-100 py-1');

                            var nameEl = $('<span>').addClass('flex-grow-1 text-truncate').text(
                                name); // kiri, melebar
                            var badgeEl = $('<span>').addClass(
                                'badge rounded-pill ms-auto'); // kanan, nempel ujung
                            // pilih warna badge (opsional)
                            badgeEl.addClass('bg-primary');

                            if (e.qty != null) {
                                badgeEl.text(e.qty);
                            } else {
                                // kalau tidak ada qty, bisa kosong atau sembunyikan:
                                // badgeEl.remove(); // kalau mau disembunyikan
                                badgeEl.text('0');
                            }

                            row.append(nameEl, badgeEl);
                            innerList.append(row);
                        });
                    } else {
                        innerList.append($('<li>').addClass('py-1 text-muted').text('Tidak ada item'));
                    }

                    itemsOuterLi.append(innerList);
                    itemsOuterUl.append(itemsOuterLi);

                    // DOWN THREE ITEM
                    var itemsOuterUlDown = $('<ul>').addClass('list-group list-group-flush');
                    var itemsOuterLiDown = $('<li>').addClass(
                        'list-group-item py-3 pe-2 d-block'); // pe-0 opsional agar badge mepet kanan
                    var innerListDown = $('<ul>').addClass('list-unstyled mb-0'); // daftar di dalam tanpa border

                    if (Array.isArray(dataOutlet.downThreeItem) && dataOutlet.downThreeItem.length) {
                        dataOutlet.downThreeItem.forEach(function(e) {
                            // nama item
                            var name;
                            if (!e.variant) {
                                name = e.product || 'Tanpa nama';
                            } else if (e.product === e.variant) {
                                name = e.variant;
                            } else {
                                name = e.product + ' - ' + e.variant;
                            }

                            // === BARIS ITEM: badge di ujung kanan ===
                            var row = $('<li>').addClass('d-flex align-items-center w-100 py-1');

                            var nameEl = $('<span>').addClass('flex-grow-1 text-truncate').text(
                                name); // kiri, melebar
                            var badgeEl = $('<span>').addClass(
                                'badge rounded-pill ms-auto'); // kanan, nempel ujung
                            // pilih warna badge (opsional)
                            badgeEl.addClass('bg-primary');

                            if (e.qty != null) {
                                badgeEl.text(e.qty);
                            } else {
                                // kalau tidak ada qty, bisa kosong atau sembunyikan:
                                // badgeEl.remove(); // kalau mau disembunyikan
                                badgeEl.text('0');
                            }

                            row.append(nameEl, badgeEl);
                            innerListDown.append(row);
                        });
                    } else {
                        innerListDown.append($('<li>').addClass('py-1 text-muted').text('Tidak ada item'));
                    }

                    itemsOuterLiDown.append(innerListDown);
                    itemsOuterUlDown.append(itemsOuterLiDown);

                    card.append(header, list1, footer, itemsOuterUl, itemsOuterUlDown);
                    cardCol.append(card);
                    $('#list-outlet').append(cardCol);
                });
            }

            function checkActiveTab() {
                var activeTab = $('a.nav-link.active').attr('href');

                // Sembunyikan semua tab-pane
                $('.tab-pane').removeClass('show active');

                // Tampilkan tab-pane yang sesuai
                $(activeTab).addClass('show active');

                // Panggil fungsi sesuai tab aktif
                if (activeTab === '#summary-sales') {
                    getDataSummary();
                } else if (activeTab === '#outlet-compare') {

                    getDataOutletCompare();
                }
            }


            function getDataOutletCompare() {
                $.ajax({
                    url: '{{ route('getDataOutletCompare') }}',
                    method: 'GET',
                    data: {
                        date: date.val(),
                        outlet: outlet.val()
                    },
                    beforeSend: function() {
                        showLoader();
                    },
                    complete: function() {
                        showLoader(false);
                    },
                    success: function(data) {
                        console.log(data);
                        $('#list-outlet .col-3:not(:first)').remove();
                        generateOutletCard(data);
                    },
                    error: function(xhr) {
                        console.error(xhr);
                    }
                });
            }


            $(document).ready(function() {
                $('#filter-outlet').select2({
                    placeholder: "-- Pilih Outlet --",
                    // allowClear: true,
                    multiple: true
                });

                // Ambil semua nilai option
                var allValues = $('#filter-outlet option').map(function() {
                    return $(this).val();
                }).get();

                // Set semua option terpilih dan update Select2
                $('#filter-outlet').val(allValues).trigger('change');

                $('#select-all-option').off().on('click', function() {
                    $("#filter-outlet > option").prop("selected", "selected");
                    $("#filter-outlet").trigger("change");
                })

                $('#filter-outlet').on('change', function(e) {
                    if (isRestoring) {
                        // Jika sedang restore, abaikan event ini untuk mencegah loop
                        return;
                    }

                    var selected = $(this).val();

                    if (selected === null || selected.length === 0) {
                        allValues.splice(1);
                        // Jika tidak ada pilihan (clear all), kembalikan pilihan terakhir
                        isRestoring = true;

                        $(this).val(lastSelected).trigger('change');
                        isRestoring = false;
                    } else {
                        // Update pilihan terakhir yang valid
                        lastSelected = selected;
                    }
                    checkActiveTab();
                });

                $('a[data-bs-toggle="pill"]').off().on('shown.bs.tab', function(e) {
                    checkActiveTab();
                });

                // Buat datasets dinamis per outlet
                const datasets = outlets.map(outlet => {
                    const borderColor = getRandomColor();
                    return {
                        label: outlet.name,
                        borderColor: borderColor,
                        pointBackgroundColor: borderColor,
                        pointRadius: 3,
                        backgroundColor: borderColor + '33', // Transparansi ~20%
                        fill: true,
                        borderWidth: 2,
                        data: hourlyGrossSalesPerOutlet[outlet.id] || Array(24).fill(0)
                    };
                });

                $('#date_range_transaction').daterangepicker({
                    startDate: startDate,
                    endDate: endDate,
                    // minDate: moment(),
                    ranges: {
                        'Today': [moment(), moment()],
                        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                        'This Month': [moment().startOf('month'), moment().endOf('month')],
                        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1,
                            'month').endOf('month')]
                    },
                    "linkedCalendars": false,
                    "autoUpdateInput": false,
                    "showCustomRangeLabel": true,
                    // "startDate": "12/30/2024",
                    // "endDate": "01/05/2025",
                    "drops": "auto",
                    "buttonClasses": "btn btn-primary"
                }, function(start, end, label) {
                    $('#date_range_transaction').val(start.format('YYYY/MM/DD') + ' - ' + end.format(
                        'YYYY/MM/DD'));
                    console.log('New date range selected: ' + start.format('YYYY-MM-DD') + ' to ' + end.format(
                        'YYYY-MM-DD') + ' (predefined range: ' + label + ')');

                    startDate = start;
                    endDate = end;

                    // Trigger refresh DataTable setelah memilih rentang tanggal
                    checkActiveTab();
                });

                // Set initial value for the input field
                $('#date_range_transaction').val(startDate.format('YYYY/MM/DD') + ' - ' + endDate.format('YYYY/MM/DD'));

                // Fungsi untuk mengubah tanggal
                $('#prevDate').on('click', function() {
                    startDate.subtract(1, 'days');
                    endDate.subtract(1, 'days');

                    $('#date_range_transaction').data('daterangepicker').setStartDate(startDate);
                    $('#date_range_transaction').data('daterangepicker').setEndDate(endDate);

                    $('#date_range_transaction').val(startDate.format('YYYY/MM/DD') + ' - ' + endDate.format(
                        'YYYY/MM/DD'));

                    checkActiveTab();
                });

                $('#nextDate').on('click', function() {
                    startDate.add(1, 'days');
                    endDate.add(1, 'days');

                    $('#date_range_transaction').data('daterangepicker').setStartDate(startDate);
                    $('#date_range_transaction').data('daterangepicker').setEndDate(endDate);

                    $('#date_range_transaction').val(startDate.format('YYYY/MM/DD') + ' - ' + endDate.format(
                        'YYYY/MM/DD'));
                    checkActiveTab()
                });

                $('.ranges li').addClass('btn btn-primary w-75 ms-3 mt-2');

                // Event untuk mengosongkan rentang saat daterangepicker dibuka
                $('#date_range_transaction').on('show.daterangepicker', function(ev, picker) {
                    picker.setStartDate(moment().startOf(
                        'day')); // Set start date ke hari ini atau tanggal lain
                    picker.setEndDate(moment().startOf('day')); // Set end date ke hari ini atau tanggal lain
                });

                // Event untuk menangani pemilihan rentang yang telah ditentukan
                $('#date_range_transaction').on('apply.daterangepicker', function(ev, picker) {
                    // Memperbarui DataTable ketika rentang yang telah ditentukan dipilih
                    checkActiveTab();
                });


                window.statisticsChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: hours,
                        datasets: datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        legend: {
                            display: false
                        },
                        tooltips: {
                            bodySpacing: 4,
                            mode: "nearest",
                            intersect: 0,
                            position: "nearest",
                            xPadding: 10,
                            yPadding: 10,
                            caretPadding: 10
                        },
                        layout: {
                            padding: {
                                left: 5,
                                right: 5,
                                top: 15,
                                bottom: 15
                            }
                        },
                        scales: {
                            yAxes: [{
                                ticks: {
                                    fontStyle: "500",
                                    beginAtZero: false,
                                    maxTicksLimit: 5,
                                    padding: 10
                                },
                                gridLines: {
                                    drawTicks: false,
                                    display: false
                                }
                            }],
                            xAxes: [{
                                gridLines: {
                                    zeroLineColor: "transparent"
                                },
                                ticks: {
                                    padding: 10,
                                    fontStyle: "500"
                                }
                            }]
                        },
                        legendCallback: function(chart) {
                            var text = [];
                            text.push('<ul class="' + chart.id + '-legend html-legend">');
                            for (var i = 0; i < chart.data.datasets.length; i++) {
                                text.push('<li><span style="background-color:' + chart.data.datasets[i]
                                    .legendColor +
                                    '"></span>');
                                if (chart.data.datasets[i].label) {
                                    text.push(chart.data.datasets[i].label);
                                }
                                text.push('</li>');
                            }
                            text.push('</ul>');
                            return text.join('');
                        }
                    }
                });


            });
        </script>
    @endpush
@endsection
