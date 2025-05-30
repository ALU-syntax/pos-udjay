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
                            {{-- <option value="all" selected>-- Semua Outlet --</option> --}}
                            @foreach ($outlets as $outlet)
                                <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                            @endforeach
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
                            <div class="row">
                                <div class="col-6">
                                    <h5>Order Details</h5>
                                </div>

                                @can('delete report/transactions')
                                    <div class="col-6 d-flex align-self-end justify-content-end">
                                        {{-- <button class="btn btn-danger">Hapus Transaksi</button> --}}
                                        <a id="btn-delete-openbill" class="btn btn-danger" href="">Hapus
                                            Open Bill</a>
                                    </div>
                                @endcan
                            </div>
                            <hr>
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
                                <button class="btn btn-primary" id="btn-close-detail">Close</button>
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
        <script>
            var dataTransaksi = @json($data);
            let totalTransaksi = 0;
            dataTransaksi.forEach(function(item) {
                totalTransaksi += item.total;
            });
            var stateLastId = 0;
            const tableContainer = $('#tableContainer');
            const detailCard = $('#container-order-details');
            const openBillTable = $('#openbill-table');

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

            $('#openbill-table tbody tr').on('click', function() {
                var tableContainer = $('#tableContainer');
                console.log(tableContainer.hasClass('col-12'))
                if (tableContainer.hasClass('col-12')) {
                    tableContainer.removeClass('col-12').addClass('col-6');
                } else {
                    tableContainer.removeClass('col-6').addClass('col-12');
                }
            });

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
                        let total = 0;
                        let pajak = res.pajak.value;
                        let totalNominalPajak = 0;

                        res.data.item.forEach(function(item){
                            let harga = item.harga;
                            let itemTerbayar = item.qty_terbayar ? item.qty_terbayar : 0;
                            let qty = parseInt(item.quantity) + itemTerbayar;

                            let hargaAkhir = harga * qty;

                            let pajakItem = hargaAkhir * pajak / 100;

                            totalNominalPajak += pajakItem;

                            total += hargaAkhir;
                        });

                        let totalHarga = total + totalNominalPajak;
                        let status = res.data.deleted_at ? '<span class="badge badge-success">Sudah dibayar</span>' : '<span class="badge badge-danger">Belum dibayar</span>'
                        $('#created-time').text(res.data.create_formated);
                        $('#collected-by').text(res.data.user.name);
                        $('#total-amount').text(formatRupiah(total.toString(), "Rp. "));
                        $('#payment-method').text(res.data.nama_tipe_pembayaran);
                        $('#status-open-bill').html(status);
                        $('#tax').text(formatRupiah(totalNominalPajak.toString(), "Rp. "));
                        $('#total').text(formatRupiah(totalHarga.toString(), "Rp. "));

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

                            let qtyText = qtyTerbayar > 0 && item.deleted_at == null ? `${item.quantity} (Sudah dibayar ${qtyTerbayar})` : qtyPesanan;
                            console.log(qtyText);
                            if (item.product) {
                                let namaProduct = item.variant.name == item.product.name ? item
                                    .product.name :
                                    `${item.product.name} - ${item.variant.name}`

                                tbody.append(`
                                                <tr>
                                                    <td>${namaProduct}</td>
                                                    <td>${qtyText}</td>
                                                </tr>
                                            `);
                            } else {
                                tbody.append(`
                                                <tr>
                                                    <td>custom</td>
                                                    <td>${qtyText}</td>
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

            function removeDetailCard(detailCard, tableContainer, openBillTable) {
                detailCard.hide(1000, function(a) {
                    const cardDetail = $(this);
                    cardDetail.css('display', 'none').animate({
                        opacity: 0
                    });
                    openBillTable.animate({
                        width: '100%'
                    });

                    tableContainer.removeClass('col-6').addClass('col-12');
                    tableContainer.animate({
                        width: '100%'
                    }, 500);
                });
            }

            function manipulateIdShowReceipt(id) {
                var $link = $('#btn-show-receipt');

                // Ambil nilai atribut href
                var currentHref = $link.attr('href');

                // Pecah URL menjadi bagian-bagian
                // Misalnya, URL adalah /report/transaction/showReceipt/1
                // Kita akan mengambil bagian terakhir yang merupakan angka 1
                var urlParts = currentHref.split('/');
                var lastIndex = urlParts.length - 1;
                var currentNumber = parseInt(urlParts[lastIndex], 10);

                // Ubah angka tersebut (misalnya tambahkan 1)
                var newNumber = id;

                // Ganti bagian terakhir dari URL dengan angka baru
                urlParts[lastIndex] = newNumber.toString();

                // Gabungkan kembali bagian-bagian URL menjadi URL baru
                var newHref = urlParts.join('/');

                // Ganti atribut href dengan URL baru
                $link.attr('href', newHref);
            }

            function handleClickRowOpenBill(idOpenBill) {
                let outletTerpilih = $('#filter-outlet').val();
                // manipulateIdShowReceipt(idOpenBill);
                if (tableContainer.hasClass('col-12')) {
                    tableContainer.removeClass('col-12').addClass('col-6');
                    tableContainer.animate({
                        width: '50%'
                    }, 500, function() {
                        openBillTable.animate({
                            width: '50%'
                        });

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


            $(document).ready(function() {
                $('#btn-close-detail').off().on('click', function() {
                    removeDetailCard(detailCard, tableContainer, openBillTable);
                });
                $("#total-collected").text(formatRupiah(totalTransaksi.toString(), "Rp. "));
                $("#net-sales").text(formatRupiah(totalTransaksi.toString(), "Rp. "));

                $("#filter-outlet").select2();
                var success = "{{ session('success') }}";
                const datatable = 'openbill-table';

                $(".select2InsideModal").select2({
                    dropdownParent: $("#modal_action")
                });

                handleAction(datatable);

                $('#filter-outlet').on('change', function() {
                    var table = $('#' + datatable).DataTable();

                    // Refresh tabel
                    table.ajax.url("{{ route('report/openbill') }}?outlet=" + $('#filter-outlet').val())
                        .load();

                    getNewOpenBillData()
                });

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
                                window.LaravelDataTables[datatable].ajax.reload(null, false)
                            }, false).excute();

                            showToast('success', "Data berhasil dihapus");
                            removeDetailCard(detailCard, tableContainer, openBillTable);
                        }
                    })

                });


            });
        </script>
    @endpush
@endsection
