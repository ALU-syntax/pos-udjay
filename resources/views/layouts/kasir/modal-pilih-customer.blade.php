<div class="modal-dialog modal-dialog-centered modal-lg" id="pilihCustomer">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="btn btn-outline-secondary" id="btn-batal-pilih-customer"
                data-bs-dismiss="modal">Batal</button>
            <h5 class="modal-title mx-auto text-center" id="productModalLabel">

            </h5>
            <button id="removeCustomer" type="button" class="btn btn-danger" >Remove</button>
        </div>
        <div class="modal-body w-100">
            <div class="row">
                <div class="col-4">
                    <div class="form-group">
                        <label>Name <span class="text-danger">*</span></label>
                        <input id="name" name="name" type="text" class="form-control" placeholder="Nama"
                            required>
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label>Umur</label>
                        <input type="text" id="umur" name="umur" type="number" class="form-control"
                            placeholder="Umur">
                    </div>
                </div>
                <div class="col-4">
                    <div class="form-group">
                        <label>Nomor Telfon</label>
                        <input id="telfon" name="telfon" type="text" class="form-control"
                            placeholder="Nomor telfon">
                    </div>
                </div>

                <div class="col-12">
                    <button class="btn btn-primary btn-lg w-100 my-3" id="btn-tambah-customer">Tambah Customer</button>
                </div>
            </div>

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

    $('#removeCustomer').on('click', function(){
        if(idPelanggan == ''){
            showToast('error', "Pelanggan Belum Dipilih");
        }else{
            idPelanggan = '';
            showToast('success', "Berhasil hapus pelanggan dari penjualan");
            $('#tambah-pelanggan').html("Tambah Pelanggan");
        }
    });

    document.getElementById('telfon').addEventListener('keyup', function(event) {
        const input = event.target;

        // Hanya izinkan angka
        if (!/^\d*$/.test(input.value)) {
            input.value = input.value.replace(/[^0-9]/g, ''); // Hapus karakter non-angka
        } 
    });

    $('#btn-tambah-customer').on('click', function() {
        let dataForm = new FormData();
        let namaCustomer = $('#name').val();
        let nomorTelfonCustomer = $('#telfon').val();
        let umurCustomer = $('#umur').val();

        dataForm.append('name', namaCustomer);
        dataForm.append('umur', umurCustomer);
        dataForm.append('telfon', nomorTelfonCustomer);

        $.ajax({
            url: "{{ route('customer/store') }}",
            method: "POST",
            data: dataForm,
            contentType: false,
            processData: false,
            beforeSend: function() {
                // submitLoader().show()
            },
            success: (res) => {

                if (res.status) {
                    console.log(res);
                    showToast(res.status, res.message);

                    'pilihpelanggan-table' && window.LaravelDataTables['pilihpelanggan-table'].ajax
                                .reload(null, false)
                }

            },
            complete: function() {
                // submitLoader().hide()
            },
            error: function(err) {
                const errors = err.responseJSON?.errors

                showToast('error', err.responseJSON?.message)
            }
        })
    })
</script>
