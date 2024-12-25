<style>
    .btn-choice {
        width: 100px;
        height: 100px;
    }
</style>

<div class="modal-dialog modal-dialog-centered modal-lg" id="choosePayment">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
            <h5 class="modal-title mx-auto text-center" id="productModalLabel">
                <strong id="totalHarga">Rp 80.000</strong><br>
            </h5>
            <button id="pay" type="button" class="btn btn-primary" disabled>Simpan</button>
        </div>
        <div class="modal-body">
            <!-- Payment Options -->
            <div class="row">
                <div class="col-3">
                    <h6 class="d-flex text-center">Cash</h6>
                </div>
                <div class="col-9">
                    <div class="row"></div>
                    <div class="btn-group w-100 d-flex " id="btnCash" role="group">
                        <button type="button" class="btn btn-lg btn-choice btn-outline-primary">Rp 50.000</button>
                        <button type="button" class="btn btn-lg btn-choice btn-outline-primary">Rp 100.000</button>
                        <button type="button" class="btn btn-lg btn-choice btn-outline-primary">Rp 150.000</button>
                    </div>
                    <input type="numeric" id="inputMoney" class="form-control mt-2" placeholder="Cash Amount">
                </div>
            </div>

            <hr>
            <div class="row">
                <div class="col-3">
                    <h6 class="d-flex text-center">EDC</h6>
                </div>
                <div class="col-9">
                    <div class="btn-group w-100 d-flex" id="btnEdc" role="group">
                        <button type="button" class="btn btn-lg btn-choice btn-outline-primary">QRIS</button>
                        <button type="button" class="btn btn-lg btn-choice btn-outline-primary">DEBIT</button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    var edcType = '';
    var cashAmoun = 0;
    var moneyInput = document.getElementById("inputMoney");
    moneyInput.addEventListener("keyup", function(e) {

        let textHarga = document.getElementById("total").textContent;
        let harga = textHarga.trim();
        let totalHarga = parseInt(harga.replace(/[^\d]/g, ""));

        let convertHargaToInt = parseInt(this.value.replace('Rp. ', '').replace(/\./g, ''));
        console.log(convertHargaToInt);
        console.log(totalHarga)
        $('#btnEdc button').removeClass('active');
        $('#btnCash button').removeClass('active');

        if (this.value == '' || convertHargaToInt < totalHarga) {
            $("#pay").attr('disabled', true);
        } else {
            $("#pay").removeAttr('disabled');
        }

        this.value = formatRupiah(this.value, "Rp. ");
    });

    function updateHargaText() {
        let total = document.getElementById("total").textContent;
        let textTotal = total.trim();
        let angkaTotal = parseInt(textTotal.replace(/[^\d]/g, ""));

        let rounding = document.getElementById("rounding").textContent;
        if (rounding) {
            let textRounding = rounding.trim();
            let angkaRounding = parseInt(textRounding.replace(/[^\d]/g, ""));

            let symbol = textRounding.charAt(0);

            let hargaFinal = symbol == "-" ? angkaTotal - angkaRounding : angkaTotal + angkaRounding;

            $('#totalHarga').text(formatRupiah(hargaFinal.toString(), "Rp. "));
        } else {
            $('#totalHarga').text(formatRupiah(angkaTotal.toString(), "Rp. "));
        }
    }

    $(document).ready(function() {
        updateHargaText()

        $('#btnEdc button').on('click', function() {
            $("#pay").removeAttr('disabled');

            // Hapus class 'active' dari semua tombol
            $('#btnEdc button').removeClass('active');
            $('#btnCash button').removeClass('active');
            // Tambahkan class 'active' ke tombol yang diklik
            $(this).addClass('active');

            // Ambil teks dari tombol yang aktif
            var selectedButton = $('#btnEdc button.active').text();
            console.log("Tombol yang dipilih: " + selectedButton);
        });

        $('#btnCash button').on('click', function() {


            // Hapus class 'active' dari semua tombol
            $('#btnCash button').removeClass('active');
            $('#btnEdc button').removeClass('active');
            // Tambahkan class 'active' ke tombol yang diklik
            $(this).addClass('active');

            // Ambil teks dari tombol yang aktif
            var selectedButton = $('#btnCash button.active').text();
            let trimSelected = selectedButton.trim();
            let hilangkanTextRp = trimSelected.replace(/[^\d]/g, "")
            let convertTextHargaButton = parseInt(hilangkanTextRp);

            //harga
            let textHarga = document.getElementById("total").textContent;
            let harga = textHarga.trim();
            let totalHarga = parseInt(harga.replace(/[^\d]/g, ""));

            if (convertTextHargaButton < totalHarga) {
                $("#pay").attr('disabled', true);
            } else {
                $("#pay").removeAttr('disabled');
            }


            console.log("Tombol yang dipilih: " + selectedButton);
        });

        $('#pay').on('click', function() {
            let selectButton = $('button.active').text();
            let checkTypeButton = $('button.active').closest('.btn-group').attr('id');

            // Pengecekan apakah berasal dari btnEdc atau btnCash
            if (checkTypeButton === 'btnEdc') {
                console.log("Pembayaran melalui EDC");
            } else if (checkTypeButton === 'btnCash') {
                console.log("Pembayaran melalui Cash");
            } else {
                console.log("pembayaran melalui cash input")
            }

            var dataForm = new FormData();
            dataForm.append('data', listItem);

            
            $.ajax({
                url: "{{ route('kasir/bayar') }}",
                method: "POST",
                data: dataForm,
                contentType: false,
                processData: false,
                beforeSend: function() {
                    // submitLoader().show()
                },
                success: (res) => {
                    console.log(res)
                },
                complete: function() {
                    // submitLoader().hide()
                },
                error: function(err) {
                    const errors = err.responseJSON?.errors

                    showToast('error', err.responseJSON?.message)
                }
            })

        });
    });
</script>
