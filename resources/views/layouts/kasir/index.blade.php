<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf_token" content="{{ csrf_token() }}">
    <title>UDJAYA POS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
        integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    {{-- <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet"> --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    {{-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous"> --}}
    <link rel="stylesheet" href="{{ asset('css/iziToast.min.css') }}">

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .product-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            margin-bottom: 15px;
        }

        .product-card img {
            width: 100%;
            height: 100px;
            object-fit: cover;
        }

        .btn-nav {
            width: 100%;
            padding: 15px;
        }

        .btn-lg-custom {
            height: 60px;
        }

        .content-section {
            display: none;
        }

        .content-section.active {
            display: block;
        }

        .order-section {
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 8px;
        }

        .bottom-nav-li {
            height: 55px;
            padding-bottom: 55px !important;
        }

        .icon-box {
            width: 60px;
            height: 60px;
            margin: 10px;
            background-color: #d3d3d3;
            /* Warna latar belakang */
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 14px;
            font-weight: bold;
            color: white;
            text-transform: uppercase;
            border-radius: 4px;
            /* Membuat sudut kotak */
        }

        .bottom-nav {
            display: flex;
            justify-content: space-around;
            align-items: center;
            position: fixed;
            bottom: 0;
            width: 100%;
            background-color: #3b5998;
            /* Biru seperti contoh */
            box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.1);
            padding: 10px 0;
        }

        .bottom-nav .nav-item {
            text-align: center;
            flex: 1;
        }

        .bottom-nav .nav-item a {
            color: #d8d8d8;
            /* Warna teks default */
            text-decoration: none;
            font-size: 14px;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 10px 0;
            transition: all 0.3s;
        }

        .bottom-nav .nav-item a i {
            font-size: 18px;
            margin-bottom: 5px;
        }

        .bottom-nav .nav-item a.active {
            background-color: #2a437a;
            /* Biru gelap untuk aktif */
            border-radius: 20px;
            color: white;
            padding: 8px 15px;
        }

        .bottom-nav .nav-item a.active i {
            color: white;
        }

        .calculator-btn {
            height: 100px;
            font-size: 25px;
            width: 100% !important;
        }

        .calculator-row {
            margin: 0;
        }

        .calculator-btn-footer {
            height: 100px;
            font-size: 25px;
            width: 100% !important;
        }

        .screen {
            height: 90px;
            font-size: 30px;
            background-color: #f8f9fa;
            text-align: right;
            padding-right: 10px;
            line-height: 100px;
            border: 1px solid #ddd;
        }

        .text-muted {
            color: #aeaeae !important;
        }

        .list-setting {
            height: 60px;
        }

        .card.list-setting {
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
        }

        .card.list-setting:hover {
            background-color: #f1f1f1;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            cursor: pointer;
        }

        .hover-effect {
            background-color: #f1f1f1;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        /* Animasi rotasi */
        .spinner {
            /* animation: spin 1s linear infinite; */
            animation: bounce 1.5s infinite;
        }

        /* Latar belakang semi-transparan */
        #preloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            /* Background semi-transparan */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1050;
            /* Tetap di atas elemen lainnya */
        }

        /* Definisi keyframes untuk animasi bouncing */
        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
                /* Posisi awal */
            }

            50% {
                transform: translateY(-55px);
                /* Melompat ke atas */
            }
        }

        .card-custom {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            transition: transform 0.2s;
        }

        .card-custom:hover {
            transform: translateY(-10px);
        }

        .card-custom-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .card-custom-content {
            padding: 16px;
        }

        .card-custom-title {
            font-size: 1.5em;
            margin: 0 0 10px;
        }

        .card-custom-description {
            font-size: 1em;
            color: #666;
            margin-bottom: 20px;
        }

        .card-custom-button {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 1em;
            transition: background-color 0.3s;
        }

        .card-custom-button:hover {
            background-color: #0056b3;
        }

        .card-active {
            background-color: #e7f1ff; /* Contoh warna latar belakang aktif */
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2); /* Efek bayangan lebih kuat */
        }

        .list-product-transaction{
            width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .history-shift-list {
            height: 65vh;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
        }
        .history-shift-item {
            border-bottom: 1px solid #ddd;
            padding: 10px 0;
        }
        .history-shift-item:last-child {
            border-bottom: none;
        }

        .list-sold-item{
            cursor: pointer;
        }

        .btn-xl{
            padding-top: 1.75rem;
            padding-bottom: 1.75rem;
            padding-left: 3.5rem;
            padding-right: 3.5rem;
            font-size: 1.25rem;
            border-radius: 0.5rem;
        }
    </style>
</head>

<body>
    <div class="container-fluid vh-100 d-flex flex-column">

        <!-- Loading Animation -->
        <div id="preloader">
            <img src="{{ asset('img/Logo Red.png') }}" alt="Loading" class="spinner" height="100">
        </div>
        <!-- Main Content -->
        <div id="content-area" class="flex-grow-1">
            <div class="row">
                <div class="col-7">
                    <!-- All Items Section -->
                    <div id="setting" class="content-section">
                        <div class="row vh-100">
                            <div class="col-12 mt-4">

                                <div id="setting-section">
                                    <div class="card">
                                        <div class="card-body d-flex" style="background-color: #0000002d">
                                            <button id="back-btn-setting" class="btn btn-primary"
                                                style="display: none !important;">&larr; Back</button>
                                            <h4 id="text-title-setting" class="ms-2">Setting</h4>
                                        </div>
                                    </div>

                                    <div id="setting-view" class="card mt-2 child-section">
                                        <div class="card-header">
                                            Hi, {{auth()->user()->name}}
                                        </div>
                                        <div class="card-body">
                                            <div class="card list-setting bg-primary" id="shift"
                                                data-target="shift-menu" data-name-section="Shift">
                                                <div class="card-body">
                                                    <h5 class="text-white">Shift</h5>
                                                </div>
                                            </div>

                                            <div class="card list-setting bg-primary mt-2" id="history-shift"
                                                data-target="history-shift-menu" data-name-section="History-Shift">
                                                <div class="card-body">
                                                    <h5 class="text-white">History Shift</h5>
                                                </div>
                                            </div>

                                            <div class="card list-setting bg-primary mt-2" id="activity"
                                                data-target="activity-menu" data-name-section="activity">
                                                <div class="card-body">
                                                    <h5 class="text-white">Activity</h5>
                                                </div>
                                            </div>

                                            <div class="bg-danger card list-setting mt-2" data-target="logout">
                                                <div class="card-body ">
                                                    <h5 class="text-white">Keluar</h5>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card d-none child-section" id="shift-menu" style="margin-bottom: 100px">
                                        <div class="card-body">
                                            <div class="container">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="row">
                                                            <div class="col-6">
                                                                <button id="end-current-shift"
                                                                class="btn btn-outline-primary w-100 btn-lg mb-4">Akhiri Shift</button>
                                                            </div>
                                                            <div class="col-6">
                                                                <a href="javascript:void(0);" class="btn btn-outline-primary w-100 btn-lg mb-4" target="_blank" id="btn-print-shift">Cetak Laporan Shift</a>
                                                            </div>
                                                        </div>
                                                        <div class="container" id="container-shift">
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <h5>Shift Details</h5>
                                                                    <hr>
                                                                    <div class="row">
                                                                        <div class="col-6">
                                                                            Open Shift
                                                                        </div>
                                                                        <div class="col-6" id="txt-open-patty-cash">
                                                                            Ardian
                                                                        </div>
                                                                    </div>
                                                                    <hr>
                                                                    <div class="row">
                                                                        <div class="col-6">
                                                                            Close Shift
                                                                        </div>
                                                                        <div class="col-6" id="txt-close-patty-cash">
                                                                            -
                                                                        </div>
                                                                    </div>
                                                                    <hr>
                                                                    <div class="row">
                                                                        <div class="col-6">
                                                                            Outlet
                                                                        </div>
                                                                        <div class="col-6" id="txt-outlet">
                                                                            Outlet 1
                                                                        </div>
                                                                    </div>
                                                                    <hr>
                                                                    <div class="row">
                                                                        <div class="col-6">
                                                                            Starting Shift
                                                                        </div>
                                                                        <div class="col-6" id="txt-starting-shift">
                                                                            Thursday blablabla
                                                                        </div>
                                                                    </div>
                                                                    <hr>
                                                                    {{-- <div class="row">
                                                                        <div class="col-6">
                                                                            Expense / Income
                                                                        </div>
                                                                        <div class="col-6">
                                                                            0
                                                                        </div>
                                                                    </div> --}}
                                                                    <hr>
                                                                </div>
                                                            </div>

                                                            <div class="row mt-3">
                                                                <div class="col-12">
                                                                    {{-- <h5>Order Details (Except Moka Order Delivery)</h5> --}}
                                                                    <h5>Order Details</h5>
                                                                    <hr>
                                                                    <div class="row">
                                                                        <div class="col-6">
                                                                            Sold Items
                                                                        </div>
                                                                        <div class="col-6">
                                                                            <div class="row">
                                                                                <div class="col-10">
                                                                                    <span id="txt-sold-items"></span>
                                                                                </div>
                                                                                <div class="col-2">
                                                                                    <i class="fas fa-arrow-right ms-auto list-sold-item" data-session="sold-item"></i></a>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <hr>
                                                                    {{-- <div class="row">
                                                                        <div class="col-6">
                                                                            Refunded Items
                                                                        </div>
                                                                        <div class="col-6">
                                                                            0
                                                                        </div>
                                                                    </div> --}}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card d-none" id="end-current-shift-section">
                                        <div class="card-body">
                                            <div class="container">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="card">
                                                            <div class="card-body">
                                                                <div class="row">
                                                                    <div class="col-12">
                                                                        <h5>Actual Ending Cash</h5>
                                                                        <div class="form-group">
                                                                            <input type="text" class="form-control"
                                                                                id="endingCash"
                                                                                placeholder="Ending cash">
                                                                        </div>
                                                                        <div class="row mt-2 d-none"
                                                                            id="container-difference">
                                                                            <hr>
                                                                            <div class="col-6">
                                                                                <h5 class="text-muted ms-4">Difference
                                                                                </h5>
                                                                            </div>
                                                                            <div class="col-6 me-auto">
                                                                                <div id="difference"></div>
                                                                            </div>
                                                                            <hr>
                                                                        </div>

                                                                        <button
                                                                            class="btn btn-outline-primary w-100 btn-lg mt-2"
                                                                            id="btnEndCurrentShift">End
                                                                            Current Shift</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="card"
                                                            style="overflow-y: auto; height: calc(100vh - 380px);">
                                                            <div class="card-body">
                                                                <div class="row">
                                                                    <div class="col-12">

                                                                        <div class="row">
                                                                            <div class="col-12">
                                                                                <div class="row">
                                                                                    <strong>SHIFT DETAILS</strong>
                                                                                </div>
                                                                                <hr>

                                                                                <div class="row">
                                                                                    <div class="col-6">Name</div>
                                                                                    <div class="col-6"
                                                                                        id="txt-name-end-current-shift">
                                                                                        Ardian</div>
                                                                                </div>
                                                                                <hr>

                                                                                <div class="row">
                                                                                    <div class="col-6">Outlet</div>
                                                                                    <div class="col-6"
                                                                                        id="txt-outlet-end-current-shift">
                                                                                        Outlet 1</div>
                                                                                </div>
                                                                                <hr>

                                                                                <div class="row">
                                                                                    <div class="col-6">Starting Shift
                                                                                    </div>
                                                                                    <div class="col-6"
                                                                                        id="txt-start-end-current-shift">
                                                                                        Thursday,
                                                                                        blabla</div>
                                                                                </div>
                                                                                <hr>

                                                                                {{-- <div class="row">
                                                                                    <div class="col-6">Expense /
                                                                                        Income</div>
                                                                                    <div class="col-6">0</div>
                                                                                </div>
                                                                                <hr> --}}
                                                                            </div>
                                                                        </div>

                                                                        <div class="row">
                                                                            <div class="col-12">
                                                                                <div class="row">
                                                                                    <strong>CASH</strong>
                                                                                </div>
                                                                                <hr>

                                                                                <div class="row">
                                                                                    <div class="col-6">Starting Cash
                                                                                        In Drawer</div>
                                                                                    <div class="col-6"
                                                                                        id="txt-starting-cash-end-current-shift">
                                                                                        Rp. 50.000
                                                                                    </div>
                                                                                </div>
                                                                                <hr>

                                                                                <div class="row">
                                                                                    <div class="col-6">Cash Sales
                                                                                    </div>
                                                                                    <div class="col-6"
                                                                                        id="txt-sales-end-current-shift">
                                                                                        Rp. 70.000
                                                                                    </div>
                                                                                </div>
                                                                                <hr>

                                                                                {{-- <div class="row">
                                                                                    <div class="col-6">Cash From
                                                                                        Invoice
                                                                                    </div>
                                                                                    <div class="col-6">Rp. 0</div>
                                                                                </div>
                                                                                <hr> --}}

                                                                                {{-- <div class="row">
                                                                                    <div class="col-6">Cash Refunds
                                                                                    </div>
                                                                                    <div class="col-6">Rp. 0</div>
                                                                                </div>
                                                                                <hr> --}}

                                                                                {{-- <div class="row">
                                                                                    <div class="col-6">Expense /
                                                                                        Income</div>
                                                                                    <div class="col-6">Rp. 0</div>
                                                                                </div>
                                                                                <hr> --}}

                                                                                <div class="row">
                                                                                    <div class="col-6">Expected Ending
                                                                                        Cash</div>
                                                                                    <div class="col-6"
                                                                                        id="txt-expected-ending-end-current-shift">
                                                                                        Rp. 121.000
                                                                                    </div>
                                                                                </div>
                                                                                <hr>

                                                                            </div>
                                                                        </div>

                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card d-none child-section" id="list-sold-item" style="margin-bottom: 100px">
                                        <div class="card-body">
                                            <div class="container">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="container" id="container-sold-item">

                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card d-none child-section" id="detail-shift-history" style="margin-bottom: 100px">
                                        <div class="card-body">
                                            <div class="container">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="row">
                                                            <div class="col-6">
                                                                {{-- <button id="end-current-shift"
                                                                class="btn btn-outline-primary w-100 btn-lg mb-4">Akhiri Shift</button> --}}
                                                            </div>
                                                            <div class="col-12">
                                                                <a href="javascript:void(0);" class="btn btn-outline-primary w-100 btn-lg mb-4" target="_blank" id="btn-print-history-shift">Cetak Laporan Shift</a>
                                                            </div>
                                                        </div>
                                                        <div class="container" id="container-detail-shift">
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <h5>Shift Details</h5>
                                                                    <hr>
                                                                    <div class="row">
                                                                        <div class="col-6">
                                                                            Open Shift
                                                                        </div>
                                                                        <div class="col-6" id="txt-detail-open-patty-cash">
                                                                            Ardian
                                                                        </div>
                                                                    </div>
                                                                    <hr>
                                                                    <div class="row">
                                                                        <div class="col-6">
                                                                            Close Shift
                                                                        </div>
                                                                        <div class="col-6" id="txt-detail-close-patty-cash">
                                                                            -
                                                                        </div>
                                                                    </div>
                                                                    <hr>
                                                                    <div class="row">
                                                                        <div class="col-6">
                                                                            Outlet
                                                                        </div>
                                                                        <div class="col-6" id="txt-detail-outlet">
                                                                            Outlet 1
                                                                        </div>
                                                                    </div>
                                                                    <hr>
                                                                    <div class="row">
                                                                        <div class="col-6">
                                                                            Starting Shift
                                                                        </div>
                                                                        <div class="col-6" id="txt-detail-starting-shift">
                                                                            Thursday blablabla
                                                                        </div>
                                                                    </div>
                                                                    <hr>
                                                                    <div class="row">
                                                                        <div class="col-6">
                                                                            Ending Shift
                                                                        </div>
                                                                        <div class="col-6" id="txt-detail-ending-shift">
                                                                            Thursday blablabla
                                                                        </div>
                                                                    </div>
                                                                    <hr>
                                                                    {{-- <div class="row">
                                                                        <div class="col-6">
                                                                            Expense / Income
                                                                        </div>
                                                                        <div class="col-6">
                                                                            0
                                                                        </div>
                                                                    </div>
                                                                    <hr> --}}
                                                                </div>
                                                            </div>

                                                            <div class="row mt-3">
                                                                <div class="col-12">
                                                                    {{-- <h5>Order Details (Except Moka Order Delivery)</h5> --}}
                                                                    <h5>Order Details</h5>
                                                                    <hr>
                                                                    <div class="row">
                                                                        <div class="col-6">
                                                                            Sold Items
                                                                        </div>
                                                                        <div class="col-6" >
                                                                            <div class="row">
                                                                                <div class="col-10">
                                                                                    <span id="txt-detail-sold-items"></span>
                                                                                </div>
                                                                                <div class="col-2">
                                                                                    <i class="fas fa-arrow-right ms-auto list-sold-item"></i></a>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <hr>
                                                                    {{-- <div class="row">
                                                                        <div class="col-6">
                                                                            Refunded Items
                                                                        </div>
                                                                        <div class="col-6">
                                                                            0
                                                                        </div>
                                                                    </div> --}}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card d-none child-section" id="history-shift-menu" style="margin-bottom: 100px">
                                        <div class="card-body">
                                            <div class="container">
                                                <div class="history-shift-list" id="shiftList">
                                                    <!-- Shift items will be dynamically inserted here -->
                                                </div>
                                                <nav aria-label="Page navigation" class="mt-4">
                                                    <ul class="pagination justify-content-center" id="pagination">
                                                        <!-- Pagination links will be dynamically inserted here -->
                                                    </ul>
                                                </nav>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- favorite Section -->
                    <div id="favorite" class="content-section active">
                        <div class="row vh-100">
                            <div class="container my-5">
                                <div class="row">
                                    <!-- Card 1 -->
                                    <center>Comming Soon</center>
                                    {{-- <div class="col-md-3 col-sm-6 mb-4">
                                        <div class="product-card">
                                            <img src="https://via.placeholder.com/150" alt="Cappuccino">
                                            <p>Cappuccino</p>
                                        </div>
                                    </div>
                                    <!-- Card 2 -->
                                    <div class="col-md-3 col-sm-6 mb-4">
                                        <div class="product-card">
                                            <img src="https://via.placeholder.com/150" alt="Dori Goreng Tepung">
                                            <p>Dori Goreng Tepung</p>
                                        </div>
                                    </div>
                                    <!-- Card 3 -->
                                    <div class="col-md-3 col-sm-6 mb-4">
                                        <div class="product-card">
                                            <img src="https://via.placeholder.com/150" alt="Beef Chop">
                                            <p>Beef Chop</p>
                                        </div>
                                    </div>
                                    <!-- Card 4 -->
                                    <div class="col-md-3 col-sm-6 mb-4">
                                        <div class="product-card">
                                            <img src="https://via.placeholder.com/150" alt="Espresso">
                                            <p>Espresso</p>
                                        </div>
                                    </div> --}}


                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- library Section -->
                    <div id="library" class="content-section ">
                        <div class="row vh-100">
                            <div class="col-12 mt-3">
                                <!-- Search Bar -->
                                <div class="input-group mb-3 " id="input-search-item">
                                    <input type="text" id="search-item" class="form-control" placeholder="Cari"
                                        aria-label="Search">
                                    <button class="btn btn-outline-secondary" id="clear-search"
                                        type="button">Clear</button>
                                </div>

                                <!-- Content Section -->
                                <div id="content-section" class="mt-2">

                                    <div class="card d-flex align-items-center">
                                        <div class="row w-100" style="height: 80px">
                                            <div class="col-auto d-flex align-items-center">
                                                <button id="back-btn" class="btn btn-primary my-3 back-btn"
                                                    style="display: none !important;">&larr; Back</button>
                                                <div class="col text-center">
                                                    <h5 class="my-3 ms-2" id="text-judul">Library</h5>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Initial Library View -->
                                    <div id="library-view" class="card child-section"
                                        style="overflow-y: auto; height: calc(100vh - 240px);">
                                        <div class="list-group">
                                            <div class="list-group-item list-category d-flex align-items-center"
                                                data-target="Diskon" data-name="Diskon">
                                                <div class="icon-box" data-text="diskon"></div>
                                                <span class="ms-3">Diskon</span>
                                                <span class="ms-auto">&gt;</span>
                                            </div>

                                            <div class="list-group-item list-category d-flex align-items-center"
                                                data-target="all-item" data-name="All Item">
                                                <div class="icon-box" data-text="all-item"></div>
                                                <span class="ms-3">All Item</span>
                                                <span class="ms-auto">&gt;</span>
                                            </div>

                                            @foreach ($categorys as $category)
                                                @if (count($category->products))
                                                    <div class="list-group-item list-category d-flex align-items-center"
                                                        data-target="kategori-{{ $category->id }}"
                                                        data-name="{{ $category->name }}">
                                                        <div class="icon-box" data-text="{{ $category->name }}"></div>
                                                        <span class="ms-3">{{ $category->name }}</span>
                                                        <span class="ms-auto">&gt;</span>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>

                                    <!-- Product View -->
                                    @foreach ($categorys as $item)
                                        <div id="kategori-{{ $item->id }}"
                                            class="card d-none child-section list-product-category"
                                            style="overflow-y: auto; height: calc(100vh - 240px);">
                                            @foreach ($item->products as $data)
                                                <div class="list-group-item list-item d-flex align-items-center"
                                                    data-harga="{{ $data->harga_jual }}"
                                                    data-nama="{{ $data->name }}" data-id="{{ $data->id }}">
                                                    <div class="icon-box" data-text="{{ $data->name }}"></div>
                                                    <span class="ms-3">{{ $data->name }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach

                                    {{-- Menu Diskon --}}
                                    <div id="Diskon" class="card d-none child-section"
                                        style="overflow-y: auto; height: calc(100vh - 240px);">

                                    </div>

                                    {{-- Menu All Item --}}
                                    <div id="all-item" class="card d-none child-section"
                                        style="overflow-y: auto; height: calc(100vh - 240px);">
                                        @foreach ($categorys as $item)
                                            @foreach ($item->products as $data)
                                                <div class="list-group-item list-item list-all-item d-flex align-items-center"
                                                    data-harga="{{ $data->harga_jual }}"
                                                    data-nama="{{ $data->name }}" data-id="{{ $data->id }}">
                                                    <div class="icon-box" data-text="{{ $data->name }}"></div>
                                                    <span class="ms-3">{{ $data->name }}</span>
                                                </div>
                                            @endforeach
                                        @endforeach

                                    </div>
                                </div>
                            </div>


                        </div>
                    </div>

                    <!-- custom Section -->
                    <div id="custom" class="content-section">
                        <div class="row vh-100">
                            <div class="container mt-2">
                                <!-- Calculator Screen -->
                                <div class="row">
                                    <div class="col-12">
                                        <div class="screen" id="calculator-screen">Rp 0</div>
                                    </div>
                                </div>
                                <!-- Calculator Buttons -->
                                <div class="row calculator-row mt-3 me-2">
                                    <div class="col-3"><button class="btn btn-light w-100 calculator-btn"
                                            data-value="1">1</button></div>
                                    <div class="col-3"><button class="btn btn-light w-100 calculator-btn"
                                            data-value="2">2</button></div>
                                    <div class="col-3"><button class="btn btn-light w-100 calculator-btn"
                                            data-value="3">3</button></div>
                                    <div class="col-3"><button class="btn btn-light w-100 calculator-btn"
                                            data-value="0">0</button></div>
                                </div>
                                <div class="row calculator-row mt-2 me-2">
                                    <div class="col-3"><button class="btn btn-light w-100 calculator-btn"
                                            data-value="4">4</button></div>
                                    <div class="col-3"><button class="btn btn-light w-100 calculator-btn"
                                            data-value="5">5</button></div>
                                    <div class="col-3"><button class="btn btn-light w-100 calculator-btn"
                                            data-value="6">6</button></div>
                                    <div class="col-3"><button class="btn btn-light w-100 calculator-btn"
                                            data-value="00">00</button></div>
                                </div>
                                <div class="row calculator-row mt-2 me-2">
                                    <div class="col-3"><button class="btn btn-light w-100 calculator-btn"
                                            data-value="7">7</button></div>
                                    <div class="col-3"><button class="btn btn-light w-100 calculator-btn"
                                            data-value="8">8</button></div>
                                    <div class="col-3"><button class="btn btn-light w-100 calculator-btn"
                                            data-value="9">9</button></div>
                                    <div class="col-3"><button class="btn btn-primary w-100 calculator-btn"
                                            data-value="add">+</button></div>
                                </div>
                                <div class="row calculator-row mt-2 me-2">
                                    <div class="col-6"><button
                                            class="btn btn-secondary w-100 calculator-btn calculator-btn-footer"
                                            data-value="clear">C</button></div>
                                    <div class="col-6"><button
                                            class="btn btn-secondary w-100 calculator-btn calculator-btn-footer"
                                            data-value="del">Del</button></div>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>

                <!-- Order Section -->
                <div class="col-5 p-3">
                    <div class="order-section">
                        <div class="row mb-1">
                            <div class="col-12 d-flex">
                                <button class="btn btn-primary-outline w-25 btn-lg my-0 ms-0 me-1 px-0 pb-0 rounded"
                                    style="border-style:solid; border-radius: 2px; border-color: #3b5998;"
                                    id="bill-list">
                                    <img src="{{ asset('img/billing.png') }}" alt="" width="40">
                                    <p class="m-0" style="font-size: 12px;">Billing list</p>
                                </button>
                                <button class="btn btn-primary me-1 w-50 btn-lg rounded" id="pilih-pelanggan">Pilih
                                    Pelanggan</button>
                                <button class="btn btn-primary ms-1 w-50 btn-lg rounded" id="tambah-pelanggan">Tambah
                                    Pelanggan</button>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-body ">

                                <!-- Dine In Section -->
                                <p class="text-muted text-center mb-2">All Sales Type</p>
                                <hr>

                                <div class="container my-5" id="produkKosong">
                                    <div class="col-12 text-center">
                                        <p>Produk Belum Dipilih</p>
                                    </div>
                                </div>

                                <div id="summary" style="display: none">
                                    <form action="bayar" method="POST">
                                        <!-- Item List -->
                                        <div id="order-list">

                                        </div>

                                        <!-- Summary Section -->
                                        <div class="row mb-2">
                                            <div class="col-6">Subtotal:</div>
                                            <div class="col-6 text-end" id="sub-total">Rp 27.000</div>
                                        </div>

                                        <div class="row mb-2 d-none" id="group-diskon">
                                            <div class="col-6">Diskon:</div>
                                            <div class="col-6 text-end" id="diskon"></div>
                                        </div>

                                        <div id="pajak">
                                        </div>

                                        <div class="row mb-2">
                                            <div class="col-6">Total:</div>
                                            <div class="col-6 text-end" id="total"></div>
                                        </div>

                                        <div class="row mb-3 d-none" id="group-rounding">
                                            <div class="col-6" style="color:gray;">Rounding:</div>
                                            <div class="col-6 text-end" id="rounding"></div>
                                        </div>
                                    </form>

                                </div>

                                <!-- Empty Cart Section -->
                                <p class="text-muted text-center mb-3 card" id="empty-cart">Kosongkan Keranjang
                                    Belanja</p>

                                <!-- Action Buttons -->
                                <div class="d-flex mb-3">
                                    <button class="btn btn-secondary w-50 me-2" id="simpan-bill"
                                        style="height: 60px;">Simpan
                                        Bill</button>
                                    <button id="cetak-bill" onclick="sendDataInCart()" class="btn btn-outline-primary w-50" style="height: 60px;">Cetak
                                        Bill</button>
                                </div>

                                <!-- Charge Button -->
                                <div class="row d-flex">
                                    <div class="col-12 d-flex bg-primary rounded" >
                                        <button class="btn w-25 btn-lg my-0 ms-0  ps-0 pe-2 pb-0 "
                                            style="border-right: 2px solid white; border-radius: 0px;"
                                            id="split-bill">
                                            <img src="{{ asset('img/split-bill.png') }}" alt="" width="40">
                                            <p class="m-0" style="font-size: 12px; color: white ;">Split Bill</p>
                                        </button>
                                        <button class="btn btn-primary btn-lg btn-block w-75" id="bayar"
                                            style="height: 100%; width: 100%;">Bayar</button>
                                    </div>

                                </div>

                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="d-none" id="activity-menu">
            <div class="row">
                <div class="card d-flex">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <button class="btn btn-primary" id="btn-back-activity">&#8592; Back</button>
                        <div class="flex-grow-1 d-flex justify-content-center">
                            <h4 class="text-center">Aktivitas</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-4 m-0" style="border-right: 2px solid gray; height: 93vh; max-height: 93vh;">
                    <div class="input-group my-1" id="input-search-aktivitas">
                        <input type="time" id="search-aktivitas" class="form-control" placeholder="Cari"
                            aria-label="Search">
                        <button class="btn btn-outline-secondary" id="clear-search-activity"
                            type="button">Clear</button>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            Hari Ini
                        </div>

                        <div class="card-body" id="list-transaction-container" style="max-height: 80vh; overflow-y: auto;">

                        </div>
                    </div>
                </div>
                <div class="col-8" style="max-height: 90vh; overflow-y: auto;">
                    <div class="container px-5">
                        <div class="row mt-3">
                            <div class="col-6">
                                <button  class="btn btn-primary btn-lg w-100 disabled"
                                    id="btn-resend-receipt"><i class="fas fa-paper-plane" style="font-size: 18px;"></i> &nbsp; Resend Receipt</button>
                            </div>

                            <div class="col-6">
                                <a  class="btn btn-primary btn-lg disabled w-100" target="_blank"
                                    id="btn-print-history-transaction"><i class="fas fa-receipt me-1"></i>Print Struk</a>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <h3>Detail</h3>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6 mb-1">
                                <i class="fa-solid fa-cash-register" style="font-size: 20px;"></i>
                                <strong style="font-size: 20px">Metode Pembayaran</strong>
                            </div>
                            <div class="col-6 ms-auto d-flex justify-content-end" id="metode-pembayaran"></div>

                            <div class="col-6 mb-1">
                                <i class="fa-solid fa-file-invoice" style="font-size: 20px;"></i>
                                <strong style="font-size: 20px">Nomor Struk</strong>
                            </div>
                            <div class="col-6"></div>

                            <div class="col-6 mb-1">
                                <i class="fa-regular fa-clock" style="font-size:20px;"></i>
                                <strong style="font-size: 20px;">Waktu Pembelian</strong>
                            </div>
                            <div class="col-6 ms-auto d-flex justify-content-end" id="waktu-pembelian"></div>
                        </div>

                        <hr>

                        <div class="row mt-2">
                            <h4>Produk</h4>
                            <br>
                            <p class="d-flex justify-content-center">dine in</p>
                        </div>
                        <div class="container mb-2" id="container-product">
                            <div class="row" id="row-product">

                            </div>
                        </div>

                        <hr>

                        <div class="row mb-2" id="row-subtotal">
                            <div class="col-6">SubTotal: </div>
                            <div class="col-6 d-flex align-items-center justify-content-end" id="subtotal"></div>
                        </div>

                        <hr>

                        <div class="row mb-2" id="row-diskon">
                            <div class="col-6">Diskon: </div>
                            <div class="col-6 d-flex align-items-center justify-content-end" id="diskon-transaction"></div>
                        </div>

                        <hr>

                        <div class="row mb-2" id="row-pajak">

                        </div>

                        <hr>

                        <div class="row mb-2" id="row-total">
                            <div class="col-6">Total</div>
                            <div class="col-6 d-flex align-items-center justify-content-end" id="total-transaction"></div>

                            <div class="col-6">Pembayaran</div>
                            <div class="col-6 d-flex align-items-center justify-content-end" id="pembayaran-transaction"></div>

                            <div class="col-6">Kembalian</div>
                            <div class="col-6 d-flex align-items-center justify-content-end" id="kembalian-transaction"></div>
                        </div>

                    </div>
                </div>
            </div>

        </div>

        <!-- Bottom Navigation -->
        <ul class="nav nav-pills nav-fill fixed-bottom bg-light" id="bottom-navbar">
            <li class="nav-item-small ">
                <a class="nav-link bottom-nav-li pb-1" data-target="#setting" href="#">
                    <div class="contianer mt-2">
                        <i class="fa-solid fa-list" style="font-size: 26px;"></i>
                    </div>
                </a>
            </li>
            <li class="nav-item ">
                <a class="nav-link active bottom-nav-li pb-1" data-target="#favorite" href="#">
                    <div class="container">
                        <i class="fa-solid fa-star" style="font-size: 26px;"></i>
                    </div>
                    Favorites
                </a>
            </li>
            <li class="nav-item ">
                <a class="nav-link bottom-nav-li pb-1" data-target="#library" href="#">
                    <div class="container">
                        <i class="fa-solid fa-table-list" style="font-size: 26px;"></i>
                    </div>
                    Library
                </a>
            </li>
            <li class="nav-item ">
                <a class="nav-link bottom-nav-li pb-1" data-target="#custom" href="#">
                    <div class="container">
                        <i class="fa-solid fa-calculator" style="font-size: 26px;"></i>
                    </div>
                    Custom
                </a>
            </li>
        </ul>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="itemModal" tabindex="-1" aria-labelledby="itemModalLabel">
    </div>

    <!-- Modal Promo-->
    <div class="modal fade" id="promoModal" tabindex="-1" aria-labelledby="promoModalLabel">
    </div>

    <!-- Modal Success -->
    <div class="modal fade" id="modals" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-body">
                    <center>
                        <img src="https://i.gifer.com/7efs.gif" alt="Transaction Successfully" class="img-fluid">
                        <h3>Transaksi Sukses!</h3>
                        <p>Kembalian :
                        <h2><strong id="change"></strong></h2>
                        </p>
                        <span class="badge rounded-pill bg-primary text-white mb-4">Metode Pembayaran : <span
                                id="metodetrx"></span></span>
                        <div class="d-flex justify-content-center mt-4">
                            {{-- <a href="javascript:void(0);" class="btn btn-secondary me-4" target="_blank"
                                id="btninvoice"><i class="fab fa-whatsapp me-1"></i>Kirim Invoice</a> --}}
                            <a href="javascript:void(0);" class="btn btn-secondary me-4" target="_blank"
                                id="btnstruk"><i class="fas fa-receipt me-1"></i>Cetak Struk</a>
                            <a href="javascript:void(0);" class="btn btn-secondary" target="_blank"
                                id="btnSettingDevice"><i class="fas fa-gear me-1"></i>Setting Device</a>
                        </div>
                    </center>
                </div>
                <div class="d-flex justify-content-center mb-4">
                    <a type="submit" class="btn btn-primary w-50" href="{{ route('kasir') }}">Buat Pesanan Baru</a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalSuccessSplitBill" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-body">
                    <center>
                        <img src="https://i.gifer.com/7efs.gif" alt="Transaction Successfully" class="img-fluid">
                        <h3>Transaksi Sukses!</h3>
                        <p>Kembalian :
                        <h2><strong id="changeSplitBill"></strong></h2>
                        </p>
                        <span class="badge rounded-pill bg-primary text-white mb-4">Metode Pembayaran : <span
                                id="metodetrxSplitBill"></span></span>
                        <div class="d-flex justify-content-center mt-4">
                            {{-- <a href="javascript:void(0);" class="btn btn-secondary me-4" target="_blank"
                                id="btninvoice"><i class="fab fa-whatsapp me-1"></i>Kirim Invoice</a> --}}
                            <a href="javascript:void(0);" class="btn btn-secondary me-4" target="_blank"
                                id="btnstrukSplitBill"><i class="fas fa-receipt me-1"></i>Cetak Struk</a>
                            <a href="javascript:void(0);" class="btn btn-secondary" target="_blank"
                                id="btnSettingDeviceSplitBill"><i class="fas fa-gear me-1"></i>Setting Device</a>
                        </div>
                    </center>
                </div>
                <div class="d-flex justify-content-center mb-4">
                    <button type="button" class="btn btn-primary w-50" id="btnRedirectSplitBill">Kembali ke pesanan</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditPesanan" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" id="editPesananModal">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn btn-outline-secondary btn-lg" data-bs-dismiss="modal"
                        id="btnBatalEditItem">Batal</button>
                    <h5 class="modal-title mx-auto text-center" id="productEditModalLabel">
                        <strong id="namaProductEdit"></strong><br>
                        <span id="totalHargaItemEdit"></span>
                    </h5>
                    <button id="saveItemToCartEdit" type="button" class="btn btn-primary btn-lg">Simpan</button>
                </div>
                <div class="modal-body">

                    <div id="listVariantEdit"></div>

                    <div id="listPilihanEdit"></div>

                    <!-- Jumlah -->
                    <div class="mb-4">
                        <label for="quantity" class="form-label"><strong>Jumlah</strong></label>
                        <div class="row">
                            <div class="col-6">
                                <input type="number" class="form-control text-center form-control-lg" id="quantity-edit"
                                    style="height: 75px; font-size: 17px;" value="1" min="1" readonly>
                            </div>
                            <div class="col-3">
                                <button
                                    class="text-center align-items-center justify-content-center btn btn-lg btn-outline-primary w-100 d-flex"
                                    id="decrement-edit" style="height: 75px; font-size:25px;"><span
                                        class="text-center"></span>-</button>
                            </div>
                            <div class="col-3">
                                <button
                                    class="text-center btn btn-lg align-items-center justify-content-center btn-outline-primary w-100 d-flex"
                                    id="increment-edit" style="height: 75px; font-size:25px;"><span
                                        class="text-center">+</span></button>
                            </div>
                        </div>
                    </div>

                    <div id="salesTypeEdit"></div>

                    <div id="listModifierEdit"></div>

                    <div id="listDiskonEdit"></div>

                    <div class="mb-4 mt-2">
                        <label for="note" class="form-label"><strong>Catatan</strong></label>
                        <textarea style="height: 220px;" class="form-control" id="catatanEdit" rows="3"></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- JS Dependencies -->
    {{-- <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script> --}}
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    {{-- IZI TOAST --}}
    <script src="{{ asset('js/plugin/izitoast/iziToast.min.js') }}"></script>
    <!-- Datatables -->
    {{-- <script src="{{ asset('js/plugin/datatables/datatables.min.js') }}"></script> --}}
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

    {{-- SELECT 2 --}}
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name=csrf_token]').attr('content')
            }
        });

        showLoader();
        var listItem = [];
        var subTotal = [];
        var listDiskon = [];
        var totalDiskon = [];
        var idPelanggan = '';
        var pointPelanggan = 0;
        var listDiskonAllItem = [];
        var tmpTampungCustomAmount = 0;
        var totalKeseluruhanPajak = 0;
        var listPajak = [];
        var tandaRounding = '';
        var amountRounding = 0;
        var listCategory = @json($categorys);
        var listProduct = [];
        var listActivityTransaction = [];
        var listItemSplitBill = [];
        var listPajakSplitBill = [];
        listCategory.forEach(function(category) {
            category.products.forEach(function(product) {
                listProduct.push(product);
            });
        });

        var listPromo = @json($promos);
        var promoTerpasang = [];
        var listItemPromo = [];
        var listRewardItem = [];
        var promoCocok = [];

        var dataPattyCash = @json($pettyCash);
        var listCategoryPayment = @json($listCategoryPayment);
        var finalExpectedEndingCash = 0;

        let openBillForm = new FormData();
        var billId = 0;

        var resultNominalDiskon = 0;
        var dataLogin = @json(auth()->user());

        var listSoldItem = [];
        var listExistingSoldItem = @json($soldItem)

        // properties edit item
        var listModifierIdEdit = [];
        var listModifierNameEdit = [];
        var listModifierHargaEdit = [];

        var listPilihanIdEdit = [];
        var listPilihanNameEdit = [];
        var listPilihanHarga = [];

        var listDiskonIdEdit = [];
        var listDiskonNameEdit = [];
        var listDiskonTypeEdit = [];
        var listDiskonValueEdit = [];
        var listDiskonAmountEdit = [];

        var hargaAkhirEditItem = 0;
        var variantIdEdit = '';
        var variantNameEdit = '';

        var salesTypeIdEdit = '';

        function showLoader(show = true) {
            const preloader = $("#preloader");

            if (show) {
                preloader.css({
                    opacity: 1,
                    visibility: "visible",
                });
            } else {
                preloader.css({
                    opacity: 0,
                    visibility: "hidden",
                });
            }
        }

        function generateRandomID() {
            return 'temp-' + Date.now() + '-' + Math.floor(Math.random() * 10000);
        }

        function deleteItem(button) {
            // Cari elemen terdekat yang merupakan parent (row item) dan hapus
            var row = $(button).closest('.row');

            let dataTmpId = button.getAttribute('data-tmpId');


            // Ambil nilai harga dari input hidden
            var harga = parseInt(row.find('input[name="harga[]"]').val());
            // Hapus nilai dari array subTotal
            var index = subTotal.indexOf(harga);
            if (index !== -1) {
                subTotal.splice(index, 1); // Hapus nilai dari array
            }

            // Cari elemen modifier terkait berdasarkan data-tmpId
            $(`.modifier[data-tmpId="${dataTmpId}"]`).each(function() {
                // Ambil nilai hargaModifier dari input hidden
                let hargaModifier = parseInt($(this).find('input[name="hargaModifier[]"]').val());

                // Hapus nilai hargaModifier dari array subTotal
                let modifierIndex = subTotal.indexOf(hargaModifier);
                if (modifierIndex !== -1) {
                    subTotal.splice(modifierIndex, 1); // Hapus nilai dari array
                }
            });

            $(`.diskon[data-tmpId="${dataTmpId}"]`).each(function() {
                let hargaDiskon = parseInt($(this).find('input[name="nominalDiskon[]"]').val());

                // Hapus nilai hargaModifier dari array subTotal
                let modifierIndex = totalDiskon.indexOf(hargaDiskon);
                if (modifierIndex !== -1) {
                    totalDiskon.splice(modifierIndex, 1); // Hapus nilai dari array
                }
            });

            // Hapus semua elemen (produk dan modifier) dengan data-tmpId terkait dari DOM
            $(`[data-tmpId="${dataTmpId}"]`).remove();

            //hapus reward jika ada
            $(`[data-itempromoid=${dataTmpId}]`).remove();

            // Hapus elemen row dari DOM
            row.remove();

            listItem = listItem.filter(item => item.tmpId !== dataTmpId);
            listItemPromo = listItemPromo.filter(item => item.tmpId !== dataTmpId);
            listRewardItem = listRewardItem.filter(item => item.idItemPromo !== dataTmpId);
            listDiskonAllItem = listDiskonAllItem.filter(item => item.tmpId !== dataTmpId);

            console.log(listItem.length)
            if(!listItem.length){
                listDiskonAllItem = listDiskonAllItem.filter(item => item.hasOwnProperty('tmpId'));

            }

            syncItemCart()
        }

        function updateCustomAmount() {
            let tmpId = generateRandomID();
            let html = `
            <div class="row mb-0 mt-2" data-tmpid="${tmpId}" onclick="handlerEditItem(this)">
                <div class="col-6" style="color:gray;">Custom Amount</div>
                <input type="text" name="nama[]" value="custom" hidden>
                <div class="col-5 text-end">${formatRupiah(tmpTampungCustomAmount.toString(), "Rp. ")}</div>
                <input type="text" name="harga[]" value="${tmpTampungCustomAmount}" hidden>
                <input type="text" name="quantity[]" value="1" hidden>
                <div class="col-1 text-end text-danger">
                    <button type="button" data-tmpId="${tmpId}" onclick="deleteItem(this)" class="btn btn-link btn-sm text-danger p-0 w-100">&times;</button>
                </div>
            </div>
            `;

            subTotal.push(parseInt(tmpTampungCustomAmount));

            let dataItemCustom = {
                catatan: "",
                diskon: [],
                harga: parseInt(tmpTampungCustomAmount),
                idProduct: null,
                idProduct: null,
                modifier: [],
                namaProduct: 'custom',
                namaVariant: 'custom',
                pilihan: [],
                promo: [],
                quantity: "1",
                resultTotal: parseInt(tmpTampungCustomAmount),
                salesType: null,
                tmpId: tmpId,

            }

            listItem.push(dataItemCustom);
            // Tambahkan elemen ke dalam form di dalam #order-list
            $('#order-list').append(html);

            syncItemCart();

        }

        function formatRupiah(angka, prefix) {
            var number_string = angka.replace(/[^,\d]/g, "").toString(),
                split = number_string.split(","),
                sisa = split[0].length % 3,
                rupiah = split[0].substr(0, sisa),
                ribuan = split[0].substr(sisa).match(/\d{3}/gi);

            if (ribuan) {
                separator = sisa ? "." : "";
                rupiah += separator + ribuan.join(".");
            }

            rupiah = split[1] != undefined ? rupiah + "," + split[1] : rupiah;
            return prefix == undefined ? rupiah : rupiah ? "Rp. " + rupiah : "";
        }

        function updateHargaFinalButton() {
            let total = document.getElementById("total").textContent;
            let textTotal = total.trim();
            let angkaTotal = parseInt(textTotal.replace(/[^\d]/g, ""));

            let rounding = document.getElementById("rounding").textContent;
            if (rounding) {
                let textRounding = rounding.trim();
                let angkaRounding = parseInt(textRounding.replace(/[^\d]/g, ""));

                let symbol = textRounding.charAt(0);

                let hargaFinal = symbol == "-" ? angkaTotal - angkaRounding : angkaTotal + angkaRounding;

                $('#bayar').text("Bayar " + formatRupiah(hargaFinal.toString(), "Rp. "));
            } else {
                $('#bayar').text("Bayar " + formatRupiah(angkaTotal.toString(), "Rp. "));
            }
        }

        function showToast(status = 'success', message) {
            console.log(message);
            iziToast[status]({
                title: status == 'success' ? 'Success' : 'Error',
                message: message,
                position: 'topRight'
            });
        }

        function syncPromo() {
            // Filter listPromo untuk mendapatkan promo yang tidak ada di promoTerpasang
            var filteredPromo = listPromo.filter(function(promo) {
                return !promoTerpasang.some(function(selectedPromo) {
                    return (selectedPromo.id === promo.id || promo.multiple !=
                        0); // Bandingkan berdasarkan ID
                });
            });


            promoCocok = [];
            let queueId = generateRandomID();
            let dataItemCart = JSON.parse(JSON.stringify(listItem));
            filteredPromo.forEach(function(item, index) {
                let productRequirement = JSON.parse(item.product_requirement);
                let tmpCondition = [];

                if (item.purchase_requirement == "any_item") {
                    productRequirement.forEach(function(listConditionProduct, listConditionIndex) {
                        listConditionProduct.forEach(function(listProduct, listProductIndex) {
                            let idProduct = listProduct[0];
                            let idVariant = listProduct[1];
                            let qtyProduct = listProduct[2];
                            let tmpDataKebutuhan = [];

                            dataItemCart.forEach(function(itemProduct, itemIndex) {
                                if (idProduct == itemProduct.idProduct) {
                                    if (idVariant != 0) {
                                        if (idVariant == itemProduct.idVariant) {
                                            if (itemProduct.quantity >= qtyProduct) {
                                                let data = {
                                                    tmpId: itemProduct.tmpId,
                                                    qty: qtyProduct
                                                } //ambil data yang akan dikurangi
                                                tmpCondition.push(data);

                                                return;
                                            } else {
                                                if ((qtyProduct - itemProduct
                                                        .quantity) <= 0) {
                                                    let data = {
                                                        tmpId: itemProduct.tmpId,
                                                        qty: qtyProduct
                                                    } //ambil data yang akan dikurangi
                                                    tmpDataKebutuhan.push(data);

                                                    tmpCondition.push(tmpDataKebutuhan);

                                                    return;
                                                } else {
                                                    let data = {
                                                        tmpId: itemProduct.tmpId,
                                                        qty: qtyProduct
                                                    } //ambil data yang akan dikurangi
                                                    tmpDataKebutuhan.push(data);

                                                    qtyProduct - itemProduct.quantity;
                                                }
                                            }
                                        }
                                    } else {
                                        if (itemProduct.quantity >= qtyProduct) {
                                            let data = {
                                                tmpId: itemProduct.tmpId,
                                                qty: qtyProduct
                                            } //ambil data yang akan dikurangi
                                            tmpCondition.push(data);

                                            return;
                                        } else {
                                            if ((qtyProduct - itemProduct.quantity) <=
                                                0) {
                                                let data = {
                                                    tmpId: itemProduct.tmpId,
                                                    qty: qtyProduct
                                                } //ambil data yang akan dikurangi
                                                tmpDataKebutuhan.push(data);

                                                tmpCondition.push(tmpDataKebutuhan);

                                                return;
                                            } else {
                                                let data = {
                                                    tmpId: itemProduct.tmpId,
                                                    qty: qtyProduct
                                                } //ambil data yang akan dikurangi
                                                tmpDataKebutuhan.push(data);

                                                qtyProduct - itemProduct.quantity;
                                            }
                                        }
                                    }
                                }
                            });

                        });

                        if (tmpCondition[listConditionIndex] === undefined) {
                            tmpCondition.push(false);
                        }
                    });
                } else {
                    let requirementProduct = JSON.parse(item.product_requirement);
                    let categoryRequirement = requirementProduct[0][0];
                    let quantityCategoryRequirement = requirementProduct[0][1];
                    let tmpDataKebutuhanCategory = []

                    dataItemCart.forEach(function(productInCart, indexProductInCart) {
                        let dataProduct = listProduct.find((val) => productInCart.idProduct == val.id);
                        let categoryProduct = dataProduct.category_id;
                        console.log(categoryRequirement, categoryProduct)

                        if (categoryRequirement == categoryProduct) {
                            if (parseInt(productInCart.quantity) >= parseInt(
                                    quantityCategoryRequirement)) {
                                let data = {
                                    tmpId: productInCart.tmpId,
                                    qty: quantityCategoryRequirement
                                } //ambil data yang akan dikurangi
                                tmpCondition.push(data);


                                return;
                            } else {
                                if ((quantityCategoryRequirement - productInCart.quantity) <= 0) {
                                    let data = {
                                        tmpId: item.tmpId,
                                        qty: quantityCategoryRequirement
                                    } //ambil data yang akan dikurangi
                                    tmpDataKebutuhanCategory.push(data);

                                    tmpCondition.push(tmpDataKebutuhanCategory);

                                    return;
                                } else {
                                    let data = {
                                        tmpId: item.tmpId,
                                        qty: qtyProduct
                                    } //ambil data yang akan dikurangi
                                    tmpDataKebutuhanCategory.push(data);

                                    qtyProduct - item.quantity;
                                }
                            }
                        }

                        if ((indexProductInCart + 1) == dataItemCart.length) {
                            if (tmpCondition.length == 0) {
                                tmpCondition.push(false);
                            }
                        }
                    });
                }

                let checkCondition = false;
                if (tmpCondition.length != 0) {
                    checkCondition = !tmpCondition.includes(false)
                }

                if (checkCondition) {
                    promoCocok.push(item)
                }
            });


            if (promoCocok.length > 1) {
                // diisi pilih promo salah satu
                // handleAjax("{{ route('kasir/choosePromo') }}", false).excute();
            } else {
                let checkAvailablePromo = true;

                while (checkAvailablePromo) {
                    promoCocok.forEach(function(item, index) {
                        let productRequirement = JSON.parse(item.product_requirement);
                        let tmpCondition = [];

                        if (item.purchase_requirement == "any_item") {
                            productRequirement.forEach(function(listConditionProduct, listConditionIndex) {
                                listConditionProduct.forEach(function(listProduct, listProductIndex) {
                                    let idProduct = listProduct[0];
                                    let idVariant = listProduct[1];
                                    let qtyProduct = listProduct[2];
                                    let tmpDataKebutuhan = [];

                                    listItem.forEach(function(itemProduct, itemIndex) {
                                        if (idProduct == itemProduct.idProduct) {
                                            if (idVariant != 0) {
                                                if (idVariant == itemProduct.idVariant) {
                                                    if (itemProduct.quantity >=
                                                        qtyProduct) {
                                                        let data = {
                                                            tmpId: itemProduct.tmpId,
                                                            qty: qtyProduct
                                                        } //ambil data yang akan dikurangi
                                                        tmpCondition.push(data);

                                                        return;
                                                    } else {
                                                        if ((qtyProduct - itemProduct
                                                                .quantity) <= 0) {
                                                            let data = {
                                                                tmpId: itemProduct
                                                                    .tmpId,
                                                                qty: qtyProduct
                                                            } //ambil data yang akan dikurangi
                                                            tmpDataKebutuhan.push(data);

                                                            tmpCondition.push(
                                                                tmpDataKebutuhan);

                                                            return;
                                                        } else {
                                                            let data = {
                                                                tmpId: itemProduct
                                                                    .tmpId,
                                                                qty: qtyProduct
                                                            } //ambil data yang akan dikurangi
                                                            tmpDataKebutuhan.push(data);

                                                            qtyProduct - itemProduct
                                                                .quantity;
                                                        }
                                                    }
                                                }
                                            } else {
                                                if (itemProduct.quantity >= qtyProduct) {
                                                    let data = {
                                                        tmpId: itemProduct.tmpId,
                                                        qty: qtyProduct
                                                    } //ambil data yang akan dikurangi
                                                    tmpCondition.push(data);

                                                    return;
                                                } else {
                                                    if ((qtyProduct - itemProduct
                                                            .quantity) <=
                                                        0) {
                                                        let data = {
                                                            tmpId: itemProduct.tmpId,
                                                            qty: qtyProduct
                                                        } //ambil data yang akan dikurangi
                                                        tmpDataKebutuhan.push(data);

                                                        tmpCondition.push(tmpDataKebutuhan);

                                                        return;
                                                    } else {
                                                        let data = {
                                                            tmpId: itemProduct.tmpId,
                                                            qty: qtyProduct
                                                        } //ambil data yang akan dikurangi
                                                        tmpDataKebutuhan.push(data);

                                                        qtyProduct - itemProduct.quantity;
                                                    }
                                                }
                                            }
                                        }
                                    });

                                });

                                if (tmpCondition[listConditionIndex] === undefined) {
                                    tmpCondition.push(false);
                                }
                            });
                        } else {
                            let requirementProduct = JSON.parse(item.product_requirement);
                            let categoryRequirement = requirementProduct[0][0];
                            let quantityCategoryRequirement = requirementProduct[0][1];
                            let tmpDataKebutuhanCategory = []

                            listItem.forEach(function(productInCart, indexProductInCart) {
                                let dataProduct = listProduct.find((val) => productInCart.idProduct == val
                                    .id);
                                let categoryProduct = dataProduct.category_id;
                                console.log(categoryRequirement, categoryProduct)

                                if (categoryRequirement == categoryProduct) {
                                    if (parseInt(productInCart.quantity) >= parseInt(
                                            quantityCategoryRequirement)) {
                                        let data = {
                                            tmpId: productInCart.tmpId,
                                            qty: quantityCategoryRequirement
                                        } //ambil data yang akan dikurangi
                                        tmpCondition.push(data);


                                        return;
                                    } else {
                                        if ((quantityCategoryRequirement - productInCart.quantity) <= 0) {
                                            let data = {
                                                tmpId: item.tmpId,
                                                qty: quantityCategoryRequirement
                                            } //ambil data yang akan dikurangi
                                            tmpDataKebutuhanCategory.push(data);

                                            tmpCondition.push(tmpDataKebutuhanCategory);

                                            return;
                                        } else {
                                            let data = {
                                                tmpId: item.tmpId,
                                                qty: qtyProduct
                                            } //ambil data yang akan dikurangi
                                            tmpDataKebutuhanCategory.push(data);

                                            qtyProduct - item.quantity;
                                        }
                                    }
                                }

                                if ((indexProductInCart + 1) == listItem.length) {
                                    if (tmpCondition.length == 0) {
                                        tmpCondition.push(false);
                                    }
                                }
                            });
                        }

                        let checkCondition = false;
                        if (tmpCondition.length != 0) {
                            checkCondition = !tmpCondition.includes(false)
                        }

                        if (checkCondition) {
                            tmpCondition.forEach(function(itemCondition, indexCondition) {
                                listItem.forEach(function(itemList, indexList) {
                                    if (itemCondition.tmpId == itemList.tmpId) {
                                        if (parseInt(itemList.quantity) - parseInt(itemCondition
                                                .qty) <=
                                            0) {
                                            itemList.promo.push(item);
                                            itemList.queueItemId = queueId;
                                            if (item.type == "discount") {
                                                let reward = JSON.parse(item.reward);

                                                let satuanReward = Object.keys(reward[0])[0];
                                                if (satuanReward == "rupiah") {
                                                    let amount = reward[0].rupiah;

                                                    let dataDiscount = {
                                                        id: "promo",
                                                        nama: item.name,
                                                        satuan: satuanReward,
                                                        value: amount,
                                                        idPromo: item.id,
                                                        tmpId: itemList.tmpId
                                                    }

                                                    listDiskonAllItem.push(dataDiscount)
                                                } else {
                                                    let amount = reward[0].percent;
                                                    let resultDiskon = (itemList.harga * parseInt(
                                                        itemList.quantity) * amount) / 100;
                                                    console.log(itemList)
                                                    let dataDiscountPercent = {
                                                        id: "promo",
                                                        nama: item.name,
                                                        result: resultDiskon,
                                                        satuan: satuanReward,
                                                        tmpIdProduct: itemList.tmpId,
                                                        value: amount,
                                                        idPromo: item.id,
                                                    }

                                                    console.log(dataDiscountPercent)

                                                    itemList.diskon.push(dataDiscountPercent);
                                                }
                                            }


                                            listItemPromo.push(itemList);

                                            listItem.splice(indexList, 1);
                                        } else {
                                            let sisaQtyItem = parseInt(itemList.quantity) -
                                                parseInt(
                                                    itemCondition.qty);
                                            itemList.quantity = sisaQtyItem;
                                            itemList.resultTotal = itemList.harga * sisaQtyItem;
                                            itemList.diskon.forEach(function(diskonItem) {
                                                let valueDiskon = diskonItem.value *
                                                    itemList
                                                    .harga / 100;
                                                diskonItem.result = valueDiskon;
                                            });

                                            let randomId = generateRandomID();
                                            let dataItemPromo = JSON.parse(JSON.stringify(
                                                itemList));
                                            dataItemPromo.tmpId = randomId
                                            let resultTotalBaru = dataItemPromo.harga * parseInt(
                                                itemCondition.qty);
                                            dataItemPromo.quantity = parseInt(itemCondition.qty);
                                            dataItemPromo.resultTotal = resultTotalBaru;
                                            dataItemPromo.promo.push(item);
                                            dataItemPromo.queueItemId = queueId;

                                            if (item.type == "discount") {
                                                let reward = JSON.parse(item.reward);

                                                let satuanReward = Object.keys(reward[0])[0];
                                                if (satuanReward == "rupiah") {
                                                    let amount = reward[0].rupiah;

                                                    let dataDiscount = {
                                                        id: "promo",
                                                        nama: item.name,
                                                        satuan: satuanReward,
                                                        value: amount,
                                                        idPromo: item.id,
                                                    }

                                                    listDiskonAllItem.push(dataDiscount)
                                                } else {
                                                    let amount = reward[0].percent;
                                                    let resultDiskon = (itemList.harga * parseInt(
                                                        itemList.quantity) * amount) / 100;
                                                    console.log(itemList)
                                                    let dataDiscountPercent = {
                                                        id: "promo",
                                                        nama: item.name,
                                                        result: resultDiskon,
                                                        satuan: satuanReward,
                                                        tmpIdProduct: itemList.tmpId,
                                                        value: amount,
                                                        idPromo: item.id,
                                                    }

                                                    dataItemPromo.diskon.push(dataDiscountPercent);
                                                }
                                            }

                                            listItemPromo.push(dataItemPromo);
                                        }
                                    }
                                });

                            });

                        } else {
                            checkAvailablePromo = false;
                        }
                    });

                    if (promoCocok.length == 0) {
                        checkAvailablePromo = false;
                    }
                }

                if (promoCocok[0]) {
                    if (promoCocok[0].type == "free-item") {
                        console.log("masok free-item")
                        let baseUrl = `{{ route('kasir/chooseRewardItem', [':queue', ':idpromo']) }}`;
                        let url = baseUrl.replace(':queue', queueId).replace(':idpromo', promoCocok[0].id);

                        handleAjax(url, false).excute();
                    }
                }
            }

        }

        function syncItemCart() {
            syncPromo();
            syncAllItemInCart();
            syncSubTotal();
            syncPajak();
            syncDiskon();
            syncTotal();
            syncRounding();
            updateHargaFinalButton();

            if (listItem.length > 0 || listItemPromo.length > 0) {
                document.getElementById("produkKosong").style.display = "none";
                document.getElementById("summary").style.setProperty('display', 'block', 'important');
            } else {
                document.getElementById("produkKosong").style.display = "block";
                document.getElementById("summary").style.setProperty('display', 'none', 'important');
            }
        }

        function syncAllItemInCart() {
            $('#order-list').empty();
            listItem.forEach(function(item, index) {
                let html = '';
                if (item.openBillId) {
                    html = `
                    <div class="row mb-0 mt-2" >
                        <div class="col-6" data-tmpid="${item.tmpId}" onclick="handlerEditItem(this)">${item.namaProduct}   <small class="text-muted">x${item.quantity}</small></div>
                        <div class="col-5 text-end">${formatRupiah(item.resultTotal.toString(), "Rp. ")}</div>
                    </div>
                    `;
                } else {
                    html = `
                    <div class="row mb-0 mt-2">
                        <div class="col-6" data-tmpid="${item.tmpId}" onclick="handlerEditItem(this)">${item.namaProduct}   <small class="text-muted">x${item.quantity}</small></div>
                        <div class="col-5 text-end">${formatRupiah(item.resultTotal.toString(), "Rp. ")}</div>
                        <div class="col-1 text-end text-danger">
                            <button type="button" onclick="deleteItem(this)" data-tmpId="${item.tmpId}" class="btn btn-link btn-sm text-danger p-0 w-100">&times;</button>
                        </div>
                    </div>
                    `;
                }

                if (item.namaVariant) {
                    if (item.namaProduct != item.namaVariant) {
                        html += `
                        <div class="row mb-0 mt-0 variant" data-tmpId="${item.tmpId}">
                            <div class="col-6 text-muted">${item.namaVariant}</div>
                        </div>
                            `
                    }
                }

                if (item.modifier.length > 0) {
                    item.modifier.forEach(function(itemModifier, indexModifier) {
                        let hargaModifierKaliQuantity = itemModifier.harga * parseInt(item.quantity);
                        html += `
                        <div class="row mb-0 mt-0 modifier" data-tmpId="${item.tmpId}">
                            <div class="col-6 text-muted">${itemModifier.nama}</div>
                            <div class="col-5 text-end text-muted">${formatRupiah(hargaModifierKaliQuantity.toString(), "Rp. ")}</div>
                        </div>
                        `;
                    });
                }

                if (item.catatan != '') {
                    html += `
                        <div class="row mb-0 mt-0 catatan" data-tmpId="${item.tmpId}">
                            <div class="col-6 text-muted">${item.catatan}</div>
                        </div>
                        `;
                }

                $('#order-list').append(html);
            })

            listItemPromo.forEach(function(item, index) {
                let html = '';
                if (item.openBillId) {
                    html = `
                    <div class="row mb-0 mt-2">
                        <div class="col-6">${item.namaProduct}   <small class="text-muted">x${item.quantity}</small></div>
                        <div class="col-5 text-end">${formatRupiah(item.resultTotal.toString(), "Rp. ")}</div>
                    </div>
                    `;
                } else {
                    html = `
                    <div class="row mb-0 mt-2">
                        <div class="col-6">${item.namaProduct}   <small class="text-muted">x${item.quantity}</small></div>
                        <div class="col-5 text-end">${formatRupiah(item.resultTotal.toString(), "Rp. ")}</div>
                        <div class="col-1 text-end text-danger">
                            <button type="button" onclick="deleteItem(this)" data-tmpId="${item.tmpId}" class="btn btn-link btn-sm text-danger p-0 w-100">&times;</button>
                        </div>
                    </div>`;
                }

                if (item.namaProduct != item.namaVariant) {
                    html += `
                    <div class="row mb-0 mt-0 variant" data-tmpId="${item.tmpId}">
                        <div class="col-6 text-muted">${item.namaVariant}</div>
                    </div>
                        `
                }

                if (item.modifier.length > 0) {
                    item.modifier.forEach(function(itemModifier, indexModifier) {
                        let hargaModifierKaliQuantity = itemModifier.harga * parseInt(item.quantity);
                        html += `
                        <div class="row mb-0 mt-0 modifier" data-tmpId="${item.tmpId}">
                            <div class="col-6 text-muted">${itemModifier.nama}</div>
                            <div class="col-5 text-end text-muted">${formatRupiah(hargaModifierKaliQuantity.toString(), "Rp. ")}</div>
                        </div>
                        `;
                    });
                }

                if (item.promo.length > 0) {
                    item.promo.forEach(function(itemPromo, indexPromo) {
                        html += `
                        <div class="row mb-0 mt-0 promo" data-tmpId="${item.tmpId}">
                            <div class="col-6 text-muted">${itemPromo.name}</div>
                        </div>
                        `;
                    });
                }

                if (item.catatan != '') {
                    html += `
                        <div class="row mb-0 mt-0 catatan" data-tmpId="${item.tmpId}">
                            <div class="col-6 text-muted">${item.catatan}</div>
                        </div>
                        `;
                }

                $('#order-list').append(html);
            })

            listRewardItem.forEach(function(item, index) {
                let html = `
                <div class="row mb-0 mt-2" data-itempromoid=${item.idItemPromo}>
                    <div class="col-6">${item.namaProduct}   <small class="text-muted">x${item.quantity}</small></div>
                    <div class="col-5 text-end">Free</div>
                </div>
                `;

                if (item.namaProduct != item.namaVariant) {
                    html += `
                    <div class="row mb-0 mt-0 reward" data-itempromoid=${item.idItemPromo}">
                        <div class="col-6 text-muted">${item.namaVariant}</div>
                    </div>
                        `
                }

                if (item.modifier.length > 0) {
                    item.modifier.forEach(function(itemModifier, indexModifier) {
                        let hargaModifierKaliQuantity = itemModifier.harga * parseInt(item.quantity);
                        html += `
                        <div class="row mb-0 mt-0 modifier" data-itempromoid=${item.idItemPromo}">
                            <div class="col-6 text-muted">${itemModifier.nama}</div>
                            <div class="col-5 text-end text-muted">${formatRupiah(hargaModifierKaliQuantity.toString(), "Rp. ")}</div>
                        </div>
                        `;
                    });
                }

                if (item.promo.length > 0) {
                    item.promo.forEach(function(itemPromo, indexPromo) {
                        html += `
                        <div class="row mb-0 mt-0 promo" data-itempromoid=${item.idItemPromo}">
                            <div class="col-6 text-muted">${itemPromo.name}</div>
                        </div>
                        `;
                    });
                }

                if (item.catatan != '') {
                    html += `
                        <div class="row mb-0 mt-0 catatan" data-itempromoid=${item.idItemPromo}">
                            <div class="col-6 text-muted">${item.catatan}</div>
                        </div>
                        `;
                }

                $('#order-list').append(html);
            })
        }

        function syncSubTotal() {
            let tmpSubTotal = [];

            listItem.forEach(function(item, index) {
                tmpSubTotal.push(item.resultTotal);

                item.modifier.forEach(function(itemModifier, indexModifier) {
                    let modifierMultipleQuantity = itemModifier.harga * parseInt(item.quantity);
                    tmpSubTotal.push(modifierMultipleQuantity);
                });
            });

            listItemPromo.forEach(function(itemPromo, indexPromo) {
                tmpSubTotal.push(itemPromo.resultTotal);

                itemPromo.modifier.forEach(function(itemPromoModifier, indexPromoModifier) {
                    let itemPromoModifierMultipleQuantity = itemPromoModifier.harga * parseInt(itemPromo
                        .quantity);
                    tmpSubTotal.push(itemPromoModifierMultipleQuantity);
                });
            });

            var total = tmpSubTotal.reduce(function(acc, curr) {
                return acc + curr;
            }, 0);

            $('#sub-total').text(formatRupiah(total.toString(), "Rp. "));

            return total;
        }

        function syncPajak() {
            let dataPajak = @json($pajak);
            let pajakContainer = $('#pajak'); // Targetkan elemen dengan id 'pajak'
            pajakContainer.html(''); //kosongkan pajak

            // Iterasi melalui setiap elemen di dalam #pajak
            let tmpSubTotal = [];

            listItem.forEach(function(item, index) {
                if(!item.excludeTax){
                    tmpSubTotal.push(item.resultTotal);

                    item.modifier.forEach(function(itemModifier, indexModifier) {
                        tmpSubTotal.push(itemModifier.harga * item.quantity);
                    });

                    item.diskon.forEach(function(itemDiskon) {
                        tmpSubTotal.push(-itemDiskon.result);
                    });
                }
            });

            listItemPromo.forEach(function(item, index) {
                tmpSubTotal.push(item.resultTotal);

                item.modifier.forEach(function(itemModifier, indexModifier) {
                    tmpSubTotal.push(itemModifier.harga);
                });

                item.diskon.forEach(function(itemDiskon) {
                    tmpSubTotal.push(-itemDiskon.result);
                });
            });

            listDiskonAllItem.forEach(function(item, index){
                tmpSubTotal.push(-item.value);
            });

            console.log(tmpSubTotal);

            var subTotal = tmpSubTotal.reduce(function(acc, curr) {
                return acc + curr;
            }, 0);

            console.log(subTotal);
            let tmpTotalPajak = [];
            let tmpDataPajak = [];

            dataPajak.forEach(function(itemPajak, indexPajak) {
                let satuan = itemPajak.satuan; // Cek karakter terakhir (misalnya % atau lainnya)
                let amount = parseFloat(itemPajak.amount); // Ambil angka sebelum satuan

                let pajakValue = 0;

                // Hitung pajak berdasarkan satuan
                if (satuan === "%") {
                    pajakValue = (subTotal * amount) / 100; // Hitung jika persentase
                } else {
                    pajakValue = amount; // Jika satuan tetap (angka biasa)
                }

                let resultPajak = Math.round(pajakValue);

                // Format nilai pajak ke format rupiah
                let formattedPajak = formatRupiah(resultPajak.toString(), "Rp. ");

                // Buat elemen row dengan format yang sesuai
                let row = `
                    <div class="row mb-2">
                        <div class="col-6">${itemPajak.name} (${itemPajak.amount}${itemPajak.satuan})</div>
                        <input type="hidden" name="idPajak[]" value="${itemPajak.id}">
                        <div class="col-6 text-end value-pajak" id="pajak-${itemPajak.name}">
                            ${formattedPajak}
                        </div>
                    </div>
                `;

                // Append elemen row ke dalam div 'pajak'
                pajakContainer.append(row);

                let dataPajak = {
                    id: itemPajak.id,
                    name: itemPajak.name,
                    amount: amount,
                    satuan: satuan,
                    total: resultPajak
                }
                tmpDataPajak.push(dataPajak);
                tmpTotalPajak.push(resultPajak);
            });

            var totalPajak = tmpTotalPajak.reduce(function(acc, curr) {
                return acc + curr;
            }, 0);

            totalKeseluruhanPajak = totalPajak;

            listPajak = tmpDataPajak;
        }

        function generateListDiskon() {
            let dataDiskon = @json($discounts);

            listDiskon = dataDiskon;
            let diskonContainer = $('#Diskon');
            diskonContainer.html(''); //kosongkan container

            let totalItem = listItem.length
            dataDiskon.forEach(function(item, index) {
                let html = `
                <div class="list-group-item list-diskon d-flex align-items-center" data-type="${item.type_input}" data-id="${item.id}" data-satuan="${item.satuan}" data-amount="${item.amount}" data-name="${item.name}">
                    <div class="icon-box" data-text="${item.name}"></div>
                    <span class="ms-3" id="text-diskon-list">${item.name}</span>
                </div>`

                diskonContainer.append(html);
            });

        }

        function checkDiskonUsage() {
            let totalItem = listItem.length; // Total item di listItem
            let diskonContainer = $('#Diskon'); // Container untuk diskon

            if (totalItem == 0) {
                listDiskon.forEach(function(item) {
                    // Jika diskon dipakai oleh semua item, cari elemen terkait di HTML
                    let diskonElement = diskonContainer.find(`.list-diskon[data-id="${item.id}"]`);

                    // Tambahkan atribut disabled
                    diskonElement.attr('disabled', true);

                    // Tambahkan class text-muted pada span dengan id text-diskon-list
                    diskonElement.find('#text-diskon-list').addClass('text-muted');
                });

            } else {
                listDiskon.forEach(function(item) {

                    if (item.satuan == "percent") {
                        let tmpCheckId = []; // Temp untuk menyimpan ID diskon yang dipakai

                        // Iterasi melalui listItem untuk memeriksa diskon
                        listItem.forEach(function(itemData) {
                            itemData.diskon.forEach(function(diskonItemData) {
                                if (diskonItemData.id == item.id) {
                                    tmpCheckId.push(item.id); // Simpan jika diskon ditemukan
                                }
                            });
                        });

                        // Cek apakah diskon sudah digunakan oleh semua item
                        if (tmpCheckId.length == totalItem) {
                            // Jika diskon dipakai oleh semua item, cari elemen terkait di HTML
                            let diskonElement = diskonContainer.find(`.list-diskon[data-id="${item.id}"]`);

                            // Tambahkan atribut disabled
                            diskonElement.attr('disabled', true);

                            // Tambahkan class text-muted pada span dengan id text-diskon-list
                            diskonElement.find('#text-diskon-list').addClass('text-muted');
                        } else {
                            // Jika diskon tidak dipakai oleh semua item, pastikan elemen aktif
                            let diskonElement = diskonContainer.find(`.list-diskon[data-id="${item.id}"]`);
                            diskonElement.removeAttr('disabled');
                            diskonElement.find('#text-diskon-list').removeClass('text-muted');
                        }
                    } else {

                        if (listDiskonAllItem.length > 0) {
                            listDiskonAllItem.forEach(function(diskonAllItem, diskonAllIndex) {
                                if (item.id == diskonAllItem.id) {
                                    let diskonElement = diskonContainer.find(
                                        `.list-diskon[data-id="${item.id}"]`);

                                    // Tambahkan atribut disabled
                                    diskonElement.attr('disabled', true);

                                    // Tambahkan class text-muted pada span dengan id text-diskon-list
                                    diskonElement.find('#text-diskon-list').addClass('text-muted');
                                } else {
                                    //jika tidak dipakai
                                    let diskonElement = diskonContainer.find(
                                        `.list-diskon[data-id="${item.id}"]`);
                                    diskonElement.removeAttr('disabled');
                                    diskonElement.find('#text-diskon-list').removeClass('text-muted');
                                }
                            });
                        } else {
                            let diskonElement = diskonContainer.find(`.list-diskon[data-id="${item.id}"]`);
                            diskonElement.removeAttr('disabled');
                            diskonElement.find('#text-diskon-list').removeClass('text-muted');
                        }
                    }
                });
            }
        }

        function syncDiskon() {
            var tmpTotalDiskon = [];

            listItem.forEach(function(item, index) {
                item.diskon.forEach(function(itemDiskon, indexDiskon) {
                    // console.log(itemDiskon);
                    // let diskonMultipleQuantity =
                    tmpTotalDiskon.push(itemDiskon.result);
                });
            });

            listItemPromo.forEach(function(item, index) {
                item.diskon.forEach(function(itemDiskon, indexDiskon) {
                    // console.log(itemDiskon);
                    // let diskonMultipleQuantity =
                    tmpTotalDiskon.push(itemDiskon.result);
                });
            });

            listDiskonAllItem.forEach(function(itemDiskonAllItem, indexDiskonAllItem) {
                tmpTotalDiskon.push(itemDiskonAllItem.value);
            });

            var totalDiskon = tmpTotalDiskon.reduce(function(acc, curr) {
                return acc + curr;
            }, 0);

            resultNominalDiskon = totalDiskon;

            let bulatkanDiskon = Math.round(totalDiskon);
            if (bulatkanDiskon > 0) {
                $("#group-diskon").removeClass('d-none');
            } else {
                $("#group-diskon").addClass('d-none');
            }

            $('#diskon').text("-" + formatRupiah(bulatkanDiskon.toString(), "Rp. "));

            return bulatkanDiskon;
        }

        function syncTotal() {
            let subTotal = syncSubTotal();
            let diskon = syncDiskon();
            let pajak = totalKeseluruhanPajak;

            let total = subTotal + pajak - diskon;

            document.getElementById("total").innerText = formatRupiah(total.toString(), "Rp. ");
        }

        function syncRounding() {
            let dataRounding = @json($rounding);
            if (dataRounding) {
                let dataRounded = dataRounding.rounded;
                if (dataRounded == "true") {
                    $("#group-rounding").removeClass('d-none');

                    let dataRoundBenchmark = parseInt(dataRounding.rounded_benchmark);
                    let roundedType = parseInt(dataRounding.rounded_type);

                    // Ambil angka total
                    let total = document.getElementById("total").textContent;
                    let totalText = total.trim();
                    let angkaTotal = parseInt(totalText.replace(/[^\d]/g, ""));

                    // Ambil bagian belakang dan depan angka
                    let angkaBelakang = angkaTotal % roundedType; // Sisa pembagian (angka belakang)
                    let angkaDepan = Math.floor(angkaTotal / roundedType); // Angka depan

                    let hasilRounded = 0;
                    let rounded = '';

                    if (angkaBelakang < 500) {
                        let hasil = 500 - angkaBelakang;
                        rounded = '-';
                    } else {
                        let hasil = 500 - angkaBelakang;
                        rounded = "+";
                    }

                    // Perhitungan Sebelumnya yang salah pemahaman
                    // if (angkaBelakang > 500) {
                    //     if (angkaBelakang > dataRoundBenchmark) {
                    //         console.log("masuk tahap 1")
                    //         let hasil = 1000 - angkaBelakang;
                    //         hasilRounded = Math.abs(hasil);
                    //         rounded = '+';
                    //     } else {
                    //         console.log("masuk tahap 2")
                    //         let hasil = 500 - angkaBelakang;
                    //         hasilRounded = -Math.abs(hasil);
                    //         rounded = '-';
                    //     }
                    // } else {
                    //     if (angkaBelakang > dataRoundBenchmark) {
                    //         console.log("masuk tahap 3")
                    //         let hasil = 500 - angkaBelakang;
                    //         hasilRounded = Math.abs(hasil);
                    //         rounded = '+';
                    //     } else {
                    //         console.log("masuk tahap 4x")
                    //         let hasil = 500 - angkaBelakang;
                    //         hasilRounded = Math.abs(hasil);
                    //         rounded = '+';
                    //     }
                    // }


                    // **
                    // BENCHMARK BAWAAN MOKA
                    // *
                    // if (angkaBelakang > dataRoundBenchmark) {
                    //     // Jika bagian belakang lebih besar dari benchmark, bulatkan ke atas
                    //     hasilRounded = roundedType - angkaBelakang;
                    //     rounded = "+";
                    // } else {
                    //     // Jika bagian belakang lebih kecil/sama, bulatkan ke bawah
                    //     hasilRounded = -angkaBelakang;
                    //     rounded = "-";
                    // }

                    // Update nilai pembulatan ke elemen
                    $('#rounding').text(rounded + formatRupiah(hasilRounded.toString(), "Rp. "));
                    tandaRounding = rounded;
                    amountRounding = hasilRounded
                }
            } else {
                tandaRounding = ''
                amountRounding = 0;
                $("#group-rounding").addClass('d-none');
            }
        }

        function handleAjax(url, primaryModal = true, method = 'get') {
            return {
                excute: function() {
                    return new Promise((resolve, reject) => {
                        $.ajax({
                            url,
                            method,
                            beforeSend: function() {
                                showLoader();
                                // showLoading()
                            },
                            complete: function() {
                                showLoader(false);
                                // hideLoading(false)
                            },
                            success: (res) => {
                                if (primaryModal) {
                                    const modal = $('#itemModal');
                                    modal.html(res);
                                    modal.modal({
                                        backdrop: 'static',
                                        keyboard: true
                                    });
                                    modal.modal('show');
                                } else {
                                    const modal = $('#promoModal');
                                    modal.html(res);
                                    modal.modal({
                                        backdrop: 'static',
                                        keyboard: true
                                    });
                                    modal.modal('show');
                                }

                                resolve(res); // Resolving the promise
                            },
                            error: function(err) {
                                console.log(err);
                                reject(err); // Rejecting the promise on error
                            }
                        });
                    });
                }
            };
        }

        // Fungsi untuk mendapatkan teks item yang dipotong
        function getTruncatedItemText(itemTransactions) {
            return itemTransactions.map(itemTransaction => {
                console.log(itemTransaction);
                if(itemTransaction.product){
                return itemTransaction.product.name === itemTransaction.variant.name
                    ? itemTransaction.product.name
                    : `${itemTransaction.product.name} - ${itemTransaction.variant.name}`;
                }
            }).join(', ');
        }

        // Fungsi untuk mendapatkan ikon berdasarkan tipe pembayaran
        function getPaymentIcon(paymentType) {
            return paymentType === "Cash"
                ? '<i class="fa-solid fa-money-bill" style="font-size: 35px;"></i>'
                : '<i class="fa-solid fa-money-check" style="font-size: 35px;"></i>';
        }

        // Fungsi untuk membuat HTML transaksi
        function createTransactionHTML(transaction, truncateItemText, paymentIcon, index) {
            if(index == 0){
                return `
                    <div class="card-custom list-transaction mt-2 card-active" data-id="${transaction.id}">
                        <div class="card-custom-body">
                            <div class="container">
                                <div class="row d-flex">
                                    <div class="col-2 d-flex justify-content-between align-items-center">
                                        ${paymentIcon}
                                    </div>
                                    <div class="col-8">
                                        <p>${formatRupiah(transaction.total.toString(), "Rp. ")}</p>
                                        <p class="list-product-transaction">${truncateItemText ? truncateItemText : 'custom'}</p>
                                    </div>
                                    <div class="col-2" id="time-transaction">
                                        ${transaction.created_time}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>`;
            }else{
                return `
                    <div class="card-custom list-transaction mt-2" data-id="${transaction.id}">
                        <div class="card-custom-body">
                            <div class="container">
                                <div class="row d-flex">
                                    <div class="col-2 d-flex justify-content-between align-items-center">
                                        ${paymentIcon}
                                    </div>
                                    <div class="col-8">
                                        <p>${formatRupiah(transaction.total.toString(), "Rp. ")}</p>
                                        <p class="list-product-transaction">${truncateItemText ? truncateItemText : 'custom'}</p>
                                    </div>
                                    <div class="col-2" id="time-transaction">
                                        ${transaction.created_time}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>`;
            }
        }

        // Fungsi untuk melampirkan event click pada transaksi
        function attachTransactionClickEvent() {
            $('.list-transaction').off().on('click', function() {
                $('.list-transaction').removeClass('card-active');
                $(this).addClass('card-active');
                let idTransactionChoose = $(this).data('id');

                let dataTransaction = listActivityTransaction.find(item => item.id === idTransactionChoose);
                if(dataTransaction) detailTransactionHandle(dataTransaction);
            });
        }

        function detailTransactionHandle(data){
            $('#metode-pembayaran').text(data.nama_tipe_pembayaran)
            $('#waktu-pembelian').text(data.created_tanggal +' pada ' + data.created_time);

            $('#btn-print-history-transaction').removeClass('disabled');
            $('#btn-print-history-transaction').attr('href', 'intent://struk-history-print?id=' + data.id);

            $('#btn-resend-receipt').removeClass('disabled');
            $('#btn-resend-receipt').attr('data-id', data.id);
            var subTotalTransaction = 0;

            $('#row-product').empty();
            data.item_transaction.forEach(function(item, index){
                console.log(item);
                // var nameProductTransaction = item.product.name == item.variant.name ? item.product.name : item.product.name + ' - ' + item.variant.name;
                var nameProductTransaction = item.product ? (item.product.name == item.variant.name ? item.product.name : item.product.name + ' - ' + item.variant.name) : 'custom';
                var modifierTransactionJson = JSON.parse(item.modifier_id);
                var inisialNameBox = item.product ? item.product.name : 'custom';
                var hargaItem = item.variant ? formatRupiah(item.variant.harga.toString(), "Rp. ") : (item.harga ? formatRupiah(item.harga.toString(), "Rp. ") : "Rp.");

                var htmlListProductTransaction = `
                            <div class="col-2 icon-box" data-text="${inisialNameBox}"></div>
                            <div class="col-5 pt-2">
                                <span>${nameProductTransaction}</span>
                                <br>
                                ${modifierTransactionJson.map(function(modifier) {
                                    return `<span style="color:gray;">${modifier.nama}</span><br> `;}).join('')}
                            </div>
                            <div class="col-5 text-end">
                                <span>${hargaItem}</span>
                                <br>
                                ${modifierTransactionJson.map(function(modifier) {
                                    return `<span style="color:gray;">${formatRupiah(modifier.harga.toString(), "Rp. ")}</span><br> `;}).join('')}
                            </div>
                `;
                $('#row-product').append(htmlListProductTransaction);

                subTotalTransaction += item.harga ? item.harga : (item.variant ? item.variant.harga : 0);

                syncIconBoxes();
            });

            var resultJsonPajak = JSON.parse(data.total_pajak);
            $('#row-pajak').empty();
            resultJsonPajak.forEach(function(itemPajak){
                var htmlPajakTranasaction = `
                    <div class="col-6">${itemPajak.name} (${itemPajak.amount}%)</div>
                    <div class="col-6 d-flex align-items-center justify-content-end">${formatRupiah(itemPajak.total.toString(), "Rp. ")}</div>
                `

                $('#row-pajak').append(htmlPajakTranasaction);
            });

            subTotalTransaction += data.total_modifier;

            $('#subtotal').text(formatRupiah(subTotalTransaction.toString(), "Rp. "))
            $('#diskon-transaction').text("-"+formatRupiah(data.total_diskon.toString(), "Rp. "));
            $('#total-transaction').text(formatRupiah(data.total.toString(), "Rp. "))
            $('#pembayaran-transaction').text(formatRupiah(data.nominal_bayar.toString(), "Rp. "))
            $('#kembalian-transaction').text(formatRupiah(data.change.toString(), "Rp. "))
        }

        // Fungsi untuk memfilter transaksi berdasarkan waktu
        function filterTransactions(filterTime) {
            $('.list-transaction').each(function() {
                var transactionTime = $(this).find('#time-transaction').text().trim(); // Mendapatkan nilai waktu dari elemen waktu

                // Memeriksa apakah waktu transaksi cocok dengan waktu filter atau apakah filter kosong
                if (transactionTime === filterTime || filterTime === '') {
                    $(this).show(); // Menampilkan transaksi
                } else {
                    $(this).hide(); // Menyembunyikan transaksi
                }
            });
        }

        function syncIconBoxes(){
            var iconBoxes = document.querySelectorAll('.icon-box');

            iconBoxes.forEach((box) => {
                const text = box.getAttribute('data-text');

                if (text) {
                    // Pisahkan kata dan ambil maksimal 2 kata pertama
                    const words = text.split(' ');
                    const initials = words.slice(0, 2).map(word => word[0]).join('');
                    box.textContent = initials; // Isi kotak dengan inisial
                }
            });
        }

        function loadHistoryShifts(page) {
            let baseUrlHistoryShift = `{{ route('kasir/historyShift', ':outletid') }}`; // Placeholder ':id'
            let outletTerpilih = JSON.parse(dataLogin.outlet_id);
            let urlHistoryShift = baseUrlHistoryShift.replace(':outletid', outletTerpilih[0]); // Ganti ':id' dengan nilai dataId

            $.ajax({
                url: urlHistoryShift,
                method: 'GET',
                success: function(data) {
                    $('#shiftList').empty();
                    data.forEach(shift => {
                        let shiftCloseResult = shift.close ? shift.close : "Masih Berjalan";

                        $('#shiftList').append(`<div class="list-group-item-action" onclick="handleDetailShift(this)" data-idshift="${shift.id}">` + shift.open + ' - ' + shiftCloseResult + '</div> <hr>');
                    });

                    if (!data.next_page_url) {
                        $('#load-more').hide(); // Sembunyikan tombol jika tidak ada halaman berikutnya
                    }
                }
            });
        }

        function sendDataInCart() {
            if (listItem.length > 0 || listItemPromo.length > 0) {
                if(dataPattyCash.length > 0){
                    // Contoh data JSON yang akan dikirim
                    console.log(dataPattyCash[0].outlet);
                    var data = {
                        listItem: listItem,
                        outlet: dataPattyCash[0].outlet,
                        userCollect: dataPattyCash[0].user_started,
                        listPajak: listPajak,
                        nominalDiskon: resultNominalDiskon,
                        nominalRounding: amountRounding,
                    };

                    console.log(data);

                    // Mengonversi objek JavaScript menjadi string JSON
                    var jsonData = JSON.stringify(data);

                    // Mengirim data ke aplikasi Android
                    if (window.Android) {
                        // Panggil metode JavaScript Interface dengan ID transaksi
                        window.Android.handleCetakBillNotReceipt(jsonData);
                    }
                }else{
                    iziToast['error']({
                    title: "Gagal",
                    message: "Open Shift Terlebih Dahulu",
                    position: 'topRight'
                });
                }
            } else {
                iziToast['error']({
                    title: "Gagal",
                    message: "Product Belum Dipilih",
                    position: 'topRight'
                });
            }
        }

        function handleDetailShift(element){
            let widget = $(element);
            let idShift = widget.data('idshift');

            $()
            $('#detail-shift-history').removeClass('d-none');
            $('#history-shift-menu').addClass('d-none');
            $('#back-btn-setting').attr('data-section', 'detail-history-shift');
            $('#btn-print-history-shift').attr('href', 'intent://shift-order-print?id=' + idShift);
            const baseUrlDetailHistoryShift = `{{ route('kasir/detailHistoryShift', ':shiftid') }}`; // Placeholder ':id'
            const urlDetailHistoryShift = baseUrlDetailHistoryShift.replace(':shiftid', idShift); // Ganti ':id' dengan nilai dataId


            $.ajax({
                url:  urlDetailHistoryShift,
                method: "GET",
                beforeSend: function() {
                    showLoader();
                },
                complete: function() {
                    showLoader(false);
                },
                success: (res) => {
                    console.log(res);
                    listSoldItem = res.soldItem;

                    let soldItemShift = 0;
                    let openNameShift = res.data.user_started.name;
                    let closeNameShift = res.data.user_ended ? res.data.user_ended.name : '-';
                    $('#txt-detail-open-patty-cash').text(openNameShift);
                    $('#txt-detail-close-patty-cash').text(closeNameShift);

                    let namaOutlet = res.data.outlet.name;
                    $('#txt-detail-outlet').text(namaOutlet);

                    let detailStartingShift = res.data.open;
                    $('#txt-detail-starting-shift').text(detailStartingShift);

                    let detailEndedShift = res.data.close;
                    $('#txt-detail-ending-shift').text(detailEndedShift)

                    let totalSoldItem = 0;
                    res.soldItem.forEach(function(itemSold){
                        totalSoldItem += itemSold.total_transaction;
                    });

                    $('#txt-detail-sold-items').text(totalSoldItem);

                    let totalSeluruhTipe = parseInt(res.data.amount_awal);


                    $('#container-detail-shift .history-category-payment').remove();
                    res.listCategoryPayment.forEach(function(item) {
                        if (item.name == 'Cash') {
                            let sales = 0;
                            let expectedEndingCash = parseInt(res.data.amount_awal);

                            soldItemShift += item.transactions.length;
                            item.transactions.forEach(function(cashTransaction) {
                                sales += cashTransaction.total;
                                expectedEndingCash += parseInt(cashTransaction.total);

                                totalSeluruhTipe += parseInt(cashTransaction.total);
                            })

                            let actualEndingCash = parseInt(res.data.amount_akhir);
                            let differenceCash = expectedEndingCash - actualEndingCash;

                            let html = `
                                <div class="row mt-3 history-category-payment">
                                    <div class="col-12">
                                        <h5>${item.name}</h5>
                                        <hr>

                                        <div class="row">
                                            <div class="col-6">
                                                Starting Cash In Drawer
                                            </div>
                                            <div class="col-6" >
                                                ${formatRupiah(res.data.amount_awal.toString(), "Rp. ")}
                                            </div>
                                        </div>
                                        <hr>

                                        <div class="row">
                                            <div class="col-6">
                                                Cash Sales
                                            </div>
                                            <div class="col-6" >
                                                ${formatRupiah(sales.toString(), "Rp. ")}
                                            </div>
                                        </div>
                                        <hr>

                                        <div class="row">
                                            <div class="col-6">
                                                Expected Ending Cash
                                            </div>
                                            <div class="col-6" >
                                                ${formatRupiah(expectedEndingCash.toString(), "Rp. ")}
                                            </div>
                                        </div>
                                        <hr>

                                        <div class="row">
                                            <div class="col-6">
                                                Actual Ending Cash
                                            </div>
                                            <div class="col-6" >
                                                ${formatRupiah(actualEndingCash.toString(), "Rp. ")}
                                            </div>
                                        </div>
                                        <hr>

                                        <div class="row">
                                            <div class="col-6">
                                                Cash Differences
                                            </div>
                                            <div class="col-6" >
                                                ${formatRupiah(differenceCash.toString(), "Rp. ")}
                                            </div>
                                        </div>
                                        <hr>

                                    </div>

                                </div>

                            `
                            $('#container-detail-shift').append(html);
                        } else {
                            let totalPerCategory = 0;
                            let html = `
                                <div class="row mt-3 history-category-payment">
                                    <div class="col-12">
                                        <h5>${item.name}</h5>
                                        <hr>

                                    ${item.payment.map(function(itemPayment) {
                                        let total = 0;
                                        soldItemShift += itemPayment.transactions.length;
                                        itemPayment.transactions.forEach(function(transactionPaymentItem){
                                            total += transactionPaymentItem.total;
                                            totalPerCategory += transactionPaymentItem.total;

                                            totalSeluruhTipe += parseInt(transactionPaymentItem.total);
                                        })
                                        return `<div class="row" >
                                                    <div class="col-6" >
                                                        ${itemPayment.name}
                                                    </div>
                                                    <div class="col-6" >
                                                        ${formatRupiah(total.toString(), "Rp. ")}
                                                    </div>
                                                </div>
                                                <hr> `;}).join('')}

                                        <div class="row">
                                            <div class="col-6">
                                                Expected ${item.name} Payment
                                            </div>
                                            <div class="col-6" >
                                                ${formatRupiah(totalPerCategory.toString(), "Rp. ")}
                                            </div>
                                        </div>
                                        <hr>

                                    </div>

                                </div>

                            `;


                            $('#container-detail-shift').append(html);
                        }

                    });

                    let totalHtml = `<div class="row mt-3 history-category-payment">
                                    <div class="col-12">
                                        <h5>Total</h5>
                                        <hr>

                                        <div class="row">
                                            <div class="col-6">
                                                Total Expected
                                            </div>
                                            <div class="col-6" >
                                                ${formatRupiah(totalSeluruhTipe.toString(), "Rp. ")}
                                            </div>
                                        </div>
                                        <hr>

                                    </div>

                                </div>`

                    $('#container-detail-shift').append(totalHtml);

                },
                error: function(err) {
                    console.log(err);
                    reject(err); // Rejecting the promise on error
                }
            });
        }

        $('.list-sold-item').on('click', function(){
            let dataSession = $(this).data('session');
            let html = "";
            if(dataSession == "sold-item"){
                $('#shift-menu').addClass('d-none');
                $('#list-sold-item').removeClass('d-none');
                $('#back-btn-setting').attr('data-section', 'list-sold-item');

                listExistingSoldItem.forEach(function(item){
                    let nameProduct = item.product.name == item.name ? item.name : item.product.name + " - " + item.name;

                    html += `<div class="row">
                                    <div class="col-6">
                                        ${nameProduct}
                                    </div>
                                    <div class="col-6 text-center" id="txt-detail-open-patty-cash">
                                        ${item.total_transaction}
                                    </div>
                                </div>
                                <hr>`;

                });
            }else{
                $('#detail-shift-history').addClass('d-none');
                $('#list-sold-item').removeClass('d-none');
                $('#back-btn-setting').attr('data-section', 'history-sold-item');

                listSoldItem.forEach(function(item){
                    let nameProduct = item.product.name == item.name ? item.name : item.product.name + " - " + item.name;

                    html += `<div class="row">
                                    <div class="col-6">
                                        ${nameProduct}
                                    </div>
                                    <div class="col-6 text-center" id="txt-detail-open-patty-cash">
                                        ${item.total_transaction}
                                    </div>
                                </div>
                                <hr>`;

                });
            }
            $('#container-sold-item').append(html)
        });

        function handlerEditItem(widget){
            let quantity = document.getElementById('quantity-edit');

            $('#listVariantEdit').empty();
            $('#listPilihanEdit').empty();
            $('#salesTypeEdit').empty();
            $('#listModifierEdit').empty();
            $('#listDiskonEdit').empty();

            let item = $(widget);
            let itemTmpId = item.attr('data-tmpid');

            let dataItem = listItem.find(item => item.tmpId == itemTmpId);

            console.log(dataItem);
            hargaAkhirEditItem = dataItem.harga;

            variantIdEdit = dataItem.idVariant;
            variantNameEdit = dataItem.namaVariant;

            $('#namaProductEdit').html(dataItem.namaProduct);
            $('#totalHargaItemEdit').html(formatRupiah(dataItem.resultTotal.toString(), "Rp. "));
            $('#quantity-edit').val(dataItem.quantity);
            $('#catatanEdit').val(dataItem.catatan);

            if(dataItem.listVariant && dataItem.listVariant.length > 1){
                // Membuat container utama seperti div mb-4 dengan label dan small
               let containerVariant = $(`
                   <div class="mb-4">
                       <label for="quantity" class="form-label"><strong>Variants</strong></label> |
                       <small>Single Choose</small>
                       <div class="row mt-1"></div>
                   </div>
               `);

               // Ambil div row di dalam container untuk append tombol variant
                let rowDiv = containerVariant.find('div.row');

                dataItem.listVariant.forEach(function(variant){
                    let variantHtml = $(`
                        <div class="form-group col-6 mt-2">
                            <button class="btn w-100 btn-xl btn-outline-primary btn-variant-edit"
                                data-variantid="${variant.id}" data-harga="${variant.harga}" data-name="${variant.name}">
                                <div class="row">
                                    <div class="col-6 me-auto">
                                        ${variant.name} (${variant.stok})
                                    </div>
                                    <div class="col-4 ms-auto pe-0">
                                        ${formatRupiah(variant.harga.toString(), 'Rp. ')}
                                    </div>
                                </div>
                            </button>
                        </div>
                    `);

                    if(dataItem.idVariant == variant.id){
                        variantHtml.find('button.btn-variant-edit').addClass('active');
                    }
                    rowDiv.append(variantHtml);
                });

                // Append container ke elemen dengan id listVariantEdit
                $('#listVariantEdit').append(containerVariant);

            }

            if(dataItem.listPilihan && dataItem.listPilihan.length > 0){

                // Render Pilihan (Choose Many)
                dataItem.listPilihan.forEach(function(pilihan){
                    let containerPilihan = $(`
                        <div class="mb-4">
                            <label for="quantity" class="form-label"><strong>${pilihan.name}</strong></label> |
                            <small>Choose Many</small>
                            <div class="row mt-1"></div>
                        </div>
                    `);

                    let rowDivPilihan = containerPilihan.find('div.row');

                    // Asumsi pilihan.pilihans adalah array pilihan di dalam listPilihan
                    pilihan.pilihans.forEach(function(data){
                        let pilihanHtml = $(`
                            <div class="col-6 mt-2">
                                <div class="custom-card">
                                    <span>${data.name} <small>(${formatRupiah(data.harga.toString(), 'Rp. ')})</small></span>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input form-pilihan-edit form-switch"
                                            value="${data.harga}" type="checkbox" data-id="${data.id}" data-name="${data.name}">
                                    </div>
                                </div>
                            </div>
                        `);

                        if(dataItem.pilihan && dataItem.pilihan.length > 0){
                            dataItem.pilihan.forEach(function(pilihanChoosed){
                                if(data.id == pilihan.pilihanChoosed.id){
                                    pilihanHtml.find('input.form-pilihan-edit').checked;
                                }
                            });
                        }
                        rowDivPilihan.append(pilihanHtml);
                    });

                    $('#listPilihanEdit').append(containerPilihan);
                });
            }

            // Render Sales Type (Single Choose)
            if(dataItem.listSalesType && dataItem.listSalesType.length > 0){
                let containerSalesType = $(`
                    <div class="mb-4">
                        <label for="quantity" class="form-label"><strong>Sales Type</strong></label> |
                        <small>Single Choose</small>
                        <div class="row mt-1"></div>
                    </div>
                `);

                let rowDivSalesType = containerSalesType.find('div.row');

                dataItem.listSalesType.forEach(function(salesType){
                    let salesTypeHtml = $(`
                        <div class="form-group col-md-6 mt-2">
                            <button class="btn w-100 btn-xl btn-outline-primary btn-sales-type-edit"
                                data-salestypeid="${salesType.id}" data-salestypename="${salesType.name}">
                                <div class="row">
                                    <div class="col-6 me-auto">
                                        ${salesType.name}
                                    </div>
                                </div>
                            </button>
                        </div>
                    `);

                    if(dataItem.salesType == salesType.id){
                        salesTypeHtml.find('button.btn-sales-type-edit').addClass('active');
                        salesTypeIdEdit = dataItem.salesType;
                    }
                    rowDivSalesType.append(salesTypeHtml);
                });

                $('#salesTypeEdit').append(containerSalesType);
            }

            // Render Modifier (Choose Many)
            dataItem.listModifier.forEach(function(dataModifier){
                let containerModifier = $(`
                    <div class="mb-4">
                        <label for="quantity" class="form-label"><strong>${dataModifier.name}</strong></label> |
                        <small>Choose Many</small>
                        <div class="row mt-1"></div>
                    </div>
                `);

                let rowDivModifier = containerModifier.find('div.row');

                dataModifier.modifier.forEach(function(data){
                    let modifierHtml = $(`
                        <div class="col-md-6 mt-2">
                            <div class="custom-card">
                                <span>${data.name} <small>(${formatRupiah(data.harga.toString(), 'Rp. ')})</small></span>
                                <div class="form-check form-switch">
                                    <input class="form-check-input form-modifier-edit form-switch"
                                        value="${data.harga}" type="checkbox" data-id="${data.id}" data-name="${data.name}">
                                </div>
                            </div>
                        </div>
                    `);
                    dataItem.modifier.forEach(function(modifierChoosed){
                        if(modifierChoosed.id == data.id){
                            modifierHtml.find('input.form-modifier-edit').attr('checked', true)
                        }
                    });
                    rowDivModifier.append(modifierHtml);
                });


                $('#listModifierEdit').append(containerModifier);
            });

            // Render Diskon (Discount)
            if(dataItem.listDiskon && dataItem.listDiskon.length > 0){
                let containerDiskon = $(`
                    <div class="mb-4">
                        <label class="form-label"><strong>Diskon</strong></label>
                        <div class="row mt-1"></div>
                    </div>
                `);

                let rowDivDiskon = containerDiskon.find('div.row');

                dataItem.listDiskon.forEach(function(discount){
                    let discountLabel = '';
                    if(discount.satuan === 'rupiah'){
                        discountLabel = `(${formatRupiah(discount.amount.toString(), 'Rp. ')})`;
                    } else {
                        discountLabel = `(%${discount.amount})`;
                    }

                    let diskonHtml = $(`
                        <div class="col-md-6 mt-2">
                            <div class="custom-card">
                                <span>${discount.name} ${discountLabel}</span>
                                <div class="form-check form-switch">
                                    <input class="form-check-input form-diskon-edit form-switch"
                                        value="${discount.amount}" data-type="${discount.satuan}"
                                        data-name="${discount.name}" type="checkbox"
                                        data-id="${discount.id}" id="discount-${discount.id}">
                                </div>
                            </div>
                        </div>
                    `);

                    dataItem.diskon.forEach(function(diskonChoosed){
                        if(diskonChoosed.id == discount.id){
                            diskonHtml.find('input.form-diskon-edit').attr('checked', true);
                        }
                    });
                    rowDivDiskon.append(diskonHtml);
                });

                $('#listDiskonEdit').append(containerDiskon);
            }

            updateHargaAkhirEditItem();

            // seluruh fungsi button edit disini
            $('.btn-variant-edit').off().on('click', function() {
                console.log("masuk variant edit")
                $('.btn-variant-edit').removeClass('active');
                $(this).addClass('active')

                let id = $(this).data('variantid');
                let harga = $(this).data('harga');
                let name = $(this).data('name');

                variantIdEdit = id;
                hargaAkhirEditItem = harga;
                variantNameEdit= name;

                updateHargaAkhirEditItem();
            });

            // Event listener untuk tombol decrement
            $('#decrement-edit').off().on('click', function(){
                const value = parseInt($('#quantity-edit').val());
                if (value > 1) {
                    $('#quantity-edit').val(value - 1);
                    updateHargaAkhirEditItem();
                }
            });

            // Event listener untuk tombol increment
            $('#increment-edit').off().on('click', function(){
                const value = parseInt($('#quantity-edit').val());
                $('#quantity-edit').val(value + 1);
                updateHargaAkhirEditItem();
            });

            $('.btn-sales-type-edit').off().on('click', function() {
                $('.btn-sales-type-edit').removeClass('active');
                $(this).addClass('active')

                let id = $(this).attr('data-salestypeid');
                let name = $(this).attr('data-salestypename');

                salesTypeIdEdit = id;

                updateHargaAkhir();
            });

            $('.form-modifier-edit').off().on('change', function() {
                updateHargaAkhirEditItem();
            });

            $('.form-diskon-edit').off().on('change', function(){
                updateHargaAkhirEditItem();
            });

            $('#saveItemToCartEdit').off().on('click', function(){
                // DISKON
                let dataDiskonId = listDiskonIdEdit;
                let dataDiskonNama = listDiskonNameEdit;
                let dataDiskonHarga = listDiskonAmountEdit;
                let dataDiskonValue = listDiskonValueEdit;
                let dataDiskonType = listDiskonTypeEdit;

                let dataDiskon = [];

                // console.log(dataDiskonNama);
                for (let i = 0; i < dataDiskonId.length; i++) {
                    let tmpDataDiskon = {
                        tmpIdProduct: dataItem.tmpId,
                        id: dataDiskonId[i],
                        nama: dataDiskonNama[i],
                        satuan: dataDiskonType[i],
                        value: dataDiskonValue[i],
                        result: dataDiskonHarga[i],
                    };

                    dataDiskon.push(tmpDataDiskon);
                }

                // MODIFIER
                let dataModifierId = listModifierIdEdit;
                let dataModifierNama = listModifierNameEdit;
                let dataModifierHarga = listModifierHargaEdit;

                let dataModifier = [];
                for (let x = 0; x < dataModifierId.length; x++) {
                    let tmpDataModifier = {
                        tmpIdProduct: dataItem.tmpId,
                        id: dataModifierId[x],
                        nama: dataModifierNama[x],
                        harga: dataModifierHarga[x],
                    }

                    dataModifier.push(tmpDataModifier);
                }

                // Pilihan
                let dataPilihanId = listPilihanIdEdit;
                let dataPilihanNama = listPilihanNameEdit;
                let dataPilihanHarga = listPilihanHargaEdit;

                let dataPilihan = [];
                for(let i = 0; i < dataPilihanId.length; i++){
                    let tmpDataPilihan = {
                        tmpIdProduct: dataItem.tmpId,
                        id: dataPilihanId[i],
                        nama: dataPilihanNama[i],
                        harga: dataPilihanHarga[i],
                    }

                    dataPilihan.push(tmpDataPilihan);
                }

                let totalHargaProduct = hargaAkhirEditItem * parseInt($('#quantity-edit').val());

                dataItem.quantity = $('#quantity-edit').val();
                dataItem.diskon = dataDiskon;
                dataItem.salesType = salesTypeIdEdit;
                dataItem.idVariant = variantIdEdit;
                dataItem.namaVariant = variantNameEdit;
                dataItem.modifier = dataModifier;
                dataItem.pilihan = dataPilihan;
                dataItem.harga = hargaAkhirEditItem;
                dataItem.resultTotal = totalHargaProduct;
                dataItem.catatan = $('#catatanEdit').val();

                syncItemCart();

                hargaAkhirEditItem = 0;
                const modal = $('#modalEditPesanan');
                modal.modal('hide');

            })


            const modalEdit = $('#modalEditPesanan');
            modalEdit.modal({
                backdrop: 'static',
                keyboard: true
            });
            modalEdit.modal('show');

        }

        function updateHargaAkhirEditItem() {
            const quantityValue = parseInt($('#quantity-edit').val());
            const totalModifier = hitungModifierEditItem();
            const totalPilihan = hitungPilihanEditItem();
            const totalDiskon = hitungDiskonEditItem();

            // Hitung harga akhir setelah diskon
            const hargaSebelumDiskon = hargaAkhirEditItem * quantityValue;
            hargaAkhir = hargaSebelumDiskon - totalDiskon + totalModifier + totalPilihan;

            // Pastikan harga tidak negatif

            if (hargaAkhir < 0) {
                hargaAkhir = 0;
            }

            let resultHargaAkhir = Math.round(hargaAkhir);
            // Update harga pada elemen HTML
            $('#totalHargaItemEdit').text(formatRupiah(resultHargaAkhir.toString(), "Rp. "));
        }

        function hitungModifierEditItem() {
            let modifierCheckboxesEdit = document.querySelectorAll('.form-modifier-edit');
            let totalModifier = 0;
            let totalModifierId = [];
            let totalModifierName = [];
            let totalModifierHarga = [];

            modifierCheckboxesEdit.forEach((checkbox) => {
                if (checkbox.checked) {
                    const amount = parseFloat(checkbox.value);
                    const id = checkbox.dataset.id;
                    const name = checkbox.dataset.name;
                    totalModifier += parseInt($('#quantity-edit').val()) * amount;

                    totalModifierId.push(id);
                    totalModifierHarga.push(amount);
                    totalModifierName.push(name);
                }
            });

            console.log(totalModifierHarga);

            listModifierIdEdit = totalModifierId;
            listModifierHargaEdit = totalModifierHarga;
            listModifierNameEdit = totalModifierName;
            return totalModifier;
        }

        function hitungPilihanEditItem(){
            let pilihanCheckboxesEdit = document.querySelectorAll('.form-pilihan-edit');

            let totalPilihan = 0;
            let totalPilihanId = [];
            let totalPilihanName = [];
            let totalPilihanHarga = [];

            pilihanCheckboxesEdit.forEach((pilihan) => {
                if(pilihan.checked){
                    const amount = parseFloat(pilihan.value);
                    const id = pilihan.dataset.id;
                    const name = pilihan.dataset.name;
                    totalPilihan += parseInt($('#quantity-edit').val()) * amount;

                    totalPilihanId.push(id);
                    totalPilihanHarga.push(amount);
                    totalPilihanName.push(name);
                }
            });

            listPilihanIdEdit = totalPilihanId;
            listPilihanHargaEdit = totalPilihanHarga;
            listPilihanNameEdit = totalPilihanName;
            return totalPilihan;

        }

        function hitungDiskonEditItem() {
            var diskonCheckboxesEditItem = document.querySelectorAll('.form-diskon-edit');
            let totalDiskon = 0;
            let totalDiskonId = [];
            let totalDiskonNama = [];
            let totalDiskonHarga = [];
            let totalDiskonValue = [];
            let totalDiskonType = [];

            diskonCheckboxesEditItem.forEach((checkbox) => {
                if (checkbox.checked) {
                    const amount = parseFloat(checkbox.value);
                    const type = checkbox.dataset.type;
                    const id = checkbox.dataset.id;
                    const name = checkbox.dataset.name;

                    if (type === "rupiah") {
                        totalDiskon += amount;
                    } else if (type === "percent") {
                        if (listModifierHargaEdit.length > 0) {
                            let hargaBarang = hargaAkhirEditItem
                            listModifierHargaEdit.forEach(function(itemModifier) {
                                hargaBarang += itemModifier;
                            });
                            totalDiskon += (hargaBarang * parseInt($('#quantity-edit').val()) * amount) / 100;
                        } else {
                            totalDiskon += (dataHarga * parseInt($('#quantity-edit').val()) * amount) / 100;
                        }
                    }

                    totalDiskonId.push(id);
                    totalDiskonValue.push(amount);
                    totalDiskonHarga.push(totalDiskon);
                    totalDiskonType.push(type);
                    totalDiskonNama.push(name);
                }
            });

            listDiskonAmountEdit = totalDiskonHarga;
            listDiskonIdEdit = totalDiskonId;
            listDiskonNameEdit = totalDiskonNama;
            listDiskonValueEdit = totalDiskonValue;
            listDiskonTypeEdit = totalDiskonType;
            return totalDiskon;
        }

        $(document).ready(function() {
            showLoader(false);
            generateListDiskon();
            attachTransactionClickEvent();

            if (dataPattyCash.length > 0) {
                let soldItem = 0;
                let name = dataPattyCash[0].user_started.name;
                $('#txt-open-patty-cash').text(name);
                $('#txt-name-end-current-shift').text(name);

                let namaOutlet = dataPattyCash[0].outlet_data.name;
                $('#txt-outlet').text(namaOutlet);
                $('#txt-outlet-end-current-shift').text(namaOutlet);

                let startingShift = dataPattyCash[0].open;
                $('#txt-starting-shift').text(startingShift);
                $('#txt-start-end-current-shift').text(startingShift);

                $('#txt-starting-cash-end-current-shift').text(formatRupiah(dataPattyCash[0].amount_awal.toString(),
                    "Rp. "));

                listExistingSoldItem.forEach(function(item){
                    soldItem += item.total_transaction;
                })


                listCategoryPayment.forEach(function(item) {
                    if (item.name == 'Cash') {
                        let sales = 0;
                        let expectedEndingCash = parseInt(dataPattyCash[0].amount_awal);
                        item.transactions.forEach(function(cashTransaction) {
                            sales += cashTransaction.total;
                            expectedEndingCash += parseInt(cashTransaction.total);
                        })

                        finalExpectedEndingCash = expectedEndingCash;

                        $('#txt-sales-end-current-shift').text(formatRupiah(sales.toString(), "Rp. "));
                        $('#txt-expected-ending-end-current-shift').text(formatRupiah(expectedEndingCash
                            .toString(), "Rp. "));

                        let html = `
                            <div class="row mt-3">
                                <div class="col-12">
                                    <h5>${item.name}</h5>
                                    <hr>

                                    <div class="row">
                                        <div class="col-6">
                                            Starting Cash In Drawer
                                        </div>
                                        <div class="col-6" >
                                            ${formatRupiah(dataPattyCash[0].amount_awal.toString(), "Rp. ")}
                                        </div>
                                    </div>
                                    <hr>

                                    <div class="row">
                                        <div class="col-6">
                                            Cash Sales
                                        </div>
                                        <div class="col-6" >
                                            ${formatRupiah(sales.toString(), "Rp. ")}
                                        </div>
                                    </div>
                                    <hr>

                                    <div class="row">
                                        <div class="col-6">
                                            Expected Ending Cash
                                        </div>
                                        <div class="col-6" >
                                            ${formatRupiah(expectedEndingCash.toString(), "Rp. ")}
                                        </div>
                                    </div>
                                    <hr>

                                </div>

                            </div>

                        `
                        $('#container-shift').append(html);
                    } else {
                        let totalPerCategory = 0;
                        let html = `
                            <div class="row mt-3">
                                <div class="col-12">
                                    <h5>${item.name}</h5>
                                    <hr>

                                ${item.payment.map(function(itemPayment) {
                                    let total = 0;
                                    itemPayment.transactions.forEach(function(transactionPaymentItem){
                                        total += transactionPaymentItem.total;
                                        totalPerCategory += transactionPaymentItem.total;
                                    })
                                    return `<div class="row" >
                                                <div class="col-6" >
                                                    ${itemPayment.name}
                                                </div>
                                                <div class="col-6" >
                                                    ${formatRupiah(total.toString(), "Rp. ")}
                                                </div>
                                            </div>
                                            <hr> `;}).join('')}

                                    <div class="row">
                                        <div class="col-6">
                                            Expected ${item.name} Payment
                                        </div>
                                        <div class="col-6" >
                                            ${formatRupiah(totalPerCategory.toString(), "Rp. ")}
                                        </div>
                                    </div>
                                    <hr>

                                </div>

                            </div>

                        `;


                        $('#container-shift').append(html);

                    }
                });


                $('#txt-sold-items').text(soldItem);

            }

            $('.nav-link').on('click', function(e) {
                e.preventDefault();
                var target = $(this).data('target');

                // Remove active class from all sections and add to the target section
                $('.content-section').removeClass('active');
                $(target).addClass('active');

                // Update active class on nav items
                $('.nav-link').removeClass('active');
                $(this).addClass('active');
            });

            var backBtn = document.getElementById('back-btn');
            // Handle click on list items to show specific views
            $('.list-category').on('click', function() {

                // backBtn.style.display = 'block !important;';
                backBtn.style.setProperty('display', 'flex', 'important');
                const targetView = $(this).data('target');
                const namaKategori = $(this).data('name');
                console.log(targetView);
                console.log(namaKategori);
                if (targetView == "Diskon") {
                    checkDiskonUsage();
                }

                $('#input-search-item').addClass('d-none');
                $('#search-item').val('');
                $('#content-section > .child-section').addClass('d-none'); // Hide all views
                $(`#${targetView}`).removeClass('d-none'); // Show selected view
                $('#text-judul').text(`${namaKategori}`)
            });



            $('.list-item').on('click', function(e) {
                const dataId = $(this).data('id');

                e.preventDefault();
                // Ambil URL dasar dari Blade tanpa parameter
                let baseUrl = `{{ route('kasir/findProduct', ':id') }}`; // Placeholder ':id'
                let url = baseUrl.replace(':id', dataId); // Ganti ':id' dengan nilai dataId

                handleAjax(url).excute();
            });

            $('.list-diskon').on('click', function(e) {
                let satuan = $(this).data('satuan');
                let amount = $(this).data('amount');
                let idDiskon = $(this).data('id');
                let type = $(this).data('type');
                let name = $(this).data('name')

                let isDisabled = $(this).attr('disabled') !== undefined;

                if (isDisabled) {
                    console.log('Elemen ini disabled');
                    return; // Jika disabled, hentikan eksekusi
                } else {
                    if (type == "fixed") {
                        if (satuan == "percent") {
                            listItem.forEach(function(dataItem, indexItem) {
                                let diskonExist = dataItem.diskon.find((diskon) => diskon.id ==
                                    idDiskon);
                                if (!diskonExist) {
                                    let hasilDiskon = dataItem.harga / amount;
                                    let tmpDataDiskon = {
                                        id: idDiskon,
                                        nama: name,
                                        result: hasilDiskon,
                                        satuan: satuan,
                                        tmpIdProduct: dataItem.tmpId,
                                        value: amount,
                                    }
                                    dataItem.diskon.push(tmpDataDiskon);
                                }
                            });
                        } else {
                            let tmpDataDiskonRupiah = {
                                id: idDiskon,
                                nama: name,
                                satuan: satuan,
                                value: amount,
                            }

                            listDiskonAllItem.push(tmpDataDiskonRupiah);
                        }

                        let diskonElement = $('#Diskon').find(`.list-diskon[data-id="${idDiskon}"]`);

                        // Tambahkan atribut disabled
                        diskonElement.attr('disabled', true);

                        // Tambahkan class text-muted pada span dengan id text-diskon-list
                        diskonElement.find('#text-diskon-list').addClass('text-muted');
                    } else {
                        let baseUrl = `{{ route('kasir/customDiskon', ':id') }}`;
                        let url = baseUrl.replace(':id', idDiskon); // Ganti ':id' dengan nilai dataId
                        handleAjax(url).excute();
                    }

                }

                syncItemCart();
            });

            // Handle back button to return to the library view
            $('.back-btn').on('click', function() {
                // backBtn.style.display = 'none !important;';
                backBtn.style.setProperty('display', 'none', 'important');
                $('#input-search-item').removeClass('d-none');
                $('#content-section > .child-section').addClass('d-none'); // Hide all views
                $('#library-view').removeClass('d-none'); // Show library view
                $('#text-judul').text("Library")
            });

            syncIconBoxes();

            // Handle delete button
            $('.btn-danger').on('click', function() {
                $(this).closest('.row').remove();
                updateHargaTotal();
                alert('Item deleted!');
                // Logic to update subtotal, total, etc.
            });

            // Handle charge button
            $('#bayar').on('click', function() {
                if (listItem.length > 0 || listItemPromo.length > 0) {
                    handleAjax("{{ route('kasir/choosePayment') }}").excute();
                } else {
                    iziToast['error']({
                        title: "Gagal",
                        message: "Product Belum Dipilih",
                        position: 'topRight'
                    });
                }
            });

            let screenValue = "Rp 0";

            // Update screen
            function updateScreen(value) {
                if (value === "") value = "Rp 0";
                $("#calculator-screen").text(formatRupiah(value, "Rp. "));
            }

            // Button click event
            $(".calculator-btn").on("click", function() {
                const value = $(this).data("value");

                if (value === "clear") {
                    screenValue = "Rp 0";
                } else if (value === "del") {
                    screenValue = screenValue.slice(0, -1);
                } else if (value === "add") {
                    console.log(screenValue);
                    tmpTampungCustomAmount = screenValue;
                    updateCustomAmount();

                } else {
                    if (screenValue === "Rp 0") screenValue = value.toString();
                    else screenValue += value.toString();
                }

                updateScreen(screenValue);
            });

            $('#pilih-pelanggan').on('click', function() {
                if(billId != 0 || billId != "0"){
                    iziToast['warning']({
                        title: "Gagal",
                        message: "Tidak bisa menambahkan customer pada bill tersimpan, buat bill baru",
                        position: 'topRight'
                    });
                }else{
                    handleAjax("{{ route('kasir/pilihCustomer') }}").excute();
                }
            });

            $('#tambah-pelanggan').on('click', function() {
                handleAjax("{{ route('kasir/tambahCustomer') }}").excute();
            });

            var backBtnSetting = document.getElementById('back-btn-setting');
            if (backBtnSetting) {
                backBtnSetting.addEventListener('click', function() {
                    let section = $(this).data('section');
                    $(this).removeAttr('data-section').removeData('section');
                    if (section == "end-current-shift") {
                        $('#shift-menu').removeClass('d-none');
                        $('#end-current-shift-section').addClass('d-none');
                    }else if(section == "detail-history-shift"){
                        $('#detail-shift-history').addClass('d-none');
                        $('#history-shift-menu').removeClass('d-none');
                    } else if(section == "history-sold-item"){
                        $('#detail-shift-history').removeClass('d-none');
                        $('#list-sold-item').addClass('d-none');
                        $('#container-sold-item').empty();
                    } else if(section == "list-sold-item"){
                        $('#shift-menu').removeClass('d-none');
                        $('#list-sold-item').addClass('d-none');
                        $('#container-sold-item').empty();
                    } else {
                        console.log('masuk else');
                        backBtnSetting.style.setProperty('display', 'none', 'important');
                        $('#setting-section > .child-section').addClass('d-none'); // Hide all views
                        $('#setting-view').removeClass('d-none'); // Show library view
                        $('#text-title-setting').text("Setting")
                    }

                });
            }
            // Handle click on list items to show specific views
            $('.list-setting').on('click', function() {
                const targetView = $(this).data('target');

                if (targetView == "logout") {
                    fetch("{{ route('logout') }}", {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': "{{ csrf_token() }}",
                                'Content-Type': 'application/json'
                            }
                        })
                        .then(response => {
                            if (response.ok) {
                                window.location.href = '/login'; // Redirect ke halaman login
                            } else {
                                alert('Logout gagal.');
                            }
                        })
                        .catch(error => console.error('Error:', error));
                } else if(targetView == "activity-menu"){
                    $('#activity-menu').removeClass('d-none');
                    $('#content-area').addClass('d-none');
                    $('#bottom-navbar').addClass('d-none');

                    $('#list-transaction-container').empty();
                    if(dataPattyCash.length){
                        const baseUrlListTransaction = `{{ route('kasir/getListTransactionToday', ':id') }}`; // Placeholder ':id'
                        const urlListTransaction = baseUrlListTransaction.replace(':id', dataPattyCash[0].id); // Ganti ':id' dengan nilai dataId

                        console.log(dataPattyCash[0].outlet_data.id)
                        $.ajax({
                            url: urlListTransaction,
                            method: "GET",
                            beforeSend: function() {
                                showLoader();
                            },
                            complete: function() {
                                showLoader(false);
                            },
                            success: (res) => {
                                listActivityTransaction = res.data;
                                console.log(res.data);
                                if(res.data.length){
                                    detailTransactionHandle(res.data[0]);
                                }

                                res.data.forEach(function(transaction, index){
                                    const truncateItemText = getTruncatedItemText(transaction.item_transaction);
                                    const paymentIcon = getPaymentIcon(transaction.nama_tipe_pembayaran);
                                    const htmlTransaction = createTransactionHTML(transaction, truncateItemText, paymentIcon, index);

                                    $('#list-transaction-container').append(htmlTransaction);
                                    attachTransactionClickEvent();
                                });
                            },
                            error: function(err) {
                                console.log(err);
                                reject(err); // Rejecting the promise on error
                            }
                        });
                    }

                } else if(targetView == "shift-menu") {
                    if (dataPattyCash.length < 1) {
                        handleAjax("{{ route('kasir/viewPattyCash') }}").excute();
                    } else {
                        backBtnSetting.style.setProperty('display', 'flex', 'important');
                        let titleSectionSetting = $(this).data('name-section');

                        $('#setting-section > .child-section').addClass('d-none'); // Hide all views
                        $(`#${targetView}`).removeClass('d-none'); // Show selected view
                        $('#text-title-setting').text(`${titleSectionSetting}`)
                        $('#btn-print-shift').attr('href', 'intent://shift-order-print?id=' + dataPattyCash[0].id);
                    }

                }else if(targetView == "history-shift-menu"){
                    // iziToast['warning']({
                    //     title: "Warning",
                    //     message: "Sementara belum bisa digunakan",
                    //     position: 'topRight'
                    // });
                    backBtnSetting.style.setProperty('display', 'flex', 'important');
                    let titleSectionSetting = $(this).data('name-section');

                    $('#setting-section > .child-section').addClass('d-none'); // Hide all views
                    $(`#${targetView}`).removeClass('d-none'); // Show selected view
                    $('#text-title-setting').text(`${titleSectionSetting}`)

                    loadHistoryShifts();
                }
            });

            $('.card.list-setting').on('touchstart', function() {
                $(this).addClass('hover-effect');
            });

            $('.card.list-setting').on('touchend', function() {
                $(this).removeClass('hover-effect');
            });

            $('#end-current-shift').on('click', function() {
                $('#end-current-shift-section').removeClass('d-none');
                $('#setting-section > .child-section').addClass('d-none'); // Hide all views
                $('#back-btn-setting').attr('data-section', 'end-current-shift');
            });

            var endingCashInput = document.getElementById('endingCash');

            if (endingCashInput) {
                endingCashInput.addEventListener("keyup", function(e) {
                    this.value = formatRupiah(this.value, "Rp. ");
                    if (this.value == '' || this.value == 'Rp. ') {
                        $('#container-difference').addClass('d-none');
                        $('#difference').text(formatRupiah("0", "Rp. "));
                    } else {
                        $('#container-difference').removeClass('d-none');

                        let hargaInput = this.value.match(/\d+/g).join('');
                        let different = hargaInput - finalExpectedEndingCash;
                        console.log(different)
                        if (different > 0) {
                            $('#difference').text(formatRupiah(different.toString(), "Rp. "));
                        } else {
                            $('#difference').text("-" + formatRupiah(different.toString(), "Rp. "));
                        }
                    }
                })
            }

            $('#btnEndCurrentShift').on('click', function() {
                let endingCash = $('#endingCash').val();
                if (endingCash != '') {
                    let dataForm = new FormData();
                    dataForm.append('endingCash', endingCash);
                    // Log dataForm untuk memastikan data yang dikirim
                    for (var pair of dataForm.entries()) {
                        console.log(pair[0] + ', ' + pair[1]);
                    }
                    $.ajax({
                        url: '{{ route('kasir/closePattyCash') }}',
                        method: "POST",
                        data: dataForm,
                        contentType: false,
                        processData: false,
                        beforeSend: function() {
                            showLoader();
                        },
                        success: (res) => {
                            showToast(res.status, res.message);
                            location.reload();

                        },
                        complete: function() {
                            showLoader(false);
                        },
                        error: function(err) {
                            const errors = err.responseJSON?.errors

                            showToast('error', err.responseJSON?.message)
                        }
                    });

                } else {
                    showToast('error', "Masukan Ending Cash")
                }

            });

            $("#empty-cart").on('click', function() {
                if(billId != 0 && billId != "0"){
                    iziToast['error']({
                        title: "Gagal",
                        message: "Bill tersimpan tidak bisa dihapus",
                        position: 'topRight'
                    });
                }else{
                    listItem = [];
                    listItemPromo = [];
                    listRewardItem = [];
                    listDiskonAllItem = [];

                    syncItemCart();
                }
            });

            $('#simpan-bill').on('click', function() {
                if (listItem.length > 0 || listItemPromo.length > 0) {
                    if (billId != 0 || billId != "0") {
                        var itemBaru = 0;
                        openBillForm = new FormData();
                        openBillForm.append('name', $('#name-open-bill').val());
                        openBillForm.append('outlet_id', dataPattyCash[0].outlet_data.id);

                        listItem.forEach(function(item, index) {
                            if (!item.openBillId) {
                                itemBaru++;
                                openBillForm.append('catatan[]', item.catatan);
                                openBillForm.append('diskon[]', JSON.stringify(item.diskon));
                                openBillForm.append('harga[]', item.harga);
                                openBillForm.append('idProduct[]', item.idProduct);
                                openBillForm.append('idVariant[]', item.idVariant);
                                openBillForm.append('modifier[]', JSON.stringify(item.modifier));
                                openBillForm.append('namaProduct[]', item.namaProduct);
                                openBillForm.append('namaVariant[]', item.namaVariant);
                                openBillForm.append('pilihan[]', JSON.stringify(item.pilihan));
                                openBillForm.append('promo[]', JSON.stringify(item.promo));
                                openBillForm.append('quantity[]', item.quantity);
                                openBillForm.append('resultTotal[]', item.resultTotal);
                                openBillForm.append('salesType[]', item.salesType);
                                openBillForm.append('tmpId[]', item.tmpId);
                            }
                        });

                        openBillForm.append('bill_id', billId);

                        if (itemBaru > 0) {
                            $.ajax({
                                url: "{{ route('kasir/updateBill') }}",
                                method: "POST",
                                data: openBillForm,
                                contentType: false,
                                processData: false,
                                beforeSend: function() {
                                    showLoader();
                                },
                                success: (res) => {
                                    listItem = [];
                                    listItemPromo = [];
                                    listRewardItem = [];

                                    syncItemCart();
                                    if (window.Android) {
                                        // Panggil metode JavaScript Interface dengan ID transaksi
                                        window.Android.handlePrintOpenBill(res.data.id);
                                    }
                                    iziToast['success']({
                                        title: "Success",
                                        message: "Berhasil Memperbarui Bill",
                                        position: 'topRight'
                                    });

                                    const modal = $('#itemModal');
                                    modal.modal('hide');

                                    billId = 0;
                                },
                                complete: function() {
                                    showLoader(false);
                                },
                                error: function(err) {
                                    const errors = err.responseJSON?.errors

                                    showToast('error', err.responseJSON?.message)
                                }
                            })
                        } else {
                            iziToast['warning']({
                                title: "Oopss",
                                message: "Tidak ada item baru",
                                position: 'topRight'
                            });
                        }
                    } else {
                        handleAjax("{{ route('kasir/viewOpenBill') }}").excute();
                    }
                } else {
                    iziToast['error']({
                        title: "Gagal",
                        message: "Product Belum Dipilih",
                        position: 'topRight'
                    });
                }

            });

            $('#bill-list').on('click', function() {
                handleAjax('{{ route('kasir/billList') }}').excute();
            });

            $('#search-item').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                console.log(value);
                if (value.length > 0) {
                    $('#library-view').addClass('d-none');
                    $('.list-group').addClass('d-none');
                    $('#all-item').removeClass('d-none');
                } else {
                    $('#library-view').removeClass('d-none');
                    $('#all-item').addClass('d-none');
                    $('.list-group').removeClass('d-none');
                }

                $('.list-all-item').each(function() {
                    var nama = $(this).data('nama').toLowerCase();
                    if (nama.indexOf(value) > -1) {
                        console.log(nama);
                        // $(this).show();
                        $(this).removeClass('d-none');
                    } else {
                        // $(this).hide();
                        $(this).addClass('d-none');
                    }
                });
            });

            $('#clear-search').on('click', function() {
                $('#search-item').val('')
                $('#library-view').removeClass('d-none');
                $('#all-item').addClass('d-none');
                $('.list-group').removeClass('d-none');
                $('.list-all-item').each(function() {
                    $(this).addClass('d-none');
                });
            });

            $('#btn-back-activity').click(function(){
                $('#activity-menu').addClass('d-none');
                $('#content-area').removeClass('d-none');
                $('#bottom-navbar').removeClass('d-none');
            });

            // Event listener untuk input waktu
            $('#search-aktivitas').off().on('input', function() {
                var filterTime = $(this).val(); // Mendapatkan nilai waktu dari input
                filterTransactions(filterTime); // Memanggil fungsi filter dengan nilai waktu
            });

            // Event listener untuk tombol clear
            $('#clear-search-activity').off().on('click', function() {
                $('#search-aktivitas').val(''); // Mengosongkan nilai input
                filterTransactions(''); // Memanggil fungsi filter dengan nilai kosong
            });

            $('#btn-resend-receipt').off().on('click', function(){
                let baseUrlResendReceipt = `{{ route('kasir/viewResendReceipt', ':id') }}`; // Placeholder ':id'
                let dataId = $(this).attr('data-id');
                let urlResendReceipt = baseUrlResendReceipt.replace(':id', dataId); // Ganti ':id' dengan nilai dataId

                handleAjax(urlResendReceipt).excute();
            });

            $('#split-bill').off().on('click', function(){
                if(listItem.length > 0 || listItemPromo.length > 0){
                    if(dataPattyCash.length > 0){
                        handleAjax('{{route("kasir/viewSplitBill")}}').excute();
                    }else{
                        iziToast['error']({
                            title: "Gagal",
                            message: "Open Shift Terlebih Dahulu",
                            position: 'topRight'
                        });
                    }
                } else{
                    iziToast['error']({
                        title: "Gagal",
                        message: "Product Belum Dipilih",
                        position: 'topRight'
                    });
                }
            });

            $('#btnBatalEditItem').off().on('click', function(){
                hargaAkhirEditItem = 0;
                const modal = $('#modalEditPesanan');
                modal.modal('hide');
            })

        });
    </script>
</body>

</html>
