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
        #openbill-table tbody tr:hover {
            cursor: pointer;
            /* Mengubah cursor menjadi pointer */
            background-color: #f0f0f0;
            /* Opsional: menambahkan efek hover */
        }

        #openbilldeleted-table tbody tr:hover {
            cursor: pointer;
            /* Mengubah cursor menjadi pointer */
            background-color: #f0f0f0;
            /* Opsional: menambahkan efek hover */
        }
    </style>
    <div class="main-content">
        <div class="card text-center">
            <h5 class="card-header">Open Bill</h5>
        </div>

        <div class="card mt-4">
            <div class="card-header ">
                <div class="row mt-2 d-flex">
                    <div class="col-4 align-self-center d-flex">
                        <select id="filter-outlet" class="form-control select2">
                            <option value="all" selected>-- Semua Outlet --</option>
                            @foreach ($outlets as $outlet)
                                <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <select id="status-bill" class="w-100" data-style="btn-default">
                            <option value="" selected disabled>Pilih Status</option>
                            <option value="1">Sudah Dibayar</option>
                            <option value="0">Belum Dibayar</option>
                        </select>
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
                        <ul class="nav nav-tabs nav-line nav-color-secondary" id="line-tab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a class="nav-link active" id="open-bill-tab" data-bs-toggle="pill" href="#open-bill"
                                    role="tab" aria-controls="pills-home" aria-selected="true">List Bill</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" id="open-bill-deleted" data-bs-toggle="pill" href="#openbill-deleted"
                                    role="tab" aria-controls="pills-profile" aria-selected="false" tabindex="-1">Open
                                    Bill Deleted</a>
                            </li>
                        </ul>
                        <div class="tab-content mt-3 mb-3" id="line-tabContent">
                            <div class="tab-pane fade show active" id="open-bill" role="tabpanel"
                                aria-labelledby="open-bill-tab">
                                <div class="row">
                                    <div class="table-responsive ">
                                        {!! $dataTable->table() !!}
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="openbill-deleted" role="tabpanel"
                                aria-labelledby="open-bill-deleted">
                                <div class="row">
                                    <div class="table-responsive">
                                        {!! $openBillDeletedDataTable->table(['class' => 'table table-bordered table-striped w-100'], true) !!}
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-6" id="container-order-details">
                <div class="card">
                    <div class="card-body">
                        <div class="container">
                            <div class="row">
                                <div class="col-6">
                                    <h5>Order Details</h5>
                                </div>

                                @can('delete report/openbill')
                                    <div class="col-6 d-flex align-self-end justify-content-end">
                                        {{-- <button class="btn btn-danger">Hapus Transaksi</button> --}}
                                        <a id="btn-delete-openbill" class="btn btn-danger" href="">Hapus
                                            Open Bill</a>
                                    </div>
                                @endcan
                            </div>
                            <hr>
                            <div class="row mb-2 d-flex">
                                <div class="col-6">Nama Bill</div>
                                <div class="col-6 justify-content-end d-flex" id="nama-open-bill"></div>
                            </div>
                            <div class="row mb-2 d-flex">
                                <div class="col-6">Status</div>
                                <div class="col-6 text-right d-flex" id="status-open-bill"></div>
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
                                <div class="col-6">Tanggal Pembuatan</div>
                                <div class="col-6 d-flex justify-content-end"><span id="created-time"></span></div>
                            </div>
                            <div class="row mb-2 d-flex">
                                <div class="col-6">Table</div>
                                <div class="col-6 text-right d-flex"></div>
                            </div>
                            <div class="row mb-2 d-flex">
                                <div class="col-6">Served by</div>
                                <div class="col-6 text-right d-flex" id="served-by"></div>
                            </div>
                            {{-- <div class="row mb-2 d-flex">
                                <div class="col-6">Pax</div>
                                <div class="col-6 text-right d-flex"></div>
                            </div> --}}
                            <div class="row mb-2 d-flex">
                                <div class="col-6">Duration</div>
                                <div class="col-6 text-right d-flex"></div>
                            </div>
                            <div class="row mb-2 d-flex">
                                <div class="col-6">Collected By</div>
                                <div class="col-6 d-flex justify-content-end" id="collected-by"></div>
                            </div>
                            <div class="row mb-2 d-flex">
                                <div class="col-6">Amount Item</div>
                                <div class="col-6 d-flex justify-content-end" id="total-amount"></div>
                            </div>
                            <div class="row mb-2 d-flex">
                                <div class="col-6">Tax</div>
                                <div class="col-6 d-flex justify-content-end" id="tax"></div>
                            </div>
                            <div class="row mb-2 d-flex">
                                <div class="col-6">Total</div>
                                <div class="col-6 d-flex justify-content-end" id="total"></div>
                            </div>
                            <div class="row mb-2 d-flex">
                                <div class="col-6">Payment Method</div>
                                <div class="col-6 d-flex justify-content-end" id="payment-method"></div>
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
                                                <th>Ditambahkan Pada</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td></td>
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
                                <button class="btn btn-primary" id="btn-close-detail">Close</button>
                            </div>
                            <div class="col-10 d-flex justify-content-end">
                                <button class="btn btn-outline-primary ms-2 d-none" id="btn-restore-openbill">Restore
                                    Openbill</button>
                            </div>
                            {{-- <div class="col-10 d-flex justify-content-end">
                                <a href="{{ route('report/transaction/modalResendReceipt', 1) }}" class="btn btn-outline-primary action"
                                    id="btn-resend-receipt">Resend Receipt</a>
                                <a href="{{ route('report/transaction/showReceipt', 1) }}" class="btn btn-outline-primary ms-2"
                                id="btn-show-receipt">Show Receipt</a>
                            </div> --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    @push('js')
        {!! $dataTable->scripts() !!}
        {!! $openBillDeletedDataTable->scripts() !!}
        <script>
            var dataTransaksi = @json($data);
            let totalTransaksi = 0;
            dataTransaksi.forEach(function(item) {
                totalTransaksi += item.total;
            });
            var stateLastId = 0;
            const tableContainer = $('#tableContainer');
            const detailCard = $('#container-order-details');

            const idOpenBillDatatable = 'openbill-table';
            const idOpenBillDeletedDatatable = 'openbilldeleted-table';

            const openBillTable = $(`#${idOpenBillDatatable}`);
            const openBillDeletedDatatable = $(`#${idOpenBillDeletedDatatable}`);

            function getNewOpenBillData() {
                let outletTerpilih = $('#filter-outlet').val();

                $.ajax({
                    url: `{{ route('report/openbill/getOpenBillData') }}`, // URL endpoint Laravel
                    type: 'GET',
                    data: {
                        idOutlet: outletTerpilih, // Kirim data array ke server
                    },
                    success: function(response) {

                    },
                    error: function(xhr, status, error) {
                        console.error("Terjadi kesalahan:", error);
                    }
                });
            }

            function fetchDetailOpenBill(idOpenBill, idOutlet) {
                $.ajax({
                    url: `{{ route('report/openbill/getOpenBillDataDetail') }}`,
                    method: 'GET',
                    data: {
                        idOpenBill: idOpenBill,
                        idOutlet: idOutlet,
                    },
                    beforeSend: function() {
                        showLoader();
                    },
                    complete: function() {
                        showLoader(false);
                    },
                    success: function(res) {
                        console.log(res)
                        let total = 0;
                        let pajak = res.pajak.value;
                        let totalNominalPajak = 0;
                        let totalTerbayar = 0;

                        if (res.data.delete_permanen) {
                            $('#btn-delete-openbill').addClass('d-none')
                            $('#btn-restore-openbill').removeClass('d-none');
                        } else {
                            $('#btn-restore-openbill').addClass('d-none');
                            $('#btn-delete-openbill').removeClass('d-none')
                        }

                        res.data.transactions.forEach(function(transaction){
                            totalTerbayar += parseInt(transaction.total);
                        });

                        res.data.item.forEach(function(item) {
                            let harga = item.harga;
                            let itemTerbayar = item.qty_terbayar ? item.qty_terbayar : 0;
                            let qty = parseInt(item.quantity) + itemTerbayar;

                            let hargaAkhir = harga * qty;

                            let pajakItem = hargaAkhir * pajak / 100;

                            totalNominalPajak += pajakItem;

                            total += hargaAkhir;
                        });

                        let totalHarga = total + totalNominalPajak;
                        let status = res.data.deleted_at ?
                            '<span class="badge badge-success">Sudah dibayar</span>' :
                            '<span class="badge badge-danger">Belum dibayar</span>';
                        $('#nama-open-bill').text(res.data.name);
                        $('#created-time').text(res.data.create_formated);
                        $('#collected-by').text(res.data.user.name);
                        $('#total-amount').text(formatRupiah(total.toString(), "Rp. "));
                        $('#payment-method').text(res.data.nama_tipe_pembayaran);
                        $('#status-open-bill').html(status);
                        $('#tax').text(formatRupiah(totalNominalPajak.toString(), "Rp. "));
                        $('#total').text(`${formatRupiah(totalHarga.toString(), "Rp. ")} (${formatRupiah(totalTerbayar.toString(), "Rp. ")})`);
                        // $('#total').html(`<span>${formatRupiah(totalHarga.toString(), "Rp. ")}</span> (<span class="badge badge-success">${formatRupiah(totalTerbayar.toString(), "Rp. ")}</span>)`);

                        var urlDestroyOpenBill = "{{ route('report/openbill/deleteOpenBill', ':id') }}".replace(
                            ':id', res.data.id);
                        $('#btn-delete-openbill').attr('href', urlDestroyOpenBill);

                        // Mengosongkan tabel sebelum mengisi data baru
                        const tbody = $('#table-list-item tbody');
                        tbody.empty(); // Menghapus semua baris yang ada

                        // Mengisi tabel dengan data item_transaction
                        res.data.item.forEach(item => {
                            let qtyTerbayar = item.qty_terbayar ? item.qty_terbayar : 0;
                            let qtyPesanan = parseInt(item.quantity) + qtyTerbayar;

                            let qtyText = qtyTerbayar > 0 && item.deleted_at == null ?
                                `${qtyPesanan} <span class="badge rounded-pill badge-success">Paid ${qtyTerbayar}</span>` : (item.deleted_at == null ? qtyPesanan : `${qtyPesanan} <span class="badge rounded-pill badge-success">Paid ${qtyPesanan}</span>`);
                            if (item.product) {
                                let namaProduct = item.variant.name == item.product.name ? item
                                    .product.name :
                                    `${item.product.name} - ${item.variant.name}`

                                tbody.append(`
                                                <tr>
                                                    <td>${namaProduct}</td>
                                                    <td>${qtyText}</td>
                                                    <td>${item.create_formated}</td>
                                                </tr>
                                            `);
                            } else {
                                tbody.append(`
                                                <tr>
                                                    <td>custom</td>
                                                    <td>${qtyText}</td>
                                                    <td>${item.create_formated}</td>
                                                </tr>
                                            `);
                            }
                        });

                    },
                    error: function(xhr) {
                        console.error(xhr);
                    }
                });
            }

            function removeDetailCard(detailCard, container, openBillTable) {
                detailCard.hide(1000, function(a) {
                    const cardDetail = $(this);
                    cardDetail.css('display', 'none').animate({
                        opacity: 0
                    });
                    openBillTable.animate({
                        width: '100%'
                    });

                    container.removeClass('col-6').addClass('col-12');
                    container.animate({
                        width: '100%'
                    }, 500);
                });
            }

            function handleClickRowOpenBill(idOpenBill) {
                let outletTerpilih = $('#filter-outlet').val();
                if (tableContainer.hasClass('col-12')) {
                    tableContainer.removeClass('col-12').addClass('col-6');
                    tableContainer.animate({
                        width: '50%'
                    }, 500, function() {

                        // Setelah animasi selesai, tampilkan detail card
                        detailCard.css('display', 'block').animate({
                            opacity: 1
                        }, 500);
                    });

                    fetchDetailOpenBill(idOpenBill, outletTerpilih);
                } else {
                    if (stateLastId != idOpenBill) {
                        fetchDetailOpenBill(idOpenBill, outletTerpilih)
                    } else {
                        // Jika ingin mengembalikan ke ukuran asal
                        removeDetailCard(detailCard, tableContainer, openBillTable);
                        stateLastId = 0;
                        return
                    }
                }

                stateLastId = idOpenBill;
            }

            function restoreOpenBill(id) {
                Swal.fire({
                    title: 'Kamu yakin?',
                    text: "ingin mengembalikan Open Bill ini?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, restore it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: `{{ route('report/openbill/restoreOpenBill') }}`,
                            method: 'POST',
                            data: {
                                idOpenBill: id,
                            },
                            beforeSend: function() {
                                // showLoader();
                            },
                            complete: function() {
                                // showLoader(false);
                            },
                            success: function(res) {
                                showToast('success', res.message);
                                window.LaravelDataTables[idOpenBillDeletedDatatable].ajax.reload(null,
                                    false)
                            },
                            error: function(xhr) {
                                console.error(xhr);
                            }
                        });

                    }

                })
            }

            function checkActiveTab() {
                let activeTab = $('a.nav-link.active').attr('href');
                let outlet = $('#filter-outlet').val();
                let status = $('#status-bill').val();
                console.log(status);

                if (activeTab === "#open-bill") { // Use your tab's href id accordingly
                    window.LaravelDataTables[idOpenBillDatatable].ajax.reload(null, false);
                    openBillTable.DataTable().ajax.url("{{ route('report/openbill') }}?outlet=" + outlet +
                        "&datatable=openbill" + "&status=" + status).load();
                    $('#btn-restore-openbill').addClass('d-none');
                } else if (activeTab === "#openbill-deleted") {
                    window.LaravelDataTables[idOpenBillDeletedDatatable].ajax.reload(null, false)
                    openBillDeletedDatatable.DataTable().ajax.url("{{ route('report/openbill') }}?outlet=" + outlet +
                        "&datatable=openbill-deleted"  + "&status=" + status).load();
                    $('#btn-restore-openbill').removeClass('d-none');
                }
            }


            $(document).ready(function() {
                $('#btn-close-detail').off().on('click', function() {
                    removeDetailCard(detailCard, tableContainer, openBillTable);
                });
                $("#total-collected").text(formatRupiah(totalTransaksi.toString(), "Rp. "));
                $("#net-sales").text(formatRupiah(totalTransaksi.toString(), "Rp. "));

                $("#filter-outlet").select2();
                var success = "{{ session('success') }}";

                $('#status-bill').select2({
                    placeholder: "Pilih Status Bill ",
                    allowClear: true
                });


                $(".select2InsideModal").select2({
                    dropdownParent: $("#modal_action")
                });

                handleAction(idOpenBillDatatable);

                $('#filter-outlet').on('change', function() {
                    checkActiveTab();
                    // getNewOpenBillData()
                });

                $('#status-bill').on('change', function(){
                    checkActiveTab();
                })

                $('#btn-delete-openbill').on('click', function(e) {
                    e.preventDefault();

                    Swal.fire({
                        title: 'Are you sure?',
                        text: "You won't be able to revert this!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            handleAjax(this.href, 'post').onSuccess(function(res) {
                                // showToast(res.status, res.message)
                                window.LaravelDataTables[idOpenBillDatatable].ajax.reload(null,
                                    false)
                            }, false).excute();

                            showToast('success', "Data berhasil dihapus");
                            removeDetailCard(detailCard, tableContainer, openBillTable);
                        }
                    })

                });

                $('#openbill-table tbody tr').on('click', function() {
                    if (tableContainer.hasClass('col-12')) {
                        tableContainer.removeClass('col-12').addClass('col-6');
                    } else {
                        tableContainer.removeClass('col-6').addClass('col-12');
                    }
                });

                // On Bootstrap tab show event, reload table data via AJAX
                $('a[data-bs-toggle="pill"]').off().on('shown.bs.tab', function(e) {
                    checkActiveTab();
                });

                $('#btn-restore-openbill').off().on('click', function(e) {
                    e.preventDefault();
                    let idOpenBill = stateLastId;
                    restoreOpenBill(idOpenBill);
                });

            });
        </script>
    @endpush
@endsection
