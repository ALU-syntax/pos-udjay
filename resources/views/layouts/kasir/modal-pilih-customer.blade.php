<div class="modal-dialog  modal-xl" id="pilihCustomer">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="btn btn-outline-secondary" id="btn-batal-pilih-customer"
                data-bs-dismiss="modal">Batal</button>
            <h5 class="modal-title mx-auto text-center" id="productModalLabel">

            </h5>
            <button id="removeCustomer" type="button" class="btn btn-danger">Remove</button>
        </div>
        <div class="modal-body w-100">
            {!! $dataTable->table(['class' => 'table table-bordered table-striped table-responsive w-100'], true) !!}

            {!! $dataTable->scripts() !!}

        </div>
    </div>
</div>


<script>
    $('#btn-batal-pilih-customer').on('click', function() {
        const modal = $('#itemModal');
        modal.modal('hide');
    });

    $('#removeCustomer').on('click', function() {
        if (idPelanggan == '') {
            showToast('error', "Pelanggan Belum Dipilih");
        } else {
            idPelanggan = '';
            showToast('success', "Berhasil hapus pelanggan dari penjualan");
            $('#pilih-pelanggan').html("Pilih Pelanggan");

            const modal = $('#itemModal');
            modal.modal('hide');
        }
    });
</script>
