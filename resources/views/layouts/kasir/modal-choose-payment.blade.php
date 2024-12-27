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
        console.log(this.value);
        console.log(convertHargaToInt);
        console.log(totalHarga)
        $('#btnEdc button').removeClass('active');
        $('#btnCash button').removeClass('active');

        if (this.value == '' || convertHargaToInt < totalHarga || this.value == 'Rp. ') {
            $("#pay").attr('disabled', true);
        } else {
            $("#pay").removeAttr('disabled');
        }

        this.value = formatRupiah(this.value, "Rp. ");
    });

    function updateHargaText() {
        let total = ambilHargaTotal();
        $('#totalHarga').text(formatRupiah(total.toString(), "Rp. "));
    }

    function ambilHargaTotal() {
        let total = document.getElementById("total").textContent;
        let textTotal = total.trim();
        let angkaTotal = parseInt(textTotal.replace(/[^\d]/g, ""));

        let rounding = document.getElementById("rounding").textContent;
        if (rounding) {
            let textRounding = rounding.trim();
            let angkaRounding = parseInt(textRounding.replace(/[^\d]/g, ""));

            let symbol = textRounding.charAt(0);

            let hargaFinal = symbol == "-" ? angkaTotal - angkaRounding : angkaTotal + angkaRounding;

            return hargaFinal;
        } else {
            return angkaTotal
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
            let selectButtonTrim = selectButton.trim();
            let selectButtonAngka = parseInt(selectButtonTrim.replace(/[^\d]/g, ""));

            let checkTypeButton = $('button.active').closest('.btn-group').attr('id');

            let cashInput = $("#inputMoney").val();
            let trimCashInput = cashInput.trim();
            let cashInputAngka = parseInt(trimCashInput.replace(/[^\d]/g, ""));

            let hargaTotal = ambilHargaTotal();
            let tipePembayaran = '';
            let nominalBayar = 0;
            let change = 0;

            // Pengecekan apakah berasal dari btnEdc atau btnCash
            if (checkTypeButton === 'btnEdc') {
                tipePembayaran = selectButton.toLowerCase() == "qris" ? 'qris' : 'debit';
                nominalBayar = hargaTotal;
            } else if (checkTypeButton === 'btnCash') {
                console.log("Pembayaran melalui Cash");
                tipePembayaran = 'cash'
                nominalBayar = selectButtonAngka;
                change = hargaTotal - selectButtonAngka;
            } else {
                console.log("pembayaran melalui cash input")
                tipePembayaran = 'cash';
                nominalBayar = cashInputAngka;
                change = hargaTotal - cashInputAngka;
            }

            var dataForm = new FormData();
            listItem.forEach(function(item, index) {
                if (item.quantity > 1) {
                    for (let x = 0; x < item.quantity; x++) {
                        dataForm.append('idProduct[]', item.idProduct);
                        let tmpDiscountData = [];
                        item.diskon.forEach(function(discountItem, indexItem) {
                            let discount = {
                                id: discountItem.id,
                                nama: discountItem.nama,
                                result: discountItem.result,
                                satuan: discountItem.satuan,
                                value: discountItem.value
                            }

                            tmpDiscountData.push(discount);
                        });

                        dataForm.append('discount_id[]', JSON.stringify(tmpDiscountData));

                        let tmpModifierData = [];
                        item.modifier.forEach(function(itemModifier, indexModifier) {
                            let modifier = {
                                id: itemModifier.id,
                                nama: itemModifier.nama,
                                harga: itemModifier.harga
                            }

                            tmpModifierData.push(modifier);
                        })
                        dataForm.append('modifier_id[]', JSON.stringify(tmpModifierData));
                        dataForm.append('catatan[]', item.catatan);
                    }
                } else {
                    dataForm.append('idProduct[]', item.idProduct);
                    let tmpDiscountData = [];
                    item.diskon.forEach(function(discountItem, indexItem) {
                        let discount = {
                            id: discountItem.id,
                            nama: discountItem.nama,
                            result: discountItem.result,
                            satuan: discountItem.satuan,
                            value: discountItem.value
                        }

                        tmpDiscountData.push(discount);
                    });

                    dataForm.append('discount_id[]', JSON.stringify(tmpDiscountData));

                    let tmpModifierData = [];
                    item.modifier.forEach(function(itemModifier, indexModifier) {
                        let modifier = {
                            id: itemModifier.id,
                            nama: itemModifier.nama,
                            harga: itemModifier.harga
                        }

                        tmpModifierData.push(modifier);
                    })
                    dataForm.append('modifier_id[]', JSON.stringify(tmpModifierData));
                    dataForm.append('catatan[]', item.catatan);
                }
            });

            dataForm.append('nominal_bayar', nominalBayar);
            dataForm.append('change', Math.abs(change));
            dataForm.append('tipe_pembayaran', tipePembayaran);
            dataForm.append('total', hargaTotal);
            dataForm.append('diskonAllItems', JSON.stringify(listDiskonAllItem))
            dataForm.append('total_pajak', JSON.stringify(listPajak));
            dataForm.append('rounding', amountRounding);
            dataForm.append('tanda_rounding', tandaRounding)

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

                    if (res.status) {
                        console.log(res)
                        const modal = $('#itemModal');
                        // modal.html(res);
                        modal.modal('hide');
                        showToast(res.status, res.message);

                        var modals = $('#modals');
                        $('#change').text(formatRupiah(res.change.toString(), "Rp. "));
                        $('#metodetrx').text(res.metode);
                        // $('#btnstruk').attr('href', '/kasir/struk/' + res.id);
                        // $('#btnstruk').attr('href', 'intent://cetak-struk?id=' + res
                        //     .id);
                        // $('#btnSettingDevice').attr('href',
                        //     'intent://list-bluetooth-device');
                        // console.log("intent://cetak-struk?id=" + res.id)


                        // if (res.pelanggan) {
                        //     $('#btninvoice').data('id', res.id)
                        //     $('#btninvoice').data('nohp', res.pelanggan.nohp)
                        // } else {
                        //     $('#btninvoice').attr('href', res.waLink);
                        // }

                        modals.modal('show');
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

        });
    });
</script>
