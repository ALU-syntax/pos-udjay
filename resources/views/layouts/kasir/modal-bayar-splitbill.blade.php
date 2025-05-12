<style>
    .btn-choice-split-bill {

        height: 125px;
    }
</style>

<div class="modal-dialog modal-dialog-centered modal-xl" id="choosePaymentSplitBill">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="btn btn-outline-secondary btn-lg" data-bs-dismiss="modal"
                id="cancelPaymentSplitBill">Batal</button>
            <h5 class="modal-title mx-auto text-center" id="productModalLabel">
                <strong id="totalHargaSplitBill">Rp 80.000</strong><br>
                <span>Split Bill</span>
            </h5>
            <button id="paySplitBill" type="button" class="btn btn-primary btn-lg" disabled>Simpan</button>
        </div>
        <div class="modal-body">
            <!-- Payment Options -->

            @if (count($listPayment))
                @foreach ($listPayment as $categoryPayment)
                    <div class="row">
                        <div class="col-3">
                            <h6 class="d-flex text-center">{{ $categoryPayment->name }}</h6>
                        </div>
                        <div class="col-9" @if ($categoryPayment->name == 'Cash') id="cash-row" @endif>
                            <div class="row">
                                @if ($categoryPayment->name == 'Cash')
                                    <div class="col-4 mt-2">
                                        <button type="button"
                                            class="btn w-100 btn-lg btn-choice-split-bill btn-cash-list-split-bill btn-outline-primary"
                                            data-kategori-payment-id="{{ $categoryPayment->id }}"
                                            data-kategori-payment-name="{{ $categoryPayment->name }}"
                                            data-value="50000">Rp. 50.000</button>
                                    </div>
                                    <div class="col-4 mt-2">
                                        <button type="button"
                                            class="btn w-100 btn-lg btn-choice-split-bill btn-cash-list-split-bill btn-outline-primary"
                                            data-kategori-payment-id="{{ $categoryPayment->id }}"
                                            data-kategori-payment-name="{{ $categoryPayment->name }}"
                                            data-value="100000">Rp. 100.000</button>
                                    </div>
                                    <div class="col-4 mt-2">
                                        <button type="button"
                                            class="btn w-100 btn-lg btn-choice-split-bill btn-cash-list-split-bill btn-outline-primary"
                                            data-kategori-payment-id="{{ $categoryPayment->id }}"
                                            data-kategori-payment-name="{{ $categoryPayment->name }}"
                                            data-value="200000">Rp. 200.000</button>
                                    </div>
                                @else
                                    @foreach ($categoryPayment->payment as $payment)
                                        <div class="col-4 mt-2">
                                            <button type="button"
                                                class="btn w-100 btn-lg btn-choice-split-bill btn-outline-primary"
                                                data-kategori-payment-id="{{ $categoryPayment->id }}"
                                                data-kategori-payment-name="{{ $categoryPayment->name }}"
                                                data-payment-id="{{ $payment->id }}"
                                                data-value="{{ $payment->name }}">{{ $payment->name }}</button>
                                        </div>
                                    @endforeach
                                @endif
                            </div>

                            @if ($categoryPayment->name == 'Cash')
                                <input style="height: 70px; font-size:20px;" type="numeric" id="inputMoneySplitBill"
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

        </div>
    </div>
</div>

<script>
    var edcType = '';
    var cashAmoun = 0;

    var dataListPayment = @json($listPayment);
    var dataCash = dataListPayment.find(item => item.name == "Cash");
    var idCash = dataCash.id;
    var potonganPoint = 0;
    var pointCustomer = 0;

    var moneyInputSplitBill = document.getElementById("inputMoneySplitBill");
    moneyInputSplitBill.addEventListener("keyup", function(e) {
        let totalHarga = amountSplitBill;

        let convertHargaToInt = parseInt(this.value.replace('Rp. ', '').replace(/\./g, ''));
        console.log(totalHarga);
        console.log(convertHargaToInt);

        $('.btn-choice-split-bill').removeClass('active');

        if (this.value == '' || convertHargaToInt < totalHarga || this.value == 'Rp. ' || isNaN(
                convertHargaToInt)) {
            $("#paySplitBill").attr('disabled', true);
        } else {
            $("#paySplitBill").removeAttr('disabled');
        }

        this.value = formatRupiah(this.value, "Rp. ");
    });

    function updateHargaText() {
        let total = ambilHargaTotal();
        $('#totalHargaSplitBill').text(formatRupiah(total.toString(), "Rp. "));
    }

    function ambilHargaTotal() {
        let total = amountSplitBill;

        let rounding = @json($rounding);

        // if (rounding) {
        //     let dataRounded = rounding.rounded;
        //     if (dataRounded == "true") {
        //         let dataRoundBenchmark = parseInt(dataRounding.rounded_benchmark);
        //         let roundedType = parseInt(dataRounding.rounded_type);

        //         // Ambil bagian belakang dan depan angka
        //         let angkaBelakang = total % roundedType; // Sisa pembagian (angka belakang)
        //         let angkaDepan = Math.floor(total / roundedType); // Angka depan

        //         let hasilRounded = 0;
        //         let rounded = '';
        //     }
        // }

        return total;
    }

    function kurangiQuantity(listBill, listItemSplitBill) {
        // Buat salinan listBill agar tidak mengubah data asli langsung
        let updatedListBill = listBill.map(item => ({
            ...item
        }));

        listItemSplitBill.forEach(splitItem => {
            // Cari index item di updatedListBill yang punya tmpId sama
            const index = updatedListBill.findIndex(billItem => billItem.tmpId === splitItem.tmpId);

            if (index !== -1) {
                // Ambil quantity asli dan quantity yang akan dikurangi
                let originalQty = parseInt(updatedListBill[index].quantity);
                let splitQty = parseInt(splitItem.quantity);

                // Kurangi quantity, pastikan tidak negatif
                let newQty = originalQty - splitQty;
                if (newQty > 0) {
                    updatedListBill[index].quantity = newQty.toString();
                    // Update resultTotal sesuai quantity baru
                    updatedListBill[index].resultTotal = updatedListBill[index].harga * newQty;
                } else {
                    // Jika quantity habis, hapus item dari list
                    updatedListBill.splice(index, 1);
                }
            }
        });

        return updatedListBill;
    }

    function generateBtnCashSplitBill(){
        var hargaAkhir = ambilHargaTotal();

        // Hitung nilai tombol
        var firstButtonValue = hargaAkhir;
        var secondButtonValue = Math.ceil((hargaAkhir + 1) / 50000) * 50000;
        var thirdButtonValue = Math.ceil((secondButtonValue + 1) / 100000) * 100000;


        // Update tombol berdasarkan urutan (asumsi urutan tombol sesuai HTML)
        $('.btn-cash-list-split-bill').each(function(index) {
            if (index === 0) {
                $(this).data('value', firstButtonValue);
                $(this).text(formatRupiah(firstButtonValue.toString(), "Rp. "));
            } else if (index === 1) {
                $(this).data('value', secondButtonValue);
                $(this).text(formatRupiah(secondButtonValue.toString(), "Rp. "));
            } else if (index === 2) {
                $(this).data('value', thirdButtonValue);
                $(this).text(formatRupiah(thirdButtonValue.toString(), "Rp. "));
            }
        });
    }


    $(document).ready(function() {
        updateHargaText();
        generateBtnCashSplitBill();

        $('.btn-choice-split-bill').off().on('click', function() {
            $('.btn-choice-split-bill').removeClass('active');
            $(this).addClass('active');

            $("#paySplitBill").attr('disabled', true);
            let value = $(this).data('value');
            console.log(value)

            if (typeof value == "number") {
                let totalHarga = amountSplitBill;

                if (value < totalHarga) {
                    $("#paySplitBill").attr('disabled', true);
                } else {
                    $("#paySplitBill").removeAttr('disabled');
                }
            } else {
                $("#paySplitBill").removeAttr('disabled');
            }
        });

        $('#cancelPaymentSplitBill').on('click', function() {
            // Tutup modal
            const modal = $('#itemModal');
            modal.modal('hide');
            amountSplitBill = 0;
            listItemSplitBill = [];
        });

        $('#paySplitBill').on('click', function() {
            $(this).prop('disabled', true);

            let selectButton = $('button.active').text();
            let selectButtonTrim = selectButton.trim();
            let selectButtonAngka = parseInt(selectButtonTrim.replace(/[^\d]/g, ""));

            let checkTypeButton = $('button.active');
            let categoryPaymentId = checkTypeButton.data('kategori-payment-id');
            let categoryPaymentName = checkTypeButton.data('kategori-payment-name');
            let paymentValue = checkTypeButton.data('value');
            let paymentId = checkTypeButton.data('payment-id');

            // let checkTypeButton = $('.btn-choice.active');

            let valueCatatan = $('#catatanTransaksi').val();

            let cashInput = $("#inputMoneySplitBill").val();
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
                category_payment_id = idCash;
                nama_tipe_pembayaran = "Cash"
                nominalBayar = cashInputAngka;
                change = hargaTotal - cashInputAngka;
            }

            var dataForm = new FormData();
            listItemSplitBill.forEach(function(item, index) {
                if (item.quantity > 1) {
                    for (let x = 0; x < item.quantity; x++) {
                        dataForm.append('idProduct[]', item.idProduct);
                        dataForm.append('idVariant[]', item.idVariant);
                        dataForm.append('harga[]', item.harga);
                        dataForm.append('tmpId[]', item.tmpId);
                        let tmpDiscountData = [];
                        item.diskon.forEach(function(discountItem, indexItem) {
                            let discount = {
                                id: discountItem.id,
                                nama: discountItem.nama,
                                result: parseInt(discountItem.result) / item
                                    .quantity,
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
                        item.promo.forEach(function(itemPromo, indexPromo) {
                            let promo = {
                                id: itemPromo.id,
                                nama: itemPromo.name,
                                purchaseRequirement: itemPromo.purchase_requirement,
                                type: itemPromo.type
                            }

                            tmpPromoData.push(promo);
                        });

                        dataForm.append('promo_id[]', JSON.stringify(tmpPromoData));
                        dataForm.append('sales_type[]', item.salesType);

                        let resultCatatan = item.catatan == '' ? '' : item.catatan;

                        let checkProductCustom = item.idProduct ? resultCatatan : 'custom';

                        dataForm.append('modifier_id[]', JSON.stringify(tmpModifierData));
                        dataForm.append('catatan[]', checkProductCustom);
                        dataForm.append('reward[]', false);
                    }
                } else {
                    dataForm.append('idProduct[]', item.idProduct);
                    dataForm.append('idVariant[]', item.idVariant);
                    dataForm.append('harga[]', item.harga);
                    dataForm.append('tmpId[]', item.tmpId);
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
                    item.promo.forEach(function(itemPromo, indexPromo) {
                        let promo = {
                            id: itemPromo.id,
                            nama: itemPromo.name,
                            purchaseRequirement: itemPromo.purchase_requirement,
                            type: itemPromo.type
                        }

                        tmpPromoData.push(promo);
                    });

                    dataForm.append('sales_type[]', item.salesType);

                    let resultCatatan = item.catatan == '' ? '' : item.catatan;

                    let checkProductCustom = item.idProduct ? resultCatatan : 'custom';

                    dataForm.append('promo_id[]', JSON.stringify(tmpPromoData));
                    dataForm.append('modifier_id[]', JSON.stringify(tmpModifierData));
                    dataForm.append('catatan[]', checkProductCustom);

                    dataForm.append('reward[]', false);
                }
            });

            dataForm.append('nominal_bayar', nominalBayar);
            dataForm.append('change', Math.abs(change));
            dataForm.append('tipe_pembayaran', tipePembayaran);
            dataForm.append('category_payment_id', category_payment_id);
            dataForm.append('nama_tipe_pembayaran', nama_tipe_pembayaran);
            dataForm.append('total', hargaTotal);
            dataForm.append('diskonAllItems', JSON.stringify(listDiskonAllItem))
            dataForm.append('total_pajak', JSON.stringify(listPajakSplitBill));
            dataForm.append('rounding', amountRounding);
            dataForm.append('tanda_rounding', tandaRounding);
            dataForm.append('catatan_transaksi', valueCatatan);
            dataForm.append('patty_cash_id', dataPattyCash[0].id);
            dataForm.append('bill_id', billId);
            dataForm.append('potongan_point', potonganPoint);
            dataForm.append('split_bill', true);

            console.log(dataForm);
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
                    console.log(listItem);
                    if (res.status) {
                        console.log(res)
                        const modal = $('#itemModal');
                        // modal.html(res);
                        modal.modal('hide');
                        showToast(res.status, res.message);

                        var modals = $('#modalSuccessSplitBill');
                        $('#changeSplitBill').text(formatRupiah(res.change.toString(),
                            "Rp. "));
                        $('#metodetrxSplitBill').text(res.metode);
                        // $('#btnstruk').attr('href', '/kasir/struk/' + res.id);
                        $('#btnstrukSplitBill').attr('href', 'intent://cetak-struk?id=' +
                            res
                            .id);
                        $('#btnSettingDeviceSplitBill').attr('href',
                            'intent://list-bluetooth-device');

                        $('#btnRedirectSplitBill').off().on('click', function() {
                            modals.modal('hide');
                            amountSplitBill = 0;
                            listItemSplitBill = [];
                        })

                        let hasil = kurangiQuantity(listItem, listItemSplitBill);
                        listItem = hasil;
                        listItemSplitBill = [];
                        listPajakSplitBill = [];
                        amountSplitBill = 0;

                        syncItemCart();

                        if (window.Android) {
                            // Panggil metode JavaScript Interface dengan ID transaksi
                            window.Android.handlePaymentSuccess(res.id);
                        }

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
