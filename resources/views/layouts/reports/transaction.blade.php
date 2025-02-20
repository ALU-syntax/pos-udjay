@extends('layouts.app')
@section('content')
    <style>
        .animated {
            transition: width 0.5s ease, flex 0.5s ease;
        }

        #container-order-details {
            display: none;
            opacity: 0;
            transition: opacity 0.5s ease;
        }

        /* CSS untuk mengubah cursor menjadi select saat hover pada baris tabel */
        #transactions-table tbody tr:hover {
            cursor: pointer;
            /* Mengubah cursor menjadi pointer */
            background-color: #f0f0f0;
            /* Opsional: menambahkan efek hover */
        }
    </style>
    <div class="main-content">
        <div class="card text-center">
            <h5 class="card-header">Transaction</h5>
        </div>

        <div class="card mt-4">
            <div class="card-header ">
                <div class="row">
                    <div class="col-12">
                        <ul class="nav nav-tabs">
                            <li class="nav-item">
                                <a class="nav-link active" aria-current="page" href="#">Success Order</a>
                            </li>
                            {{-- <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button"
                                    aria-expanded="false">Dropdown</a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#">Action</a></li>
                                    <li><a class="dropdown-item" href="#">Another action</a></li>
                                    <li><a class="dropdown-item" href="#">Something else here</a></li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item" href="#">Separated link</a></li>
                                </ul>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#">Link</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link disabled" href="#" tabindex="-1" aria-disabled="true">Disabled</a>
                            </li> --}}
                        </ul>
                    </div>
                </div>

                <div class="row mt-2 d-flex">
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

                    {{-- <div class="col-3 offset-5 text-right justify-content-end d-flex">
                        @can('create library/tax')
                            <a href="{{ route('library/tax/create') }}" type="button"
                                class="btn btn-md btn-primary btn-round ms-auto action"><i class="fa fa-plus"></i> Tambah
                                Tax</a>
                        @endcan
                    </div> --}}

                </div>

                <div class="row row-card-no-pd mt-3">
                    <div class="col-sm-6 col-md-4">
                        <div class="card card-stats card-round">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-5">
                                        <div class="icon-big text-center">
                                            <i class="icon-pie-chart text-warning"></i>
                                        </div>
                                    </div>
                                    <div class="col-7 col-stats">
                                        <div class="numbers">
                                            <p class="card-category">Transactions</p>
                                            <h4 class="card-title" id="transaction">{{ count($data) }}</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <div class="card card-stats card-round">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-5">
                                        <div class="icon-big text-center">
                                            <i class="icon-wallet text-primary"></i>
                                        </div>
                                    </div>
                                    <div class="col-7 col-stats">
                                        <div class="numbers">
                                            <p class="card-category">Total Collected</p>
                                            <h4 class="card-title" id="total-collected"></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-4">
                        <div class="card card-stats card-round">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-5">
                                        <div class="icon-big text-center">
                                            <i class="icon-check text-success"></i>
                                        </div>
                                    </div>
                                    <div class="col-7 col-stats">
                                        <div class="numbers">
                                            <p class="card-category">Net Sales</p>
                                            <h4 class="card-title" id="net-sales">23</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">

            </div>
        </div>

        <div class="row">
            <div class="col-12 animated" id="tableContainer">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="table-responsive">
                                {!! $dataTable->table() !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6" id="container-order-details">
                <div class="card">
                    <div class="card-body">
                        <div class="container">
                            <h5>Order Details</h5>
                            <hr>
                            <div class="row mb-2 d-flex">
                                <div class="col-6">Status</div>
                                <div class="col-6 text-right d-flex"><span class="badge badge-success">Complete</span></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-6">Order Id</div>
                                <div class="col-6 text-right d-flex"></div>
                            </div>
                            {{-- <div class="row mb-2">
                                <div class="col-6">Receipt Number</div>
                                <div class="col-6 text-right d-flex"></div>
                            </div> --}}
                            <div class="row mb-2 d-flex">
                                <div class="col-6">Complete Time</div>
                                <div class="col-6 text-right d-flex" ><span id="complete-time"></span></div>
                            </div>
                            <div class="row mb-2 d-flex">
                                <div class="col-6">Table</div>
                                <div class="col-6 text-right d-flex"></div>
                            </div>
                            <div class="row mb-2 d-flex">
                                <div class="col-6">Served by</div>
                                <div class="col-6 text-right d-flex" id="served-by"></div>
                            </div>
                            <div class="row mb-2 d-flex">
                                <div class="col-6">Pax</div>
                                <div class="col-6 text-right d-flex"></div>
                            </div>
                            <div class="row mb-2 d-flex">
                                <div class="col-6">Duration</div>
                                <div class="col-6 text-right d-flex"></div>
                            </div>
                            <div class="row mb-2 d-flex">
                                <div class="col-6">Collected By</div>
                                <div class="col-6 text-right d-flex" id="collected-by"></div>
                            </div>
                            <div class="row mb-2 d-flex">
                                <div class="col-6">Total Amount</div>
                                <div class="col-6 text-right d-flex" id="total-amount"></div>
                            </div>
                            <div class="row mb-2 d-flex">
                                <div class="col-6">Payment Method</div>
                                <div class="col-6 text-right d-flex" id="payment-method"></div>
                            </div>
                            <div class="row mb-2 d-flex">
                                <div class="col-6">
                                    <h4>ORDERED ITEMS</h4>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-12">
                                    <table class="table" id="table-list-item">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>Item</th>
                                                <th>Quantity</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="row d-flex">
                            <div class="col-2">
                                <button class="btn btn-primary">Close</button>
                            </div>
                            <div class="col-4 d-flex text-right alignt-content-end justify-content-end ">
                                <button class="btn btn-primary text-right">Show Receipt</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    @push('js')
        {!! $dataTable->scripts() !!}
        <script>
            var dataTransaksi = @json($data);
            let totalTransaksi = 0;
            dataTransaksi.forEach(function(item) {
                totalTransaksi += item.total;
            });


            function getNewData() {
                let outletTerpilih = $('#filter-outlet').val();
                let date = $('#date_range_transaction').val();

                $.ajax({
                    url: `{{ route('report/transaction/getTransactionData') }}`, // URL endpoint Laravel
                    type: 'GET',
                    data: {
                        idOutlet: outletTerpilih, // Kirim data array ke server
                        date: date
                    },
                    success: function(response) {
                        console.log(response)
                        let totalTransaksi = 0;
                        response.data.forEach(function(item) {
                            totalTransaksi += item.total;
                        });

                        $('#transaction').text(response.data.length);
                        $("#total-collected").text(formatRupiah(totalTransaksi.toString(), "Rp. "));
                        $("#net-sales").text(formatRupiah(totalTransaksi.toString(), "Rp. "));
                    },
                    error: function(xhr, status, error) {
                        console.error("Terjadi kesalahan:", error);
                    }
                });
            }

            $('#transactions-table tbody tr').on('click', function() {
                var tableContainer = $('#tableContainer');
                console.log(tableContainer.hasClass('col-12'))
                if (tableContainer.hasClass('col-12')) {
                    tableContainer.removeClass('col-12').addClass('col-6');
                } else {
                    tableContainer.removeClass('col-6').addClass('col-12');
                }
            });


            function handleClickRowTransaction(idTransaction) {
                const tableContainer = $('#tableContainer');
                const detailCard = $('#container-order-details');

                if (tableContainer.hasClass('col-12')) {
                    $.ajax({
                        url: `{{ route('report/transaction/getTransactionDataDetail') }}`,
                        method: 'GET',
                        data: {
                            idTransaction: idTransaction,
                        },
                        beforeSend: function() {
                            showLoader();
                        },
                        complete: function() {
                            showLoader(false);
                        },
                        success: function(res) {
                            console.log(res);

                            tableContainer.removeClass('col-12').addClass('col-6');
                            tableContainer.animate({
                                width: '50%'
                            }, 500, function() {
                                $('#complete-time').text(res.data.create_formated);
                                $('#collected-by').text(res.data.user.name);
                                $('#total-amount').text(formatRupiah(res.data.total.toString(), "Rp. "));
                                $('#payment-method').text(res.data.nama_tipe_pembayaran)

                                // Mengosongkan tabel sebelum mengisi data baru
                                const tbody = $('table tbody');
                                tbody.empty(); // Menghapus semua baris yang ada

                                // Mengisi tabel dengan data item_transaction
                                var tmpDiskonBefore,
                                tmpModifierBefore,
                                tmpCatatanBefore,
                                tmpProductIdBefore,
                                tmpVariantIdBefore,
                                tmpPromoId,
                                qtyProduct;

                                res.data.item_transaction.forEach(item => {
                                    let namaProduct = item.variant.name == item.product.name ? item.product.name : `${item.product.name} - ${item.variant.name}`

                                    tbody.append(`
                                            <tr>
                                                <td>${namaProduct}</td>
                                                <td>${item.total_count}</td>
                                            </tr>
                                        `);
                                    });

                                // Setelah animasi selesai, tampilkan detail card
                                detailCard.css('display', 'block').animate({
                                    opacity: 1
                                }, 500);
                            });

                        },
                        error: function(xhr) {
                            console.error(xhr);
                        }
                    });

                } else {
                    // Jika ingin mengembalikan ke ukuran asal
                    detailCard.hide(1000, function(a) {
                        const cardDetail = $(this);
                        cardDetail.css('display', 'none').animate({
                            opacity: 0
                        });

                        tableContainer.removeClass('col-6').addClass('col-12');
                        tableContainer.animate({
                            width: '100%'
                        }, 500);
                    });
                }
            }


            $(document).ready(function() {
                $("#total-collected").text(formatRupiah(totalTransaksi.toString(), "Rp. "));
                $("#net-sales").text(formatRupiah(totalTransaksi.toString(), "Rp. "));

                $("#filter-outlet").select2();
                var success = "{{ session('success') }}";
                const datatable = 'transactions-table';

                $(".select2InsideModal").select2({
                    dropdownParent: $("#modal_action")
                });

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
                    var table = $('#' + datatable).DataTable();
                    table.ajax.url("{{ route('report/transaction') }}?outlet=" + $('#filter-outlet').val() +
                            "&date=" + $('#date_range_transaction').val())
                        .load();

                    getNewData();
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

                    var table = $('#' + datatable).DataTable();
                    table.ajax.url("{{ route('report/transaction') }}?outlet=" + $('#filter-outlet').val() +
                            "&date=" + $('#date_range_transaction').val())
                        .load();

                    getNewData();
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

                    var table = $('#' + datatable).DataTable();
                    table.ajax.url("{{ route('report/transaction') }}?outlet=" + $('#filter-outlet').val() +
                            "&date=" + $('#date_range_transaction').val())
                        .load();

                    getNewData();
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
                    var table = $('#' + datatable).DataTable();

                    console.log(picker.startDate.format('YYYY-MM-DD'));
                    console.log(picker.endDate.format('YYYY-MM-DD'));
                    table.ajax.url("{{ route('report/transaction') }}?outlet=" + $('#filter-outlet').val() +
                            "&date=" + picker.startDate.format('YYYY/MM/DD') + ' - ' + picker.endDate.format(
                                'YYYY/MM/DD'))
                        .load();

                    getNewData();
                });

                handleAction(datatable);
                handleDelete(datatable);

                $('#filter-outlet').on('change', function() {
                    console.log($('#filter-outlet').val());
                    console.log($('#date_range_transaction').val());
                    var table = $('#' + datatable).DataTable();

                    // Refresh tabel
                    table.ajax.url("{{ route('report/transaction') }}?outlet=" + $('#filter-outlet').val() +
                            "&date=" + $('#date_range_transaction').val())
                        .load();

                    getNewData()
                });
            });
        </script>
    @endpush
@endsection
