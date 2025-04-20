<div class="modal-dialog">
    <div class="modal-content">
        <form id="actionResendReceipt" action="{{ $action }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
                <h4 class="modal-title w-100 text-center">Resend Receipt</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-12">
                        <input type="email" class="form-control border-0 text-center" id="email" name="email"
                            style="height: 80px; font-size: 18px;" placeholder="Email" required>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <div class="row w-100">
                    <div class="col-6">
                        <button id="cancelResendReceipt" type="button" data-bs-dismiss="modal"
                            class="btn btn-lg btn-secondary w-100">Cancel</button>
                    </div>
                    <div class="col-6">
                        <button type="submit" class="btn btn-lg btn-primary w-100">Send  &nbsp; <i class="fas fa-paper-plane" style="font-size: 18px;"></i></button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    $("#cancelResendReceipt").on('click', function(e) {
        // Tutup modal
        const modal = $('#itemModal');
        modal.modal('hide');
    });

    $("#actionResendReceipt").on('submit', function(e) {
        e.preventDefault();

        const _form = this
        let data = new FormData(_form);

        console.log(data)
        $.ajax({
            url: this.action,
            method: this.method,
            data: data,
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
    });
</script>
