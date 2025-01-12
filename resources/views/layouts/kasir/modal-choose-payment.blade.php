<style>
    .btn-choice {

        height: 125px;
    }
</style>

<div class="modal-dialog modal-dialog-centered modal-xl" id="choosePayment">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="btn btn-outline-secondary btn-lg" data-bs-dismiss="modal">Batal</button>
            <h5 class="modal-title mx-auto text-center" id="productModalLabel">
                <strong id="totalHarga">Rp 80.000</strong><br>
            </h5>
            <button id="pay" type="button" class="btn btn-primary btn-lg" disabled>Simpan</button>
        </div>
        <div class="modal-body">
            <!-- Payment Options -->

            @if (count($listPayment))
                @foreach ($listPayment as $categoryPayment)
                    <div class="row">
                        <div class="col-3">
                            <h6 class="d-flex text-center">{{ $categoryPayment->name }}</h6>
                        </div>
                        <div class="col-9">
                            <div class="row">
                                @if ($categoryPayment->name == 'Cash')
                                    <div class="col-4 mt-2">
                                        <button type="button" class="btn w-100 btn-lg btn-choice btn-outline-primary"
                                            data-kategori-payment-id="{{ $categoryPayment->id }}"
                                            data-kategori-payment-name="{{ $categoryPayment->name }}"
                                            data-value="50000">Rp. 50.000</button>
                                    </div>
                                    <div class="col-4 mt-2">
                                        <button type="button" class="btn w-100 btn-lg btn-choice btn-outline-primary"
                                            data-kategori-payment-id="{{ $categoryPayment->id }}"
                                            data-kategori-payment-name="{{ $categoryPayment->name }}"
                                            data-value="100000">Rp. 100.000</button>
                                    </div>
                                    <div class="col-4 mt-2">
                                        <button type="button" class="btn w-100 btn-lg btn-choice btn-outline-primary"
                                            data-kategori-payment-id="{{ $categoryPayment->id }}"
                                            data-kategori-payment-name="{{ $categoryPayment->name }}"
                                            data-value="200000">Rp. 200.000</button>
                                    </div>
                                @else
                                    @foreach ($categoryPayment->payment as $payment)
                                        <div class="col-4 mt-2">
                                            <button type="button"
                                                class="btn w-100 btn-lg btn-choice btn-outline-primary"
                                                data-kategori-payment-id="{{ $categoryPayment->id }}"
                                                data-kategori-payment-name="{{ $categoryPayment->name }}"
                                                data-payment-id="{{ $payment->id }}"
                                                data-value="{{ $payment->name }}">{{ $payment->name }}</button>
                                        </div>
                                    @endforeach
                                @endif
                            </div>

                            @if ($categoryPayment->name == 'Cash')
                                <input style="height: 70px; font-size:20px;" type="numeric" id="inputMoney"
                                    class="form-control input-xl mt-2" placeholder="Cash Amount">
                            @endif
                        </div>
                    </div>

                    <hr>
                @endforeach
            @else
                    <p>Tambahkan Tipe Pembayaran Terlebih Dahulu</p>
            @endif


            <div class="row">
                <div class="col-12">
                    <div class="mb-4 mt-2">
                        <label for="note" class="form-label"><strong>Catatan</strong></label>
                        <textarea style="height: 150px;" class="form-control" id="catatanTransaksi" rows="3"></textarea>
                    </div>
                </div>
            </div>


            {{-- <div class="row">
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
            </div> --}}

        </div>
    </div>
</div>

<script>
    var edcType = '';
    var cashAmoun = 0;

    var dataListPayment = @json($listPayment);
    var dataCash = dataListPayment.find(item => item.name == "Cash");
    var idCash = dataCash.id;
    
    var moneyInput = document.getElementById("inputMoney");
    moneyInput.addEventListener("keyup", function(e) {

        let textHarga = document.getElementById("total").textContent;
        let harga = textHarga.trim();
        let totalHarga = parseInt(harga.replace(/[^\d]/g, ""));

        let convertHargaToInt = parseInt(this.value.replace('Rp. ', '').replace(/\./g, ''));
        console.log(this.value);
        console.log(convertHargaToInt);
        console.log(totalHarga)
        $('.btn-choice').removeClass('active');

        if (this.value == '' || convertHargaToInt < totalHarga || this.value == 'Rp. ' || isNaN(
                convertHargaToInt)) {
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

        $('.btn-choice').off().on('click', function() {
            $('.btn-choice').removeClass('active');
            $(this).addClass('active');

            $("#pay").attr('disabled', true);
            let value = $(this).data('value');
            console.log(value)

            if (typeof value == "number") {
                let textHarga = document.getElementById("total").textContent;
                let harga = textHarga.trim();
                let totalHarga = parseInt(harga.replace(/[^\d]/g, ""));

                if (value < totalHarga) {
                    $("#pay").attr('disabled', true);
                } else {
                    $("#pay").removeAttr('disabled');
                }
            } else {
                $("#pay").removeAttr('disabled');
            }
        });


        $('#pay').on('click', function() {
            let selectButton = $('button.active').text();
            let selectButtonTrim = selectButton.trim();
            let selectButtonAngka = parseInt(selectButtonTrim.replace(/[^\d]/g, ""));

            let checkTypeButton = $('button.active');
            let categoryPaymentId = checkTypeButton.data('kategori-payment-id');
            let categoryPaymentName = checkTypeButton.data('kategori-payment-name');
            let paymentValue = checkTypeButton.data('value');
            let paymentId = checkTypeButton.data('payment-id');

            // let checkTypeButton = $('.btn-choice.active');
            console.log(checkTypeButton);
            console.log(categoryPaymentId);
            console.log(categoryPaymentName);
            console.log(paymentValue);
            console.log(paymentId);

            let valueCatatan = $('#catatanTransaksi').val();

            let cashInput = $("#inputMoney").val();
            let trimCashInput = cashInput.trim();
            let cashInputAngka = parseInt(trimCashInput.replace(/[^\d]/g, ""));

            let hargaTotal = ambilHargaTotal();
            let category_payment_id = '';
            let nama_tipe_pembayaran = '';
            let tipePembayaran = '';
            let nominalBayar = 0;
            let change = 0;

            if (checkTypeButton.length > 0) {
                if (categoryPaymentName == "Cash") {
                    category_payment_id = categoryPaymentId;
                    nama_tipe_pembayaran = "Cash";
                    tipePembayaran = null;
                    nominalBayar = paymentValue;
                    change = hargaTotal - paymentValue;
                } else {
                    category_payment_id = categoryPaymentId;
                    nama_tipe_pembayaran = paymentValue;
                    tipePembayaran = paymentId;
                    nominalBayar = hargaTotal;
                }
            } else {
                tipePembayaran = null;
                category_payment_id = idCash;;
                nama_tipe_pembayaran = "Cash"
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

                        let tmpPromoData = [];
                        item.promo.forEach(function(itemPromo, indexPromo){
                            let promo = {
                                id: itemPromo.id,
                                nama: itemPromo.name,
                                purchaseRequirement: itemPromo.purchase_requirement,
                                type: itemPromo.type
                            }

                            tmpPromoData.push(promo);
                        });

                        dataForm.append('promo_id[]', JSON.stringify(tmpPromoData));
                        
                        let resultCatatan = item.catatan == '' ? '' : item.catatan;
                        dataForm.append('modifier_id[]', JSON.stringify(tmpModifierData));
                        dataForm.append('catatan[]', resultCatatan);
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

                    let tmpPromoData = [];
                    item.promo.forEach(function(itemPromo, indexPromo){
                        let promo = {
                            id: itemPromo.id,
                            nama: itemPromo.name,
                            purchaseRequirement: itemPromo.purchase_requirement,
                            type: itemPromo.type
                        }

                        tmpPromoData.push(promo);
                    });

                    let resultCatatan = item.catatan == '' ? '' : item.catatan;
                    dataForm.append('promo_id[]', JSON.stringify(tmpPromoData));
                    dataForm.append('modifier_id[]', JSON.stringify(tmpModifierData));
                    dataForm.append('catatan[]', resultCatatan);
                }

                dataForm.append('reward[]', false);
            });

            listItemPromo.forEach(function(item, index){
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

                        let tmpPromoData = [];
                        item.promo.forEach(function(itemPromo, indexPromo){
                            let promo = {
                                id: itemPromo.id,
                                nama: itemPromo.name,
                                purchaseRequirement: itemPromo.purchase_requirement,
                                type: itemPromo.type
                            }

                            tmpPromoData.push(promo);
                        });

                        let resultCatatan = item.catatan == '' ? '' : item.catatan;
                        dataForm.append('promo_id[]', JSON.stringify(tmpPromoData));
                        dataForm.append('modifier_id[]', JSON.stringify(tmpModifierData));
                        dataForm.append('catatan[]', resultCatatan);
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

                    let tmpPromoData = [];
                        item.promo.forEach(function(itemPromo, indexPromo){
                            let promo = {
                                id: itemPromo.id,
                                nama: itemPromo.name,
                                purchaseRequirement: itemPromo.purchase_requirement,
                                type: itemPromo.type
                            }

                            tmpPromoData.push(promo);
                        });

                    let resultCatatan = item.catatan == '' ? '' : item.catatan;
                    dataForm.append('promo_id[]', JSON.stringify(tmpPromoData));
                    dataForm.append('modifier_id[]', JSON.stringify(tmpModifierData));
                    dataForm.append('catatan[]', resultCatatan);
                }
                dataForm.append('reward[]', false);
            });

            listRewardItem.forEach(function(item, index){
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

                        let tmpPromoData = [];
                        item.promo.forEach(function(itemPromo, indexPromo){
                            let promo = {
                                id: itemPromo.id,
                                nama: itemPromo.name,
                                purchaseRequirement: itemPromo.purchase_requirement,
                                type: itemPromo.type
                            }

                            tmpPromoData.push(promo);
                        });

                        let resultCatatan = item.catatan == '' ? '' : item.catatan;
                        dataForm.append('promo_id[]', JSON.stringify(tmpPromoData));
                        dataForm.append('modifier_id[]', JSON.stringify(tmpModifierData));
                        dataForm.append('catatan[]', resultCatatan);
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

                    let tmpPromoData = [];
                        item.promo.forEach(function(itemPromo, indexPromo){
                            let promo = {
                                id: itemPromo.id,
                                nama: itemPromo.name,
                                purchaseRequirement: itemPromo.purchase_requirement,
                                type: itemPromo.type
                            }

                            tmpPromoData.push(promo);
                        });

                    let resultCatatan = item.catatan == '' ? '' : item.catatan;
                    dataForm.append('promo_id[]', JSON.stringify(tmpPromoData));
                    dataForm.append('modifier_id[]', JSON.stringify(tmpModifierData));
                    dataForm.append('catatan[]', resultCatatan);
                }
                dataForm.append('reward[]', true);
            });

            let idCustomer = (idPelanggan == '') ? null : idPelanggan;
            console.log(idPelanggan);

            dataForm.append('nominal_bayar', nominalBayar);
            dataForm.append('change', Math.abs(change));
            dataForm.append('tipe_pembayaran', tipePembayaran);
            dataForm.append('category_payment_id', category_payment_id);
            dataForm.append('nama_tipe_pembayaran', nama_tipe_pembayaran);
            dataForm.append('total', hargaTotal);
            dataForm.append('diskonAllItems', JSON.stringify(listDiskonAllItem))
            dataForm.append('total_pajak', JSON.stringify(listPajak));
            dataForm.append('rounding', amountRounding);
            dataForm.append('tanda_rounding', tandaRounding);
            dataForm.append('customer_id', idCustomer);
            dataForm.append('catatan_transaksi', valueCatatan);

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
