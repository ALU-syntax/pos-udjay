<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <h4 class="modal-title w-100 text-center">Save As</h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-12">
                    <input type="text" class="form-control border-0 text-center" id="name-open-bill"
                        style="height: 80px; font-size: 18px;" placeholder="Nama">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <div class="row w-100">
                <div class="col-6">
                    <button id="cancel-openbill" type="button" data-bs-dismiss="modal"
                        class="btn btn-lg btn-secondary w-100">Cancel</button>
                </div>
                <div class="col-6">
                    <button id="save-open-bill" type="button" class="btn btn-lg btn-primary w-100">Save</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $('#cancel-openbill').on('click', function() {
        const modal = $('#itemModal')
        modal.modal('hide');
    });

    $('#save-open-bill').on('click', function() {
        openBillForm = new FormData();
        openBillForm.append('name', $('#name-open-bill').val());
        openBillForm.append('outlet_id', dataPattyCash[0].outlet_data.id);
        openBillForm.append('customer_id', idPelanggan);


        listItem.forEach(function(item, index) {
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
            openBillForm.append('bill_id_item[]', item.billId);
        });

        listItemPromo.forEach(function(item, index) {
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
            openBillForm.append('bill_id_item[]', item.billId);
        });

        console.log(openBillForm);
        $.ajax({
            url: "{{ route('kasir/openBill') }}",
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
                listDiskonAllItem = [];
                idPelanggan = '';

                console.log(res);

                if (window.Android) {
                    // Panggil metode JavaScript Interface dengan ID transaksi
                    window.Android.handlePrintOpenBill(res.data.id);
                }

                syncItemCart();
                iziToast['success']({
                    title: "Success",
                    message: "Berhasil Menyimpan Bill",
                    position: 'topRight'
                });

                const modal = $('#itemModal');
                modal.modal('hide');
            },
            complete: function() {
                showLoader(false);
            },
            error: function(err) {
                const errors = err.responseJSON?.errors

                showToast('error', err.responseJSON?.message)
            }
        });
    });
</script>
