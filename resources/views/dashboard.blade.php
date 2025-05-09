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
                            <a class="nav-link active" aria-current="page" href="#">Summary</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" aria-current="page" href="#">Outlet Comparison</a>
                        </li>

                    </ul>
                </div>
            </div>

            <div class="row mt-2 d-flex">
                <div class="col-4 align-self-center d-flex">
                    <select id="filter-outlet" class="form-control select2">
                        <option value="all" selected>-- Semua Outlet --</option>
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
                                <h4 id="gross-sales" class="card-title">{{ formatRupiah(strval($grossSales), 'Rp. ') }}</h4>
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
                                <h4 id="net-sales" class="card-title">{{ formatRupiah(strval($netSales), 'Rp. ') }}</h4>
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
                                <h4 id="gross-profit" class="card-title">{{ formatRupiah(strval($netSales), 'Rp. ') }}</h4>
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

    @push('js')
        <script>
            //Chart
            var ctx = document.getElementById('statisticsChart').getContext('2d');
            // Data dari PHP
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


            $(document).ready(function() {
                $('#filter-outlet').on('change', function() {
                    getDataSummary();
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
                    getDataSummary();
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

                    getDataSummary();
                });

                $('#nextDate').on('click', function() {
                    startDate.add(1, 'days');
                    endDate.add(1, 'days');

                    $('#date_range_transaction').data('daterangepicker').setStartDate(startDate);
                    $('#date_range_transaction').data('daterangepicker').setEndDate(endDate);

                    $('#date_range_transaction').val(startDate.format('YYYY/MM/DD') + ' - ' + endDate.format(
                        'YYYY/MM/DD'));
                    getDataSummary()
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
                    getDataSummary();
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
