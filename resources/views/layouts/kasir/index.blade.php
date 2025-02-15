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
            height: 55px
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
                                            <button id="back-btn-setting" class="btn btn-link"
                                                style="display: none !important;">&larr; Back</button>
                                            <h4 id="text-title-setting">Setting</h4>
                                        </div>
                                    </div>

                                    <div id="setting-view" class="card mt-2 child-section">
                                        <div class="card-body">
                                            <div class="card list-setting bg-primary" id="shift"
                                                data-target="shift-menu" data-name-section="Shift">
                                                <div class="card-body">
                                                    <h5 class="text-white">Shift</h5>
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
                                                        <button id="end-current-shift"
                                                            class="btn btn-outline-primary w-100 btn-lg mb-4">End
                                                            Current Shift</button>

                                                        <div class="container" id="container-shift">
                                                            <div class="row">
                                                                <div class="col-12">
                                                                    <h5>Shift Details</h5>
                                                                    <hr>
                                                                    <div class="row">
                                                                        <div class="col-6">
                                                                            Name
                                                                        </div>
                                                                        <div class="col-6" id="txt-open-patty-cash">
                                                                            Ardian
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
                                                                        <div class="col-6" id="txt-sold-items">
                                                                            27
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
                                                <button id="back-btn" class="btn btn-link my-3 back-btn"
                                                    style="display: none !important;">&larr; Back</button>
                                                <div class="col text-center">
                                                    <h5 class="my-3" id="text-judul">Library</h5>
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
                                    <button class="btn btn-outline-primary w-50" style="height: 60px;">Cetak
                                        Bill</button>
                                </div>

                                <!-- Charge Button -->
                                <div class="row">
                                    <button class="btn btn-primary btn-lg btn-block" id="bayar"
                                        style="height: 60px;">Bayar</button>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Bottom Navigation -->
        <ul class="nav nav-pills nav-fill fixed-bottom bg-light">
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
    <div class="modal fade" id="promoModal" tabindex="-1" aria-labelledby="itemModalLabel">
    </div>

    <!-- Modal Success -->
    <div class="modal fade" id="modals" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-body">
                    <center>
                        <img src="https://i.gifer.com/7efs.gif" alt="Transaction Successfully" class="img-fluid">
                        <h1>Transaksi Sukses!</h1>
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

            syncItemCart()
        }

        function updateCustomAmount() {
            let tmpId = generateRandomID();
            let html = `
            <div class="row mb-0 mt-2">
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
                tmpSubTotal.push(item.resultTotal);

                item.modifier.forEach(function(itemModifier, indexModifier) {
                    tmpSubTotal.push(itemModifier.harga);
                });

                item.diskon.forEach(function(itemDiskon) {
                    tmpSubTotal.push(-itemDiskon.result);
                });
            });

            listItemPromo.forEach(function(item, index) {
                tmpSubTotal.push(item.resultTotal);

                item.modifier.forEach(function(itemModifier, indexModifier) {
                    tmpSubTotal.push(itemModifier.harga);
                });
            });

            listDiskonAllItem.forEach(function(item, index){
                tmpSubTotal.push(-item.value);
            });

            console.log(tmpSubTotal);

            var subTotal = tmpSubTotal.reduce(function(acc, curr) {
                return acc + curr;
            }, 0);

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
            console.log("masok lagi")
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



        $(document).ready(function() {
            showLoader(false)
            generateListDiskon()

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


                listCategoryPayment.forEach(function(item) {
                    if (item.name == 'Cash') {
                        let sales = 0;
                        let expectedEndingCash = parseInt(dataPattyCash[0].amount_awal);
                        soldItem += item.transactions.length;
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
                                    soldItem += itemPayment.transactions.length;
                                    itemPayment.transactions.forEach(function(transactionPaymentItem){
                                        total += transactionPaymentItem.total;
                                        totalPerCategory += transactionPaymentItem.total;
                                    })
                                    return `<div class = "row" >
                                                <div class = "col-6" >
                                                    ${itemPayment.name}
                                                </div>
                                                <div class = "col-6" >
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
                        $('#txt-sold-items').text(soldItem);
                    }


                });
                console.log(listCategoryPayment);
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

            const iconBoxes = document.querySelectorAll('.icon-box');

            iconBoxes.forEach((box) => {
                const text = box.getAttribute('data-text');

                if (text) {
                    // Pisahkan kata dan ambil maksimal 2 kata pertama
                    const words = text.split(' ');
                    const initials = words.slice(0, 2).map(word => word[0]).join('');
                    box.textContent = initials; // Isi kotak dengan inisial
                }
            });

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
                handleAjax("{{ route('kasir/pilihCustomer') }}").excute();
            });

            $('#tambah-pelanggan').on('click', function() {
                handleAjax("{{ route('kasir/tambahCustomer') }}").excute();
            });

            var backBtnSetting = document.getElementById('back-btn-setting');
            if (backBtnSetting) {
                backBtnSetting.addEventListener('click', function() {
                    let section = $(this).data('section');

                    if (section == "end-current-shift") {
                        $(this).removeAttr('data-section').removeData('section');
                        $('#shift-menu').removeClass('d-none');
                        $('#end-current-shift-section').addClass('d-none');
                    } else {
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
                } else {
                    if (dataPattyCash.length < 1) {
                        handleAjax("{{ route('kasir/viewPattyCash') }}").excute();
                    } else {
                        backBtnSetting.style.setProperty('display', 'flex', 'important');
                        let titleSectionSetting = $(this).data('name-section');

                        $('#setting-section > .child-section').addClass('d-none'); // Hide all views
                        $(`#${targetView}`).removeClass('d-none'); // Show selected view
                        $('#text-title-setting').text(`${titleSectionSetting}`)
                    }

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
                listItem = [];
                listItemPromo = [];
                listRewardItem = [];

                syncItemCart();
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
                            console.log(billId);
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
        });
    </script>
</body>

</html>
