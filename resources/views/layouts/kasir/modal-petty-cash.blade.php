<style>
    .btn-choice {
        width: 100px;
        height: 100px;
    }
</style>

<div class="modal-dialog modal-dialog-centered modal-lg" id="pattyCash">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" id="btnClosePattyCash" class="btn btn-outline-secondary btn-lg"
                data-bs-dismiss="modal">Batal</button>
            <h5 class="modal-title mx-auto text-center" id="productModalLabel">
                <strong id="namaProduct">Mulai Shift</strong><br>
            </h5>
        </div>
        <div class="modal-body">
            <!-- Payment Options -->
            <div class="container">
                <div class="row d-flex justify-content-center">
                    <img width="250px" src="{{ asset('img/cashier-machine.png') }}" alt="">
                </div>
            </div>
            <hr>

            <form id="actionPattyCash" action="{{ route('kasir/pattyCash') }}" method="POST"
                enctype="multipart/form-data">
                @csrf

                <div class="container mt-2">
                    <div class="row">
                        <div class="form-group w-100">
                            <div class="input-group input-group-lg">
                                <span class="input-group-text" id="inputGroup-sizing-lg">Saldo Tunai</span>
                                <input type="text" name="saldo_awal" id="saldo_awal" class="form-control"
                                    aria-label="Sizing example input" aria-describedby="inputGroup-sizing-lg">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <button type="submit" id="btnSubmitPattyCash"
                        class="btn btn-primary btn-lg w-100 mx-3">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $("#btnClosePattyCash").on('click', function(e) {
        // Tutup modal
        const modal = $('#itemModal');
        modal.modal('hide');
    });

    var saldoAwalInput = document.getElementById('saldo_awal');

    saldoAwalInput.addEventListener("keyup", function(e) {
        this.value = formatRupiah(this.value, "Rp. ");
    })

    $("#actionPattyCash").on('submit', function(e) {
        e.preventDefault();
        // let url = "{{ route('kasir/pattyCash') }}"

        const _form = this
        let data = new FormData(_form);
        let dataForm;
        let dataCustom = false;
        data.forEach(function(item, index) {
            console.log(index, item);
            if (index == "input-modifier-product") {
                dataCustom = true;
            }
        });
        dataForm = data;
        console.log(dataForm)
        // if (dataCustom) {
        //     const token = document.querySelector('meta[name="csrf_token"]').getAttribute('content');
        //     dataForm = new FormData();
        //     dataForm.append("_token", token);
        //     dataForm.append("_method", 'put');
        //     tmpDataProductModifier.forEach(function(item) {
        //         dataForm.append("products[]", item);
        //     });
        // } else {
        //     dataForm = data;
        // }
        $.ajax({
            url: this.action,
            method: this.method,
            data: dataForm,
            contentType: false,
            processData: false,
            beforeSend: function() {
                $(_form).find('.is-invalid').removeClass('is-invalid')
                $(_form).find(".invalid-feedback").remove()
                // submitLoader().show()
            },
            success: (res) => {
                console.log(res)
                $('#itemModal').modal('hide')
                showToast(res.status, res.message)
            },
            complete: function() {
                // submitLoader().hide()
            },
            error: function(err) {
                const errors = err.responseJSON?.errors

                if (errors) {
                    for (let [key, message] of Object.entries(errors)) {
                        console.log(message);
                        $(`[name=${key}]`).addClass('is-invalid')
                            .parent()
                            .append(
                                `<div class="invalid-feedback">${message}</div>`
                            )
                    }
                }

                showToast('error', err.responseJSON?.message)
            }
        })
    })
</script>
