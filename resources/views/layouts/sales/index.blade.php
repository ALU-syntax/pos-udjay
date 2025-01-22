@extends('layouts.app')
@section('content')
    <style>
        /* Menghilangkan semua border pada tabel */
        #payment-method-table {
            border-collapse: collapse;
            /* Menghilangkan border */
        }

        /* Menghilangkan border pada sel */
        #payment-method-table th,
        #payment-method-table td {
            border: none;
            /* Menghilangkan border */
            padding: 8px;
            /* Menambahkan padding untuk estetika */
        }

        /* Menambahkan border vertikal di kanan kolom Payment Method */
        #payment-method-table td:first-child {
            border-right: 1px solid #ccc;
            /* Border kanan */
        }

        /* Menambahkan border horizontal atas dan bawah di baris Total */
        #payment-method-table tr:last-child {
            border-top: 2px solid #000;
            /* Border atas */
            border-bottom: 2px solid #000;
            /* Border bawah */
        }

        /* Menambahkan background abu pada header tabel */
        #payment-method-table thead th {
            background-color: #f2f2f2;
            /* Warna abu */
        }

        .nav-pills.nav-primary .nav-link.active {
            background: #d03c3c;
            border: 1px solid #d03c3c
        }
    </style>
    <div class="main-content">
        <div class="card text-center">
            <h5 class="card-header">Sales</h5>
        </div>

        <div class="card mt-4">
            <div class="card-header ">
                <div class="row">
                    <div class="col-4 align-self-center d-flex">
                        <select id="filter-outlet" class="form-control select2">
                            {{-- <option value="all" selected>-- Semua Outlet --</option> --}}
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
                <!-- Page Content -->
                <div class="row">
                    <div class="col-3">
                        <div class="nav flex-column nav-pills nav-primary nav-pills-no-bd" id="v-pills-tab" role="tablist"
                            aria-orientation="vertical">
                            <a class="nav-link active" id="sales-summary-tab-nobd" data-bs-toggle="pill"
                                href="#sales-summary-nobd" role="tab" aria-controls="v-pills-home-nobd"
                                aria-selected="true">Sales Summary</a>
                            <a class="nav-link" id="gross-profit-tab-nobd" data-bs-toggle="pill" href="#gross-profit-nobd"
                                role="tab" aria-controls="gross-profit-nobd" aria-selected="false">Gross Profit</a>
                            <a class="nav-link" id="payment-method-tab-nobd" data-bs-toggle="pill"
                                href="#payment-method-nobd" role="tab" aria-controls="payment-method-nobd"
                                aria-selected="false">Payment Method</a>
                            <a class="nav-link" id="sales-type-tab-nobd" data-bs-toggle="pill" href="#sales-type-nobd"
                                role="tab" aria-controls="sales-type-nobd" aria-selected="false">Sales Type</a>
                            <a class="nav-link" id="item-sales-tab-nobd" data-bs-toggle="pill" href="#item-sales-nobd"
                                role="tab" aria-controls="item-sales-nobd" aria-selected="false">Item Sales</a>
                            <a class="nav-link" id="category-sales-tab-nobd" data-bs-toggle="pill"
                                href="#category-sales-nobd" role="tab" aria-controls="category-sales-nobd"
                                aria-selected="false">Category Sales</a>
                            <a class="nav-link" id="brand-sales-tab-nobd" data-bs-toggle="pill" href="#brand-sales-nobd"
                                role="tab" aria-controls="brand-sales-nobd" aria-selected="false">Brand Sales</a>
                            <a class="nav-link" id="modifier-sales-tab-nobd" data-bs-toggle="pill"
                                href="#modifier-sales-nobd" role="tab" aria-controls="modifier-sales-nobd"
                                aria-selected="false">Modifier Sales</a>
                            <a class="nav-link" id="discount-tab-nobd" data-bs-toggle="pill" href="#discount-nobd"
                                role="tab" aria-controls="discount-nobd" aria-selected="false">Discounts</a>
                            <a class="nav-link" id="taxes-tab-nobd" data-bs-toggle="pill" href="#taxes-nobd" role="tab"
                                aria-controls="taxes-nobd" aria-selected="false">Taxes</a>
                            <a class="nav-link" id="gratuity-tab-nobd" data-bs-toggle="pill" href="#gratuity-nobd"
                                role="tab" aria-controls="gratuity-nobd" aria-selected="false">Gratuity</a>
                            <a class="nav-link" id="collected-by-tab-nobd" data-bs-toggle="pill"
                                href="#collected-by-nobd" role="tab" aria-controls="collected-by-nobd"
                                aria-selected="false">Collected By</a>
                            <a class="nav-link" id="served-by-tab-nobd" data-bs-toggle="pill" href="#served-by-nobd"
                                role="tab" aria-controls="served-by-nobd" aria-selected="false">Served By</a>
                        </div>
                    </div>
                    <div class="col-9">
                        <div class="tab-content" id="v-pills-tabContent">
                            <div class="tab-pane fade show active" id="sales-summary-nobd" role="tabpanel"
                                aria-labelledby="sales-summary-tab-nobd">
                                <p>Comming Soon</p>
                            </div>
                            <div class="tab-pane fade" id="gross-profit-nobd" role="tabpanel"
                                aria-labelledby="gross-profit-tab-nobd">
                                <p>Comming Soon</p>
                            </div>
                            <div class="tab-pane fade" id="payment-method-nobd" role="tabpanel"
                                aria-labelledby="payment-method-tab-nobd">
                                <table id="payment-method-table" class="table">
                                    <thead>
                                        <tr>
                                            <th><b>Payment Method</b></th>
                                            <th><b>Number of Transactions</b></th>
                                            <th><b>Total Collected</b></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data akan dimuat di sini -->
                                    </tbody>
                                </table>
                            </div>
                            <div class="tab-pane fade" id="sales-type-nobd" role="tabpanel"
                                aria-labelledby="sales-type-tab-nobd">
                                <p>Comming Soon</p>
                            </div>
                            <div class="tab-pane fade" id="item-sales-nobd" role="tabpanel"
                                aria-labelledby="item-sales-tab-nobd">
                                <p>Comming Soon</p>
                            </div>
                            <div class="tab-pane fade" id="category-sales-nobd" role="tabpanel"
                                aria-labelledby="category-sales-tab-nobd">
                                <p>Comming Soon</p>
                            </div>
                            <div class="tab-pane fade" id="brand-sales-nobd" role="tabpanel"
                                aria-labelledby="brand-sales-tab-nobd">
                                <p>Comming Soon</p>
                            </div>
                            <div class="tab-pane fade" id="modifier-sales-nobd" role="tabpanel"
                                aria-labelledby="modifier-sales-tab-nobd">
                                <p>Comming Soon</p>
                            </div>
                            <div class="tab-pane fade" id="discount-nobd" role="tabpanel"
                                aria-labelledby="discount-tab-nobd">
                                <p>Comming Soon</p>
                            </div>
                            <div class="tab-pane fade" id="taxes-nobd" role="tabpanel" aria-labelledby="taxes-tab-nobd">
                                <p>Comming Soon</p>
                            </div>
                            <div class="tab-pane fade" id="gratuity-nobd" role="tabpanel"
                                aria-labelledby="gratuity-tab-nobd">
                                <p>Comming Soon</p>
                            </div>
                            <div class="tab-pane fade" id="collected-by-nobd" role="tabpanel"
                                aria-labelledby="collected-by-tab-nobd">
                                <p>Comming Soon</p>
                            </div>
                            <div class="tab-pane fade" id="served-by-nobd" role="tabpanel"
                                aria-labelledby="served-by-tab-nobd">
                                <p>Comming Soon</p>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    @push('js')
        <script>
            function checkActiveTab() {
                var activeTab = $('a.nav-link.active').attr('href');
                console.log('Active Tab:', activeTab);

                if (activeTab === '#sales-summary-nobd') {
                    // Logika untuk Sales Summary  
                } else if (activeTab === '#gross-profit-nobd') {
                    // Logika untuk Gross Profit  
                } else if (activeTab === '#payment-method-nobd') {
                    var outlet = $('#filter-outlet').val();
                    var date = $('#date_range_transaction').val();

                    $.ajax({
                        url: '{{ route('report/sales/getPaymentMethodSales') }}',
                        method: 'GET',
                        data: {
                            date: date,
                            outlet: outlet
                        },
                        beforeSend: function() {
                            showLoader();
                        },
                        complete: function() {
                            showLoader(false);
                        },
                        success: function(data) {
                            var tbody = $('#payment-method-table tbody');
                            tbody.empty();

                            let numberOfTransaction = 0;
                            let totalCollected = 0;

                            $.each(data, function(index, transaction) {
                                console.log(transaction);
                                numberOfTransaction += transaction.number_of_transactions != '' ?
                                    transaction.number_of_transactions : 0;
                                totalCollected += transaction.total_collected != '' ? transaction
                                    .total_collected : 0;

                                if (transaction.parent) {
                                    if (transaction.payment_method == "Cash") {
                                        tbody.append(`  
                                <tr>  
                                    <td>${transaction.payment_method}</td>  
                                    <td>${transaction.number_of_transactions}</td>  
                                    <td>${formatRupiah(transaction.total_collected.toString(), "Rp. ")}</td>  
                                </tr>  
                            `);
                                    } else {
                                        tbody.append(`  
                                <tr>  
                                    <td>${transaction.payment_method}</td>  
                                    <td>${transaction.number_of_transactions}</td>  
                                    <td></td>  
                                </tr>  
                            `);
                                    }
                                } else {
                                    tbody.append(`  
                            <tr>  
                                <td style="color: rgb(133 133 133 / 75%) !important;">&nbsp ${transaction.payment_method}</td>  
                                <td>${transaction.number_of_transactions}</td>  
                                <td>${formatRupiah(transaction.total_collected.toString(), "Rp. ")}</td>  
                            </tr>  
                        `);
                                }
                            });

                            tbody.append(`  
                    <tr>  
                        <td>Total</td>  
                        <td>${numberOfTransaction}</td>  
                        <td>${formatRupiah(totalCollected.toString(), "Rp. ")}</td>  
                    </tr>  
                `);
                        },
                        error: function(xhr) {
                            console.error(xhr);
                        }
                    });
                }
                // Tambahkan logika untuk tab lainnya  
            }

            $(document).ready(function() {
                checkActiveTab();
                var startDate = moment().startOf('day');
                var endDate = moment().endOf('day');

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

                    console.log(startDate);
                    console.log(endDate);
                    $('#date_range_transaction').data('daterangepicker').setStartDate(startDate);
                    $('#date_range_transaction').data('daterangepicker').setEndDate(endDate);

                    $('#date_range_transaction').val(startDate.format('YYYY/MM/DD') + ' - ' + endDate.format(
                        'YYYY/MM/DD'));

                    checkActiveTab();
                });

                $('#nextDate').on('click', function() {
                    startDate.add(1, 'days');
                    endDate.add(1, 'days');

                    console.log(startDate)
                    console.log(endDate)
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

                $('a[data-bs-toggle="pill"]').off().on('shown.bs.tab', function(e) {
                    checkActiveTab();
                });

                $('#filter-outlet').on('change', function() {
                    checkActiveTab();
                });

            })
        </script>
    @endpush
@endsection
