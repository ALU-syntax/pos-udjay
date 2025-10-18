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

        #payment-method-merchant-table {
            border-collapse: collapse;
            /* Menghilangkan border */
        }

        /* Menghilangkan border pada sel */
        #payment-method-merchant-table th,
        #payment-method-merchant-table td {
            border: none;
            /* Menghilangkan border */
            padding: 8px;
            /* Menambahkan padding untuk estetika */
        }

        /* Menambahkan border vertikal di kanan kolom Payment Method */
        #payment-method-merchant-table td:first-child {
            border-right: 1px solid #ccc;
            /* Border kanan */
        }

        /* Menambahkan border horizontal atas dan bawah di baris Total */
        #payment-method-merchant-table tr:last-child {
            border-top: 2px solid #000;
            /* Border atas */
            border-bottom: 2px solid #000;
            /* Border bawah */
        }

        /* Menambahkan background abu pada header tabel */
        #payment-method-merchant-table thead th {
            background-color: #f2f2f2;
            /* Warna abu */
        }

        /* Menambahkan border vertikal di kanan kolom item sales */
        #item-sales td:first-child {
            border-right: 1px solid #ccc;
            /* Border kanan */
        }

        /* Menambahkan border horizontal atas dan bawah di baris Total */
        #item-sales tr:last-child {
            border-top: 2px solid #000;
            /* Border atas */
            border-bottom: 2px solid #000;
            /* Border bawah */
        }

        /* Menambahkan background abu pada header tabel */
        #item-sales thead th {
            background-color: #f2f2f2;
            /* Warna abu */
        }

        /* Pastikan kolom Name boleh membungkus dan punya lebar minimal */
        #item-sales td:nth-child(1),
        #item-sales th:nth-child(1) {
            white-space: normal;
            /* hapus kalau sebelumnya pakai text-nowrap */
            min-width: 200px;
            /* match dengan JS columnDefs width */
        }

        /* Kalau kamu sebelumnya menaruh text-truncate/nowrap di semua sel, netralkan khusus Name */
        #item-sales td:nth-child(1) .text-truncate {
            overflow: visible;
            text-overflow: unset;
            white-space: normal;
        }


        /* Menambahkan border vertikal di kanan kolom category sales */
        #category-sales td:first-child {
            border-right: 1px solid #ccc;
            /* Border kanan */
        }

        /* Menambahkan border horizontal atas dan bawah di baris Total */
        #category-sales tr:last-child {
            border-top: 2px solid #000;
            /* Border atas */
            border-bottom: 2px solid #000;
            /* Border bawah */
        }

        /* Menambahkan background abu pada header tabel */
        #category-sales thead th {
            background-color: #f2f2f2;
            /* Warna abu */
        }

        /* Menambahkan border vertikal di kanan kolom modifier sales */
        #modifier-sales td:first-child {
            border-right: 1px solid #ccc;
            /* Border kanan */
        }

        /* Menambahkan border horizontal atas dan bawah di baris Total */
        #modifier-sales tr:last-child {
            border-top: 2px solid #000;
            /* Border atas */
            border-bottom: 2px solid #000;
            /* Border bawah */
        }

        /* Menambahkan background abu pada header tabel */
        #modifier-sales thead th {
            background-color: #f2f2f2;
            /* Warna abu */
        }

        /* Menambahkan border vertikal di kanan kolom discount sales */
        #discount-sales td:first-child {
            border-right: 1px solid #ccc;
            /* Border kanan */
        }

        /* Menambahkan border horizontal atas dan bawah di baris Total */
        #discount-sales tr:last-child {
            border-top: 2px solid #000;
            /* Border atas */
            border-bottom: 2px solid #000;
            /* Border bawah */
        }

        /* Menambahkan background abu pada header tabel */
        #discount-sales thead th {
            background-color: #f2f2f2;
            /* Warna abu */
        }

        .nav-pills.nav-primary .nav-link.active {
            background: #d03c3c;
            border: 1px solid #d03c3c
        }

        .text-col-right {
            text-align: right;
        }

        /* basis tombol modern */
        .btn-modern {
            border: none !important;
            border-radius: 10px !important;
            padding: 10px 14px !important;
            font-weight: 600 !important;
            box-shadow: 0 6px 14px rgba(0, 0, 0, .08);
            transition: transform .12s ease, box-shadow .12s ease, opacity .12s ease;
            color: #fff !important;
            background-color: #d03c3c !important;
        }

        .btn-modern:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 18px rgba(0, 0, 0, .12);
        }

        /* varian warna */
        .btn-excel {
            background: #22c55e;
            color: #fff;
        }

        .btn-csv {
            background: #3b82f6;
            color: #fff;
        }

        .btn-print {
            background: #111827;
            color: #fff;
        }

        /* icon optional (pakai Material Design Icons / FontAwesome kalau ada) */
        .btn-modern .mdi {
            margin-right: 6px;
            font-size: 18px;
            vertical-align: -2px;
        }

        /* pastikan tombol berada “di atas” header scroll */
        .dataTables_wrapper .dt-buttons {
            position: relative;
            z-index: 5;
        }
    </style>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/4.3.0/css/fixedColumns.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

    <div class="main-content">
        <div class="card text-center">
            <h5 class="card-header">Sales</h5>
        </div>

        <div class="card mt-4">
            <div class="card-header ">
                <div class="row">
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
                            <a class="nav-link" id="payment-method-merchant-tab-nobd" data-bs-toggle="pill"
                                href="#payment-method-merchant-nobd" role="tab" aria-controls="payment-method-merchant-nobd"
                                aria-selected="false">Payment Method (Merchant)</a>
                            <a class="nav-link" id="sales-type-tab-nobd" data-bs-toggle="pill" href="#sales-type-nobd"
                                role="tab" aria-controls="sales-type-nobd" aria-selected="false">Sales Type</a>
                            <a class="nav-link" id="item-sales-tab-nobd" data-bs-toggle="pill" href="#item-sales-nobd"
                                role="tab" aria-controls="item-sales-nobd" aria-selected="false">Item Sales</a>
                            <a class="nav-link" id="category-sales-tab-nobd" data-bs-toggle="pill"
                                href="#category-sales-nobd" role="tab" aria-controls="category-sales-nobd"
                                aria-selected="false">Category Sales</a>
                            <a class="nav-link" id="modifier-sales-tab-nobd" data-bs-toggle="pill"
                                href="#modifier-sales-nobd" role="tab" aria-controls="modifier-sales-nobd"
                                aria-selected="false">Modifier Sales</a>
                            <a class="nav-link" id="discount-tab-nobd" data-bs-toggle="pill" href="#discount-nobd"
                                role="tab" aria-controls="discount-nobd" aria-selected="false">Discounts</a>
                            <a class="nav-link" id="taxes-tab-nobd" data-bs-toggle="pill" href="#taxes-nobd" role="tab"
                                aria-controls="taxes-nobd" aria-selected="false">Taxes</a>
                            <a class="nav-link" id="collected-by-tab-nobd" data-bs-toggle="pill"
                                href="#collected-by-nobd" role="tab" aria-controls="collected-by-nobd"
                                aria-selected="false">Collected By</a>
                        </div>
                    </div>
                    <div class="col-9">
                        <div class="tab-content" id="v-pills-tabContent">
                            <div class="tab-pane fade show active" id="sales-summary-nobd" role="tabpanel"
                                aria-labelledby="sales-summary-tab-nobd">
                                <!-- contoh tombol -->
                                <button id="btn-export-sales-summary" class="btn btn-sm btn-success btn-modern"
                                    style="margin-left: -5px; margin-bottom: 10px;">
                                    Export Excel
                                </button>

                                <div class="container">
                                    <div class="row" style="background-color: #ccc; height: 50px; margin-bottom: 5px;">
                                    </div>
                                    <div class="row ">
                                        <div class="col-6">
                                            Gross Sales
                                        </div>
                                        <div class="col-6  justify-content-end d-flex" id="gross-sales-summary">
                                            Rp. 0
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-6">
                                            Discounts
                                        </div>
                                        <div class=" justify-content-end d-flex col-6" id="discount-sales-summary">
                                            Rp. 0
                                        </div>
                                    </div>
                                    {{-- <hr> --}}
                                    {{-- <div class="row">
                                        <div class="col-6">
                                            Refunds
                                        </div>
                                        <div class=" justify-content-end d-flex col-6">
                                            Rp. 0
                                        </div>
                                    </div> --}}
                                    <hr style="border: 2px solid black;">
                                    <div class="row">
                                        <div class="col-6">
                                            <strong>Net Sales</strong>
                                        </div>
                                        <div class="col-6 justify-content-end d-flex ">
                                            <strong id="net-sales-summary">Rp. 0</strong>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-6">
                                            Tax
                                        </div>
                                        <div class=" justify-content-end d-flex col-6" id="tax-sales-summary">
                                            Rp. 0
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-6">
                                            Rounding
                                        </div>
                                        <div class=" justify-content-end d-flex col-6" id="rounding-sales-summary">
                                            Rp. 0
                                        </div>
                                    </div>
                                    <hr style="border: 2px solid black;">
                                    <div class="row">
                                        <div class="col-6">
                                            <strong>Total Collected</strong>
                                        </div>
                                        <div class="col-6 justify-content-end d-flex ">
                                            <strong id="total-collected-sales-summary">Rp. 0</strong>
                                        </div>
                                    </div>
                                    <hr style="border: 2px solid black;">
                                </div>
                            </div>
                            <div class="tab-pane fade" id="gross-profit-nobd" role="tabpanel"
                                aria-labelledby="gross-profit-tab-nobd">
                                <div class="alert alert-primary" role="alert">
                                    <p><b>Gross Profit</b></p>
                                    Gross Profit is your Net sales minus cost of Goods Sold (COGS). <b>To Report Gross
                                        Profit Accuratel, please make sure all items have a COGS</b>
                                </div>

                                <div class="container">
                                    <div class="row" style="background-color: #ccc; height: 50px; margin-bottom: 5px;">
                                    </div>
                                    <div class="row ">
                                        <div class="col-6">
                                            Gross Sales
                                        </div>
                                        <div class="col-6  justify-content-end d-flex" id="gross-sales-gross-profit">
                                            Rp. 0
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-6">
                                            Discounts
                                        </div>
                                        <div class=" justify-content-end d-flex col-6" id="discount-gross-profit">
                                            Rp. 0
                                        </div>
                                    </div>
                                    <hr style="border: 2px solid black;">
                                    <div class="row">
                                        <div class="col-6">
                                            <strong>Net Sales</strong>
                                        </div>
                                        <div class="col-6 justify-content-end d-flex ">
                                            <strong id="net-sales-gross-profit">Rp. 0 <span
                                                    class="badge badge-success">100%</span></strong>
                                        </div>

                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-6">
                                            <strong>Cost Of Goods Sold (COGS)</strong>
                                        </div>
                                        <div class="col-6 justify-content-end d-flex ">
                                            <strong id="cogs-gross-profit">Rp. 0 <span
                                                    class="badge badge-danger">0%</span></strong>
                                        </div>
                                    </div>
                                    <hr style="border: 2px solid black;">
                                    <div class="row">
                                        <div class="col-6">
                                            <strong>Gross Profit</strong>
                                        </div>
                                        <div class="col-6 justify-content-end d-flex ">
                                            <strong id="gross-profit">Rp. 0 <span
                                                    class="badge badge-success">100%</span></strong>
                                        </div>
                                    </div>
                                    <hr style="border: 2px solid black;">
                                </div>
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
                            <div class="tab-pane fade" id="payment-method-merchant-nobd" role="tabpanel"
                                aria-labelledby="payment-method-merchant-tab-nobd">
                                <table id="payment-method-merchant-table" class="table">
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
                                <table id="sales-type" class="display table table-bordered" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>Sales Type</th>
                                            <th>Count</th>
                                            <th>Total Collected</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                            <div class="tab-pane fade" id="item-sales-nobd" role="tabpanel"
                                aria-labelledby="item-sales-tab-nobd">
                                <table id="item-sales" class="table display row-border order-column " style="width:100%">
                                    <thead id="head-item-sales">
                                        <tr>
                                            <th style="border-right: #000; border-radius: 2px"><b>Name</b></th>
                                            <th><b>Category</b></th>
                                            <th><b>Item Sold</b></th>
                                            <th><b>Gross Sales</b></th>
                                            <th><b>Discounts</b></th>
                                            <th><b>Net Sales</b></th>
                                            <th><b>Gross Profit</b></th>
                                            <th><b>Gross Margin</b></th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th colspan="1">Total</th>
                                            <th></th>
                                            <th></th>
                                            <th></th>
                                            <th></th>
                                            <th></th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <div class="tab-pane fade" id="category-sales-nobd" role="tabpanel"
                                aria-labelledby="category-sales-tab-nobd">
                                <div class="alert alert-primary" role="alert">
                                    <p><b>Gross Profit</b></p>
                                    To Report Gross
                                    Profit Accurately, please make sure all items have a COGS
                                </div>

                                <table id="category-sales" class="table display row-border order-column"
                                    style="width:100%">
                                    <thead>
                                        <tr>
                                            <th style="border-right: #000; border-radius: 2px"><b>Category</b></th>
                                            <th><b>Item Sold</b></th>
                                            <th><b>Gross Sales</b></th>
                                            <th><b>Discounts</b></th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th colspan="1">Total</th>
                                            <th></th>
                                            <th></th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <div class="tab-pane fade" id="modifier-sales-nobd" role="tabpanel"
                                aria-labelledby="modifier-sales-tab-nobd">
                                <table id="modifier-sales" class="table display row-border order-column"
                                    style="width:100%">
                                    <thead>
                                        <tr>
                                            <th style="border-right: #000; border-radius: 2px"><b>Name</b></th>
                                            <th><b>Quantity Sold</b></th>
                                            <th><b>Gross Sales</b></th>
                                            <th><b>Discounts</b></th>
                                            <th><b>Net Sales</b></th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th colspan="1">Total</th>
                                            <th></th>
                                            <th></th>
                                            <th></th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <div class="tab-pane fade" id="discount-nobd" role="tabpanel"
                                aria-labelledby="discount-tab-nobd">
                                <table id="discount-sales" class="table display row-border order-column"
                                    style="width:100%">
                                    <thead>
                                        <tr>
                                            <th style="border-right: #000; border-radius: 2px"><b>Name</b></th>
                                            <th><b>Discount Amount</b></th>
                                            <th><b>Count</b></th>
                                            <th><b>Discount Total</b></th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th colspan="1">Total</th>
                                            <th></th>
                                            <th></th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <div class="tab-pane fade" id="taxes-nobd" role="tabpanel" aria-labelledby="taxes-tab-nobd">
                                <table id="tax-sales" class="table display row-border order-column" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th style="border-right: #000; border-radius: 2px"><b>Name</b></th>
                                            <th><b>Tax Rate</b></th>
                                            <th><b>Taxable Amount</b></th>
                                            <th><b>Tax Collected</b></th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th colspan="1">Total</th>
                                            <th></th>
                                            <th></th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <div class="tab-pane fade" id="collected-by-nobd" role="tabpanel"
                                aria-labelledby="collected-by-tab-nobd">
                                <table id="collected-by-sales" class="table display row-border order-column"
                                    style="width:100%">
                                    <thead>
                                        <tr>
                                            <th style="border-right: #000; border-radius: 2px"><b>Name</b></th>
                                            <th><b>Title</b></th>
                                            <th><b>Number Of Tranasctions</b></th>
                                            <th><b>Total Collected</b></th>
                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th colspan="1">Total</th>
                                            <th></th>
                                            <th></th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal_detail" tabindex="-1" role="dialog" aria-hidden="true">
    </div>


    @push('js')
        <script src="https://cdn.datatables.net/2.2.1/js/dataTables.js"></script>
        <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

        <!-- FixedColumns 4.x (untuk DT 1.x) -->
        <script src="https://cdn.datatables.net/fixedcolumns/4.3.0/js/dataTables.fixedColumns.min.js"></script>

        {{-- <script src="https://cdn.datatables.net/fixedcolumns/5.0.4/js/dataTables.fixedColumns.js"></script>
        <script src="https://cdn.datatables.net/fixedcolumns/5.0.4/js/fixedColumns.dataTables.js"></script> --}}


        <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
        <script>
            var outlet = $('#filter-outlet').val();
            var date = $('#date_range_transaction').val();
            function checkActiveTab() {
                var activeTab = $('a.nav-link.active').attr('href');
                console.log('Active Tab:', activeTab);

                outlet = $('#filter-outlet').val();
                date = $('#date_range_transaction').val();

                console.log(date)

                if (activeTab === '#sales-summary-nobd') {
                    // Logika untuk Sales Summary
                    $.ajax({
                        url: '{{ route('report/sales/getSalesSummary') }}',
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
                            console.log(data);

                            $('#gross-sales-summary').text(formatRupiah(data.grossSales.toString(), 'Rp. '));
                            $('#discount-sales-summary').text(formatRupiah(data.discount.toString(), 'Rp. '));
                            $('#net-sales-summary').text(formatRupiah(data.netSales.toString(), 'Rp. '));
                            $('#tax-sales-summary').text(formatRupiah(data.tax.toString(), 'Rp. '));
                            $('#rounding-sales-summary').text(formatRupiah(data.rounding.toString(), 'Rp. '));
                            $('#total-collected-sales-summary').text(formatRupiah(data.totalCollect.toString(),
                                'Rp. '));

                        },
                        error: function(xhr) {
                            console.error(xhr);
                        }
                    });
                } else if (activeTab === '#gross-profit-nobd') {
                    $.ajax({
                        url: '{{ route('report/sales/getGrossProfit') }}',
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
                            console.log(data);

                            $('#gross-sales-gross-profit').text(formatRupiah(data.grossSales.toString(), 'Rp. '));
                            $('#discount-gross-profit').text(formatRupiah(data.discount.toString(), 'Rp. '));
                            $('#net-sales-gross-profit').html(formatRupiah(data.netSales.toString(), 'Rp. ') + ' ' +
                                '<span class="badge badge-success">100%</span>');
                            $('#gross-profit').html(formatRupiah(data.netSales.toString(), 'Rp. ') + ' ' +
                                '<span class="badge badge-success">100%</span>');
                        },
                        error: function(xhr) {
                            console.error(xhr);
                        }
                    });

                } else if (activeTab === '#payment-method-nobd') {
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
                } else if (activeTab === '#payment-method-merchant-nobd') {
                    $.ajax({
                        url: '{{ route('report/sales/getPaymentMerchantMethodSales') }}',
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
                            var tbody = $('#payment-method-merchant-table tbody');
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
                } else if (activeTab === '#sales-type-nobd') {
                    // Hancurkan instance DataTable jika sudah ada
                    if ($.fn.dataTable.isDataTable('#sales-type')) {
                        $('#sales-type').DataTable().destroy();
                    }

                    var tableSales = $('#sales-type').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: '{{ route('report/sales/getSalesType') }}', // Make sure this URL matches your Laravel route
                            type: 'GET',
                            data: {
                                date: date,
                                outlet: outlet
                            },
                        },
                        columns: [{
                                data: 'sales_type',
                                name: 'sales_type'
                            },
                            {
                                data: 'count',
                                name: 'count'
                            },
                            {
                                data: 'total_collected',
                                name: 'total_collected',
                                className: 'text-col-right'
                            }
                        ],
                        paging: false, // Menghilangkan pagination
                        searching: false, // Menghilangkan search bar
                        ordering: false,
                        initComplete: function(settings, json) {
                            var totalTransaction = 0;
                            console.log(json);
                            json.data.forEach(function(item) {
                                item.item_transaction.forEach(function(transactionItem) {
                                    totalTransaction += transactionItem.variant.harga;
                                })
                            });
                            console.log(totalTransaction);
                            // Menambahkan baris kustom setelah semua data dimuat
                            var customRow = {
                                sales_type: "Total Collected",
                                count: '',
                                total_collected: formatRupiah(totalTransaction.toString(), "Rp. ")
                            };

                            $(customRow).addClass('border-top');

                            tableSales.row.add(customRow).draw(true); // Menambahkan baris kustom
                        }
                    });
                } else if (activeTab === "#item-sales-nobd") {
                    if ($.fn.dataTable.isDataTable('#item-sales')) {
                        $('#item-sales').DataTable().destroy();

                        // kalau sebelumnya kamu pernah menambah baris header tambahan, bersihkan:
                        $('#item-sales').find('thead tr:gt(0)').remove(); // sisakan hanya baris header pertama
                    }

                    var tableSales = $('#item-sales').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: '{{ route('report/sales/getItemSales') }}', // Make sure this URL matches your Laravel route
                            type: 'GET',
                            data: {
                                date: date,
                                outlet: outlet
                            },
                        },
                        columns: [{
                                data: 'name',
                                name: 'name'
                            },
                            {
                                data: 'category',
                                name: 'category'
                            },
                            {
                                data: 'item_sold',
                                name: 'item_sold',
                            },
                            {
                                data: 'gross_sales',
                                name: 'gross_sales',
                            },
                            {
                                data: 'discounts',
                                name: 'discounts',
                            },
                            {
                                data: 'net_sales',
                                name: 'net_sales',
                            },
                            {
                                data: 'gross_profit',
                                name: 'gross_profit',
                            },
                            {
                                data: 'gross_margin',
                                name: 'gross_margin',
                            }
                        ],
                        paging: false, // Menghilangkan pagination
                        searching: true, // Menghilangkan search bar
                        ordering: true,
                        orderMulti: true,
                        scrollCollapse: true,
                        scrollX: true,
                        scrollY: 500,
                        autoWidth: false,
                        info: true,
                        dom: 'Bfrtip',
                        buttons: [{
                                extend: 'excelHtml5',
                                text: '<span class="mdi mdi-file-excel"></span> Export Excel',
                                className: 'btn btn-modern btn-excel',
                                title: 'Item Sales Report', // judul di file
                                filename: 'item_sales_report', // nama file
                                sheetName: 'Item Sales', // nama sheet (Excel)
                                exportOptions: {
                                    columns: [0, 1, 2, 3, 4, 5, 6]
                                } // misal kolom ke 0,1,3,5
                            },
                            // {
                            //     extend: 'csvHtml5',
                            //     title: 'Item Sales Report',
                            //     exportOptions: {
                            //         columns: ':visible'
                            //     }
                            // }
                        ],
                        fixedHeader: false,
                        fixedColumns: {
                            start: 1,
                            end: 0
                        },
                        columnDefs: [
                            // Kolom Name (index 0): buat lebih lebar
                            {
                                targets: 0,
                                width: 320
                            }, // atau '320px'
                            // Kolom angka: width tetap agar Name bisa “mencuri” ruang
                            {
                                targets: [3, 4, 5, 6],
                                width: 120,
                                className: 'text-end'
                            }
                        ],
                        colResize: {
                            saveState: true,
                            hasBoundCheck: true
                        },
                        footerCallback: function(row, data, start, end, display) {
                            var api = this.api();

                            // Menghitung total untuk setiap kolom yang diinginkan
                            var totalItemSold = api.column(2).data().reduce(function(a, b) {
                                return parseInt(a) + parseInt(b);
                                // return getAmount(a.toString() + getAmount(b.toString()))
                            }, 0);

                            var totalGrossSales = api.column(3).data().reduce(function(a, b) {
                                // return parseFloat(a) + parseFloat(b);
                                return getAmount(a.toString()) + getAmount(b.toString());
                            }, 0);

                            var totalDiscounts = api.column(4).data().reduce(function(a, b) {
                                // return parseFloat(a) + parseFloat(b);
                                return getAmount(a.toString()) + getAmount(b.toString());
                            }, 0);

                            var totalNetSales = api.column(5).data().reduce(function(a, b) {
                                // return parseFloat(a) + parseFloat(b);
                                return getAmount(a.toString()) + getAmount(b.toString());
                            }, 0);

                            var totalGrossProfit = api.column(6).data().reduce(function(a, b) {
                                // return parseFloat(a) + parseFloat(b);
                                return getAmount(a.toString()) + getAmount(b.toString());
                            }, 0);

                            // Menampilkan total di footer
                            $(api.column(2).footer()).html(totalItemSold);
                            $(api.column(3).footer()).html(formatRupiah(totalGrossSales.toString(), "Rp. "));
                            $(api.column(4).footer()).html(formatRupiah(totalDiscounts.toString(), "Rp. "));
                            $(api.column(5).footer()).html(formatRupiah(totalNetSales.toString(), "Rp. "));
                            $(api.column(6).footer()).html(formatRupiah(totalGrossProfit.toString(), "Rp. "));
                        },
                        initComplete: function(setting, json) {
                            $('.dt-scroll-body table thead').remove();
                            $('.dt-scroll-body table tfoot').remove();
                        },
                    });

                    // Pastikan untuk menambahkan elemen <tfoot> di HTML Anda
                    $('#item-sales tfoot').append(`
                        <tr>
                            <th colspan="1">Total</th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                    `);

                    // Event handler untuk menghapus header dan footer setiap kali tabel di redraw
                    tableSales.on('draw', function() {
                        // Cek apakah input kosong atau tidak
                        if ($('.dt-input').val() !== '') {
                            $('.dt-scroll-head').addClass('d-none');
                            $('.dt-scroll-foot').addClass('d-none');
                        }
                    });

                    // setiap kali tabel redraw / re-size / re-order, hide lagi header body
                    tableSales.on('draw.dt order.dt column-sizing.dt', function() {
                        $('.dt-scroll-body table thead').remove();
                    });

                    // Custom search function hanya untuk kolom name dan category
                    $.fn.dataTable.ext.search.push(
                        function(settings, data, dataIndex) {
                            var searchTerm = $('.dataTables_filter input').val().toLowerCase();
                            var name = data[0].toLowerCase(); // Kolom name
                            var category = data[1].toLowerCase(); // Kolom category

                            // Jika search term ada di name atau category, tampilkan baris
                            if (name.includes(searchTerm) || category.includes(searchTerm)) {
                                return true;
                            }
                            return false;
                        }
                    );

                    $('#item-sales tbody').off().on('click', 'tr', function() {
                        var data = tableSales.row(this).data();
                        console.log(data);
                        $.ajax({
                            url: '{{ route('report/sales/getDetailItemSales') }}',
                            method: 'GET',
                            data: {
                                date: date,
                                outlet: outlet,
                                idProduct: data.product_id,
                                idVariant: data.id,
                                nameProductVariant: data.name
                            },
                            beforeSend: function() {
                                showLoader();
                            },
                            complete: function() {
                                showLoader(false);
                            },
                            success: function(data) {
                                const modal = $('#modal_detail');
                                
                                modal.html(data);
                                modal.modal('show');

                            },
                            error: function(xhr) {
                                console.error(xhr);
                            }
                        });
                    });

                } else if (activeTab === "#category-sales-nobd") {
                    if ($.fn.dataTable.isDataTable('#category-sales')) {
                        $('#category-sales').DataTable().destroy();
                    }

                    var categorySales = $('#category-sales').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: '{{ route('report/sales/getCategorySales') }}', // Make sure this URL matches your Laravel route
                            type: 'GET',
                            data: {
                                date: date,
                                outlet: outlet
                            },
                        },
                        columns: [{
                                data: 'category',
                                name: 'category'
                            },
                            {
                                data: 'item_sold',
                                name: 'item_sold'
                            },
                            {
                                data: 'gross_sales',
                                name: 'gross_sales',
                            },
                            {
                                data: 'discounts',
                                name: 'discounts',
                            },
                        ],
                        paging: false, // Menghilangkan pagination
                        searching: false, // Menghilangkan search bar
                        ordering: true,
                        scrollX: true,
                        scrollCollapse: true,
                        scrollY: 500,
                        fixedColumns: {
                            start: 1,
                        },
                        dom: 'Bfrtip',
                        buttons: [{
                            extend: 'excelHtml5',
                            text: '<span class="mdi mdi-file-excel"></span> Export Excel',
                            className: 'btn btn-modern btn-excel',
                            title: 'Category Sales Report', // judul di file
                            filename: 'category_sales_report', // nama file
                            sheetName: 'Category Sales', // nama sheet (Excel)
                            exportOptions: {
                                columns: ':visible'
                            } // misal kolom ke 0,1,3,5
                        }, ],
                        columnDefs: [{
                                targets: 0,
                                width: '200px'
                            } // Menetapkan lebar kolom pertama menjadi 200px
                        ],
                        initComplete: function(setting, json) {
                            console.log(json)
                            $('.dt-scroll-body table thead').remove();
                            $('.dt-scroll-body table tfoot').remove();
                        },
                        footerCallback: function(row, data, start, end, display) {
                            var api = this.api();

                            // Menghitung total untuk setiap kolom yang diinginkan
                            var totalItemSold = api.column(1).data().reduce(function(a, b) {
                                return parseInt(a) + parseInt(b);
                            }, 0);

                            var totalGrossSales = api.column(2).data().reduce(function(a, b) {
                                // return parseFloat(a) + parseFloat(b);
                                return getAmount(a.toString()) + getAmount(b.toString());
                            }, 0);

                            var totalDiscounts = api.column(3).data().reduce(function(a, b) {
                                // return parseFloat(a) + parseFloat(b);
                                return getAmount(a.toString()) + getAmount(b.toString());
                            }, 0);


                            // Menampilkan total di footer
                            $(api.column(1).footer()).html(totalItemSold);
                            $(api.column(2).footer()).html(formatRupiah(totalGrossSales.toString(), "Rp. "));
                            $(api.column(3).footer()).html(formatRupiah(totalDiscounts.toString(), "Rp. "));
                        }
                    });

                    // Pastikan untuk menambahkan elemen <tfoot> di HTML Anda
                    $('#category-sales tfoot').append(`
                        <tr>
                            <th colspan="1">Total</th>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                    `);

                    $('#category-sales tbody').off().on('click', 'tr', function() {
                        var data = categorySales.row(this).data();
                        console.log(data);
                        $.ajax({
                            url: '{{ route('report/sales/getDetailItemCategorySales') }}',
                            method: 'GET',
                            data: {
                                date: date,
                                outlet: outlet,
                                idCategory: data.id
                            },
                            beforeSend: function() {
                                showLoader();
                            },
                            complete: function() {
                                showLoader(false);
                            },
                            success: function(data) {
                                const modal = $('#modal_detail');
                                modal.html(data);
                                modal.modal('show');

                            },
                            error: function(xhr) {
                                console.error(xhr);
                            }
                        });
                    });
                } else if (activeTab === "#modifier-sales-nobd") {
                    if ($.fn.dataTable.isDataTable('#modifier-sales')) {
                        $('#modifier-sales').DataTable().destroy();
                    }

                    var modifierSales = $('#modifier-sales').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: '{{ route('report/sales/getModifierSales') }}', // Make sure this URL matches your Laravel route
                            type: 'GET',
                            data: {
                                date: date,
                                outlet: outlet
                            },
                        },
                        columns: [{
                                data: 'name',
                                name: 'name'
                            },
                            {
                                data: 'quantity_sold',
                                name: 'quantity_sold'
                            },
                            {
                                data: 'gross_sales',
                                name: 'gross_sales',
                            },
                            {
                                data: 'discounts',
                                name: 'discounts',
                            },
                            {
                                data: 'net_sales',
                                name: 'net_sales',
                            },
                        ],
                        paging: false, // Menghilangkan pagination
                        searching: false, // Menghilangkan search bar
                        ordering: true,
                        scrollX: true,
                        scrollCollapse: true,
                        scrollY: 500,
                        dom: 'Bfrtip',
                        buttons: [{
                            extend: 'excelHtml5',
                            text: '<span class="mdi mdi-file-excel"></span> Export Excel',
                            className: 'btn btn-modern btn-excel',
                            title: 'Category Sales Report', // judul di file
                            filename: 'category_sales_report', // nama file
                            sheetName: 'Category Sales', // nama sheet (Excel)
                            exportOptions: {
                                columns: ':visible'
                            } // misal kolom ke 0,1,3,5
                        }, ],
                        fixedColumns: {
                            start: 1,
                        },
                        columnDefs: [{
                                targets: 0,
                                width: '200px'
                            } // Menetapkan lebar kolom pertama menjadi 200px
                        ],
                        initComplete: function(setting, json) {
                            $('.dt-scroll-body table thead').remove();
                            $('.dt-scroll-body table tfoot').remove();
                        },
                        footerCallback: function(row, data, start, end, display) {
                            var api = this.api();

                            var totalItemSold = 0;
                            var totalGrossSales = 0;
                            var totalDiscounts = 0;
                            var totalNetSales = 0;
                            data.forEach(function(item, index) {
                                console.log(item)
                                if (data[index][6]) {
                                    totalItemSold += item[2];
                                    totalGrossSales += item[3];
                                    totalDiscounts += item[4];
                                    totalNetSales += item[5];
                                }
                            })

                            // Menampilkan total di footer
                            $(api.column(1).footer()).html(totalItemSold);
                            $(api.column(2).footer()).html(formatRupiah(totalGrossSales.toString(), "Rp. "));
                            $(api.column(3).footer()).html(formatRupiah(totalDiscounts.toString(), "Rp. "));
                            $(api.column(4).footer()).html(formatRupiah(totalNetSales.toString(), "Rp. "));
                        }
                    });

                    // Pastikan untuk menambahkan elemen <tfoot> di HTML Anda
                    $('#modifier-sales tfoot').append(`
                        <tr>
                            <th colspan="1">Total</th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                    `);
                } else if (activeTab === "#discount-nobd") {
                    if ($.fn.dataTable.isDataTable('#discount-sales')) {
                        $('#discount-sales').DataTable().destroy();
                    }

                    var discountSales = $('#discount-sales').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: '{{ route('report/sales/getDiscountSales') }}', // Make sure this URL matches your Laravel route
                            type: 'GET',
                            data: {
                                date: date,
                                outlet: outlet
                            },
                        },
                        columns: [{
                                data: 'name',
                                name: 'name'
                            },
                            {
                                data: 'discount_amount',
                                name: 'discount_amount'
                            },
                            {
                                data: 'count',
                                name: 'count',
                            },
                            {
                                data: 'discount_total',
                                name: 'discount_total',
                            },
                        ],
                        paging: false, // Menghilangkan pagination
                        searching: true, // Menghilangkan search bar
                        ordering: true,
                        scrollX: true,
                        scrollCollapse: true,
                        scrollY: 500,
                        fixedColumns: {
                            start: 1,
                        },
                        dom: 'Bfrtip',
                        buttons: [{
                            extend: 'excelHtml5',
                            text: '<span class="mdi mdi-file-excel"></span> Export Excel',
                            className: 'btn btn-modern btn-excel',
                            title: 'Category Sales Report', // judul di file
                            filename: 'category_sales_report', // nama file
                            sheetName: 'Category Sales', // nama sheet (Excel)
                            exportOptions: {
                                columns: ':visible'
                            } // misal kolom ke 0,1,3,5
                        }, ],
                        columnDefs: [{
                                targets: 0,
                                width: '200px'
                            } // Menetapkan lebar kolom pertama menjadi 200px
                        ],
                        initComplete: function(setting, json) {
                            $('.dt-scroll-body table thead').remove();
                            $('.dt-scroll-body table tfoot').remove();
                        },
                        footerCallback: function(row, data, start, end, display) {
                            var api = this.api();

                            var count = 0;
                            var totalDiscounts = 0;
                            data.forEach(function(item, index) {
                                count += item.count;
                                totalDiscounts += item.total_discount;
                            });

                            // Menampilkan total di footer
                            $(api.column(2).footer()).html(count);
                            $(api.column(3).footer()).html(formatRupiah(totalDiscounts.toString(), "Rp. "));
                        }
                    });

                    // Pastikan untuk menambahkan elemen <tfoot> di HTML Anda
                    $('#discount-sales tfoot').append(`
                        <tr>
                            <th colspan="2">Total</th>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                    `);
                } else if (activeTab === "#taxes-nobd") {
                    if ($.fn.dataTable.isDataTable('#tax-sales')) {
                        $('#tax-sales').DataTable().destroy();
                    }

                    var modifierSales = $('#tax-sales').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: '{{ route('report/sales/getTaxSales') }}', // Make sure this URL matches your Laravel route
                            type: 'GET',
                            data: {
                                date: date,
                                outlet: outlet
                            },
                        },
                        columns: [{
                                data: 'name',
                                name: 'name'
                            },
                            {
                                data: 'tax_rate',
                                name: 'tax_rate'
                            },
                            {
                                data: 'taxable_amount',
                                name: 'taxable_amount',
                            },
                            {
                                data: 'tax_collected',
                                name: 'tax_collected',
                            },
                        ],
                        paging: false, // Menghilangkan pagination
                        searching: false, // Menghilangkan search bar
                        ordering: true,
                        scrollX: true,
                        scrollCollapse: true,
                        scrollY: 500,
                        fixedColumns: {
                            start: 1,
                        },
                        dom: 'Bfrtip',
                        buttons: [{
                            extend: 'excelHtml5',
                            text: '<span class="mdi mdi-file-excel"></span> Export Excel',
                            className: 'btn btn-modern btn-excel',
                            title: 'Category Sales Report', // judul di file
                            filename: 'category_sales_report', // nama file
                            sheetName: 'Category Sales', // nama sheet (Excel)
                            exportOptions: {
                                columns: ':visible'
                            } // misal kolom ke 0,1,3,5
                        }, ],
                        columnDefs: [{
                                targets: 0,
                                width: '200px'
                            } // Menetapkan lebar kolom pertama menjadi 200px
                        ],
                        initComplete: function(setting, json) {
                            $('.dt-scroll-body table thead').remove();
                            $('.dt-scroll-body table tfoot').remove();
                        },
                        footerCallback: function(row, data, start, end, display) {
                            var api = this.api();

                            var totalTaxableAmount = 0;
                            var totalTaxCollected = 0;
                            console.log(data);
                            data.forEach(function(item, index) {
                                totalTaxableAmount += getAmount(item.taxable_amount);
                                totalTaxCollected += getAmount(item.tax_collected);
                            });

                            // Menampilkan total di footer
                            $(api.column(2).footer()).html(formatRupiah(totalTaxableAmount.toString(), "Rp. "));
                            $(api.column(3).footer()).html(formatRupiah(totalTaxCollected.toString(), "Rp. "));
                        }
                    });

                    // Pastikan untuk menambahkan elemen <tfoot> di HTML Anda
                    $('#tax-sales tfoot').append(`
                        <tr>
                            <th colspan="2">Total</th>
                            <th></th>
                            <th></th>
                        </tr>
                    `);

                } else if (activeTab === "#collected-by-nobd") {
                    if ($.fn.dataTable.isDataTable('#collected-by-sales')) {
                        $('#collected-by-sales').DataTable().destroy();
                    }

                    var collectedBySales = $('#collected-by-sales').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: '{{ route('report/sales/getCollectedBySales') }}', // Make sure this URL matches your Laravel route
                            type: 'GET',
                            data: {
                                date: date,
                                outlet: outlet
                            },
                        },
                        columns: [{
                                data: 'name',
                                name: 'name'
                            },
                            {
                                data: 'title',
                                name: 'title'
                            },
                            {
                                data: 'number_of_transaction',
                                name: 'number_of_transaction',
                            },
                            {
                                data: 'total_collected',
                                name: 'total_collected',
                            },
                        ],
                        paging: false, // Menghilangkan pagination
                        searching: false, // Menghilangkan search bar
                        ordering: true,
                        scrollX: true,
                        scrollCollapse: true,
                        scrollY: 500,
                        fixedColumns: {
                            start: 1,
                        },
                        dom: 'Bfrtip',
                        buttons: [{
                            extend: 'excelHtml5',
                            text: '<span class="mdi mdi-file-excel"></span> Export Excel',
                            className: 'btn btn-modern btn-excel',
                            title: 'Category Sales Report', // judul di file
                            filename: 'category_sales_report', // nama file
                            sheetName: 'Category Sales', // nama sheet (Excel)
                            exportOptions: {
                                columns: ':visible'
                            } // misal kolom ke 0,1,3,5
                        }, ],
                        columnDefs: [{
                                targets: 0,
                                width: '200px'
                            } // Menetapkan lebar kolom pertama menjadi 200px
                        ],
                        initComplete: function(setting, json) {
                            $('.dt-scroll-body table thead').remove();
                            $('.dt-scroll-body table tfoot').remove();
                        },
                        footerCallback: function(row, data, start, end, display) {
                            var api = this.api();

                            var totalNumberTranasction = 0;
                            var totalCollected = 0;
                            data.forEach(function(item, index) {
                                totalNumberTranasction += item.number_of_transaction;
                                totalCollected += getAmount(item.total_collected);
                            });

                            // Menampilkan total di footer
                            $(api.column(2).footer()).html(totalNumberTranasction);
                            $(api.column(3).footer()).html(formatRupiah(totalCollected.toString(), "Rp. "));
                        }
                    });

                    // Pastikan untuk menambahkan elemen <tfoot> di HTML Anda
                    $('#collected-by-sales tfoot').append(`
                        <tr>
                            <th colspan="2">Total</th>
                            <th></th>
                            <th></th>
                        </tr>
                    `);

                }
                // Tambahkan logika untuk tab lainnya
            }

            $(document).ready(function() {
                $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function() {
                    checkActiveTab();
                });

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

                outlet = $('#filter-outlet').val();
                date = $('#date_range_transaction').val();

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

                $(document).on('click', '#btn-export-sales-summary', function() {
                    // cukup redirect GET agar memicu download
                    const url = "{{ route('report/sales/exportSalesSummary') }}";
                    const params = new URLSearchParams({
                        outlet: outlet,
                        date: date
                    });
                    window.location = url + '?' + params.toString();
                });

            });
        </script>
    @endpush
@endsection
