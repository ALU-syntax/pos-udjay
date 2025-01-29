<div class="modal-dialog modal-lg">
    <div class="modal-content">

        <!-- Modal Header -->
        <div class="modal-header">
            <button type="button" class="btn btn-outline-secondary btn-lg" data-dismiss="modal"
                id="btnBatal">Batal</button>
            <h5 class="modal-title mx-auto text-center" id="productModalLabel">
                Billing Management
            </h5>
            <button id="new-bill" type="button" class="btn btn-primary btn-lg">New Bill</button>
        </div>

        <!-- Modal Body -->
        <div class="modal-body">
            <!-- Tabs -->
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="openBills-tab" data-toggle="tab" href="#openBills" role="tab"
                        aria-controls="openBills" aria-selected="true">Open Bills</a>
                </li>
                {{-- <li class="nav-item">  
                    <a class="nav-link" id="cancelledBills-tab" data-toggle="tab" href="#cancelledBills" role="tab" aria-controls="cancelledBills" aria-selected="false">Cancelled Bills</a>  
                </li>  
                <li class="nav-item">  
                    <a class="nav-link" id="itemVoid-tab" data-toggle="tab" href="#itemVoid" role="tab" aria-controls="itemVoid" aria-selected="false">Item Void</a>  
                </li>   --}}
            </ul>
            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active" id="openBills" role="tabpanel" aria-labelledby="openBills-tab">
                    <!-- Search Bar -->
                    <div class="input-group mt-3 mb-3">
                        <input type="text" class="form-control" placeholder="Search..." aria-label="Search"
                            aria-describedby="basic-addon2">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button">Search</button>
                        </div>
                    </div>
                    <!-- Table -->
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Billing Name</th>
                                {{-- <th>Table Group</th> --}}
                                <th>Server</th>
                                <th>Time</th>
                                <th>Sync</th>
                            </tr>
                        </thead>
                        <tbody id="body-table-openbill">

                        </tbody>
                    </table>
                </div>
                {{-- <div class="tab-pane fade" id="cancelledBills" role="tabpanel" aria-labelledby="cancelledBills-tab">  
                    <!-- Content for Cancelled Bills tab -->  
                    <p>No cancelled bills available.</p>  
                </div>  
                <div class="tab-pane fade" id="itemVoid" role="tabpanel" aria-labelledby="itemVoid-tab">  
                    <!-- Content for Item Void tab -->  
                    <p>No voided items available.</p>  
                </div>   --}}
            </div>
        </div>
    </div>
</div>

<script>
    var dataListBill = @json($listBills);
    console.log(dataListBill);
    if (dataListBill.length > 0) {
        dataListBill.forEach(function(bill) {
            if (billId == bill.id) {
                var htmlBodyBill = `<tr>
                                        <td>${bill.name}</td>
                                        {{-- <td>Group 1</td> --}}
                                        <td>${bill.user.name}</td>
                                        <td>${bill.created_at_human}</td>
                                        <td><button class="btn btn-success btnSyncBill" data-id="${bill.id}"><i
                                                    class="fas fa-check text-white"
                                                    style="cursor: pointer"></i></button></td>
                                        <td style="color: blue;">&#8226;</td>
                                    </tr>`;
            } else {
                var htmlBodyBill = `<tr>
                                        <td>${bill.name}</td>
                                        <td>${bill.user.name}</td>
                                        <td>${bill.created_at_human}</td>
                                        <td><button class="btn btn-success btnSyncBill" data-id="${bill.id}"><i
                                                    class="fas fa-check text-white"
                                                    style="cursor: pointer"></i></button></td>
                                        <td></td>
                                    </tr>`;
            }

            $('#body-table-openbill').append(htmlBodyBill);
        });
    } else {
        var htmlBodyBill = `<tr>
                                <td class="text-center" colspan="4">Data Kosong </td>
                            </tr>`;
        $('#body-table-openbill').append(htmlBodyBill);
    }


    $('.btnSyncBill').on('click', function() {
        const dataId = $(this).data('id');
        billId = dataId;

        console.log(dataId)

        const baseUrlBill = `{{ route('kasir/chooseBill', ':id') }}`; // Placeholder ':id'
        const urlBill = baseUrlBill.replace(':id', dataId); // Ganti ':id' dengan nilai dataId

        $.ajax({
            url: urlBill,
            method: "GET",
            beforeSend: function() {
                showLoader();
                // showLoading()  
            },
            complete: function() {
                showLoader(false);
                // hideLoading(false)  
            },
            success: (res) => {
                listItem = [];
                listItemPromo = [];
                listRewardItem = [];
                res.data.item.forEach(function(item) {
                    let data = {
                        tmpId: item.tmp_id,
                        idProduct: item.product_id,
                        namaProduct: item.nama_product,
                        harga: item.harga,
                        quantity: item.quantity,
                        diskon: JSON.parse(item.diskon),
                        salesType: item.sales_type == "null" ? null : item.sales_type,
                        promo: JSON.parse(item.promo),
                        idVariant: item.variant_id,
                        namaVariant: item.nama_variant,
                        modifier: JSON.parse(item.modifier),
                        pilihan: JSON.parse(item.pilihan),
                        catatan: item.catatan ? item.catatan : "",
                        resultTotal: item.result_total, //result
                        openBillId: item.open_bill_id,
                    }

                    listItem.push(data);
                    syncItemCart()
                });

                iziToast['success']({
                    title: "Success",
                    message: "Sync Berhasil",
                    position: 'topRight'
                });

                const modal = $('#itemModal');
                modal.modal('hide');
            },
            error: function(err) {
                console.log(err);
                reject(err); // Rejecting the promise on error  
            }
        });
    });

    $('#new-bill').on('click', function() {
        billId = 0;
        listItem = [];
        listItemPromo = [];
        listRewardItem = [];

        syncItemCart()

        iziToast['success']({
            title: "Success",
            message: "Berhasil Membuat Order Baru",
            position: 'topRight'
        });

        const modal = $('#itemModal');
        modal.modal('hide');
    })
</script>
