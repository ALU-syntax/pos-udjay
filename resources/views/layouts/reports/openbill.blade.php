@extends('layouts.app')
@section('content')
    @include('layouts.reports.partials.ui-styles')

    <div class="main-content ob-page">
        {{-- Header --}}
        <div class="ob-header">
            <div>
                <div class="ob-header__eyebrow">Laporan</div>
                <h1 class="ob-header__title">
                    <span class="ob-header__icon" aria-hidden="true"><i class="fas fa-receipt"></i></span>
                    Open Bill
                </h1>
                <p class="ob-header__desc">Pantau bill yang sedang berjalan maupun yang sudah dibayar di setiap outlet.</p>
            </div>
        </div>

        {{-- Filter --}}
        <div class="ob-card">
            <div class="ob-toolbar">
                <div class="ob-field">
                    <label for="filter-outlet"><i class="fas fa-store" aria-hidden="true"></i> Outlet</label>
                    <select id="filter-outlet" class="form-control select2">
                        <option value="all" selected>-- Semua Outlet --</option>
                        @foreach ($outlets as $outlet)
                            <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="ob-field">
                    <label for="status-bill"><i class="fas fa-filter" aria-hidden="true"></i> Status Pembayaran</label>
                    <select id="status-bill" class="w-100" data-style="btn-default">
                        <option value="" selected disabled>Pilih Status</option>
                        <option value="1">Sudah Dibayar</option>
                        <option value="0">Belum Dibayar</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Tabel --}}
            <div class="col-12 animated" id="tableContainer">
                <div class="ob-card">
                    <div class="ob-tabs-bar">
                        <ul class="nav ob-tabs" id="line-tab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a class="nav-link active" id="open-bill-tab" data-bs-toggle="pill" href="#open-bill"
                                    role="tab" aria-controls="open-bill" aria-selected="true">
                                    <i class="fas fa-list-ul" aria-hidden="true"></i> List Bill
                                    <span class="ob-count" id="count-open-bill" aria-label="Jumlah bill">0</span>
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" id="open-bill-deleted" data-bs-toggle="pill" href="#openbill-deleted"
                                    role="tab" aria-controls="openbill-deleted" aria-selected="false" tabindex="-1">
                                    <i class="fas fa-trash-alt" aria-hidden="true"></i> Open Bill Deleted
                                    <span class="ob-count" id="count-openbill-deleted" aria-label="Jumlah bill terhapus">0</span>
                                </a>
                            </li>
                        </ul>
                        <span class="ob-hint"><i class="fas fa-mouse-pointer" aria-hidden="true"></i> Klik baris untuk melihat detail</span>
                    </div>

                    @can('delete report/openbill')
                        <div class="ob-bulkbar" id="ob-bulkbar" role="region" aria-live="polite" aria-label="Aksi bill terpilih">
                            <div class="ob-bulkbar__info">
                                <i class="fas fa-check-square" aria-hidden="true"></i>
                                <span><span id="ob-selected-count">0</span> bill dipilih</span>
                            </div>
                            <div class="ob-bulkbar__actions">
                                <button type="button" class="btn ob-btn ob-btn-ghost" id="btn-clear-selection">Batal</button>
                                <button type="button" class="btn ob-btn ob-btn-danger" id="btn-bulk-delete-openbill">
                                    <i class="fas fa-trash-alt" aria-hidden="true"></i> Hapus Terpilih
                                </button>
                            </div>
                        </div>
                    @endcan

                    <div class="tab-content ob-table-wrap" id="line-tabContent">
                        <div class="tab-pane fade show active" id="open-bill" role="tabpanel"
                            aria-labelledby="open-bill-tab">
                            <div class="table-responsive">
                                {!! $dataTable->table() !!}
                            </div>
                        </div>
                        <div class="tab-pane fade" id="openbill-deleted" role="tabpanel"
                            aria-labelledby="open-bill-deleted">
                            <div class="table-responsive">
                                {!! $openBillDeletedDataTable->table(['class' => 'table table-bordered table-striped w-100'], true) !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Panel detail --}}
            <div class="col-6" id="container-order-details">
                <div class="ob-card ob-detail">
                    <div class="ob-detail__head">
                        <div>
                            <div class="ob-detail__label">Order Details</div>
                            <div class="ob-detail__name" id="nama-open-bill"></div>
                            <div id="status-open-bill"></div>
                        </div>
                        <button type="button" class="ob-icon-btn" id="btn-close-detail" aria-label="Tutup detail">
                            <i class="fas fa-times" aria-hidden="true"></i>
                        </button>
                    </div>

                    <div class="ob-detail__body">
                        <h2 class="ob-section-title"><i class="fas fa-info-circle" aria-hidden="true"></i> Informasi</h2>
                        <dl class="ob-meta">
                            <div class="ob-meta__item">
                                <dt>Tanggal Pembuatan</dt>
                                <dd><span id="created-time"></span></dd>
                            </div>
                            <div class="ob-meta__item">
                                <dt>Collected By</dt>
                                <dd id="collected-by"></dd>
                            </div>
                            <div class="ob-meta__item">
                                <dt>Served by</dt>
                                <dd id="served-by"></dd>
                            </div>
                            <div class="ob-meta__item">
                                <dt>Payment Method</dt>
                                <dd id="payment-method"></dd>
                            </div>
                            <div class="ob-meta__item">
                                <dt>Order Id</dt>
                                <dd></dd>
                            </div>
                            <div class="ob-meta__item">
                                <dt>Table</dt>
                                <dd></dd>
                            </div>
                            <div class="ob-meta__item">
                                <dt>Duration</dt>
                                <dd></dd>
                            </div>
                        </dl>

                        <h2 class="ob-section-title"><i class="fas fa-calculator" aria-hidden="true"></i> Ringkasan</h2>
                        <div class="ob-summary">
                            <div class="ob-summary__row">
                                <div>Amount Item</div>
                                <div id="total-amount"></div>
                            </div>
                            <div class="ob-summary__row">
                                <div>Tax</div>
                                <div id="tax"></div>
                            </div>
                            <div class="ob-summary__row ob-summary__row--total">
                                <div>Total <small class="fw-normal text-muted">(terbayar)</small></div>
                                <div id="total"></div>
                            </div>
                        </div>

                        <h2 class="ob-section-title"><i class="fas fa-utensils" aria-hidden="true"></i> Ordered Items</h2>
                        <div class="ob-items">
                            <table class="table" id="table-list-item">
                                <thead>
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

                    <div class="ob-detail__foot">
                        <div class="ob-actions">
                            @can('delete report/openbill')
                                <a id="btn-delete-openbill" class="btn ob-btn ob-btn-danger" href="">
                                    <i class="fas fa-trash-alt" aria-hidden="true"></i> Hapus Open Bill
                                </a>
                            @endcan
                            <button class="btn btn-primary ob-btn d-none" id="btn-restore-openbill">
                                <i class="fas fa-undo" aria-hidden="true"></i> Restore Openbill
                            </button>
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

            // Badge jumlah data pada tab (hanya tampilan)
            openBillTable.on('xhr.dt', function(e, settings, json) {
                if (json) $('#count-open-bill').text(json.recordsFiltered ?? 0);
            });
            openBillDeletedDatatable.on('xhr.dt', function(e, settings, json) {
                if (json) $('#count-openbill-deleted').text(json.recordsFiltered ?? 0);
            });

            // Tandai baris yang sedang dibuka di panel detail (hanya tampilan)
            function markActiveRow(idOpenBill) {
                $('.ob-page tr.ob-row-active').removeClass('ob-row-active');
                if (idOpenBill) $(`#${idOpenBillDatatable} tr[id="${idOpenBill}"], #${idOpenBillDeletedDatatable} tr[id="${idOpenBill}"]`).addClass('ob-row-active');
            }
            openBillTable.add(openBillDeletedDatatable).on('draw.dt', function() {
                markActiveRow(stateLastId);
            });

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
                        // console.log(res)
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
                            // console.log(item);
                            let harga = item.harga;
                            let itemTerbayar = item.qty_terbayar ? item.qty_terbayar : 0;
                            let qty = parseInt(item.quantity) + itemTerbayar;

                            let listModifier = [];
                            try{
                                listModifier = JSON.parse(item.modifier);
                            }catch(e){
                                listModifier = [];
                            }

                            let listHargaModifier = listModifier.map((val) => val.harga);
                            let totalHargaModifier = listHargaModifier.reduce((sum, value) => sum + value, 0);
                            let hargaAkhirModifier = totalHargaModifier * qty;
                            
                            let hargaAkhirItem = harga * qty;

                            let hargaAkhir = hargaAkhirItem + hargaAkhirModifier;

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
                            // console.log(item);
                            let qtyTerbayar = item.qty_terbayar ? item.qty_terbayar : 0;
                            let qtyPesanan = parseInt(item.quantity) + qtyTerbayar;

                            let modifiers = [];
                            try {
                                modifiers = JSON.parse(item.modifier) ?? [];
                            } catch (e) {
                                modifiers = [];
                            }

                            let modifierHtml = '';
                            if (modifiers.length > 0) {
                                modifierHtml = `
                                    <ul class="mb-0 ps-3 small text-muted">
                                        ${modifiers.map(m => `<li>${m.nama ?? "modifier tidak ditemukan"}</li>`).join('')}
                                    </ul>
                                `;
                            }
                            

                            let qtyText = qtyTerbayar > 0 && item.deleted_at == null ?
                                `${qtyPesanan} <span class="badge rounded-pill badge-success">Paid ${qtyTerbayar}</span>` : (item.deleted_at == null ? qtyPesanan : `${qtyPesanan} <span class="badge rounded-pill badge-success">Paid ${qtyPesanan}</span>`);
                            if (item.product) {
                                let namaProduct = item.variant.name == item.product.name ? item
                                    .product.name :
                                    `${item.product.name} - ${item.variant.name}`

                                tbody.append(`
                                                <tr>
                                                    <td>
                                                        <div class="fw-semibold">${namaProduct}</div>
                                                        ${modifierHtml}
                                                    </td>
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
                markActiveRow(0);
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
                markActiveRow(idOpenBill);
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
                // console.log(status);

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

            // ===== Bulk delete open bill =====
            // Pilihan disimpan lintas halaman pagination, dikosongkan saat filter/tab berubah.
            const selectedOpenBills = new Map(); // id => nama bill

            function escapeHtml(text) {
                return $('<div>').text(text ?? '').html();
            }

            function syncBulkSelectionUi() {
                const jumlah = selectedOpenBills.size;
                $('#ob-selected-count').text(jumlah);
                $('#ob-bulkbar').toggleClass('is-visible', jumlah > 0);

                const rowChecks = openBillTable.find('tbody input.ob-row-check');
                rowChecks.each(function() {
                    const checked = selectedOpenBills.has(String(this.value));
                    this.checked = checked;
                    $(this).closest('tr').toggleClass('ob-row-checked', checked);
                });

                const totalDiHalaman = rowChecks.length;
                const terpilihDiHalaman = rowChecks.filter(':checked').length;
                const checkAll = $('#ob-check-all')[0];
                if (checkAll) {
                    checkAll.checked = totalDiHalaman > 0 && terpilihDiHalaman === totalDiHalaman;
                    checkAll.indeterminate = terpilihDiHalaman > 0 && terpilihDiHalaman < totalDiHalaman;
                }
            }

            function clearBulkSelection() {
                selectedOpenBills.clear();
                syncBulkSelectionUi();
            }

            function bulkDeleteOpenBill() {
                const ids = Array.from(selectedOpenBills.keys());
                if (!ids.length) return;

                const names = Array.from(selectedOpenBills.values());
                const maxTampil = 5;
                let listHtml = names.slice(0, maxTampil).map(n => `<li>${escapeHtml(n)}</li>`).join('');
                if (names.length > maxTampil) {
                    listHtml += `<li><em>+${names.length - maxTampil} lainnya</em></li>`;
                }

                Swal.fire({
                    title: `Hapus ${ids.length} Open Bill?`,
                    html: `<ul class="text-start mb-0">${listHtml}</ul>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, hapus semua',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (!result.isConfirmed) return;

                    $.ajax({
                        url: `{{ route('report/openbill/bulkDeleteOpenBill') }}`,
                        method: 'POST',
                        data: {
                            ids: ids,
                        },
                        beforeSend: function() {
                            showLoader();
                            $('#btn-bulk-delete-openbill').prop('disabled', true);
                        },
                        complete: function() {
                            showLoader(false);
                            $('#btn-bulk-delete-openbill').prop('disabled', false);
                        },
                        success: function(res) {
                            showToast('success', res.message);

                            if (stateLastId && selectedOpenBills.has(String(stateLastId))) {
                                removeDetailCard(detailCard, tableContainer, openBillTable);
                                stateLastId = 0;
                            }

                            clearBulkSelection();
                            window.LaravelDataTables[idOpenBillDatatable].ajax.reload(null, false);
                            window.LaravelDataTables[idOpenBillDeletedDatatable].ajax.reload(null, false);
                        },
                        error: function(xhr) {
                            console.error(xhr);
                            Swal.fire('Gagal', xhr.responseJSON?.message ?? 'Open Bill gagal dihapus', 'error');
                        }
                    });
                });
            }

            $(document).ready(function() {
                openBillTable.on('change', 'tbody input.ob-row-check', function() {
                    if (this.checked) {
                        selectedOpenBills.set(String(this.value), $(this).data('name'));
                    } else {
                        selectedOpenBills.delete(String(this.value));
                    }
                    syncBulkSelectionUi();
                });

                $(document).on('change', '#ob-check-all', function() {
                    const checked = this.checked;
                    openBillTable.find('tbody input.ob-row-check').each(function() {
                        if (checked) {
                            selectedOpenBills.set(String(this.value), $(this).data('name'));
                        } else {
                            selectedOpenBills.delete(String(this.value));
                        }
                    });
                    syncBulkSelectionUi();
                });

                openBillTable.on('draw.dt', syncBulkSelectionUi);

                // Filter / tab berubah -> data berbeda, kosongkan pilihan agar tidak salah hapus
                $('#filter-outlet, #status-bill').on('change', clearBulkSelection);
                $('a[data-bs-toggle="pill"]').on('shown.bs.tab', clearBulkSelection);

                $('#btn-clear-selection').on('click', clearBulkSelection);
                $('#btn-bulk-delete-openbill').on('click', bulkDeleteOpenBill);
            });
        </script>
    @endpush
@endsection
