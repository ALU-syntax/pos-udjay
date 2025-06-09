<style>
    .quantity {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }

    .quantity__minus,
    .quantity__plus {
        display: block;
        width: 65px;
        height: 50px;
        margin: 0;
        background: #dee0ee;
        text-decoration: none;
        text-align: center;
        line-height: 23px;
        border: 0;
    }

    .quantity__minus:hover,
    .quantity__plus:hover {
        background: #575b71;
        color: #fff;
    }

    .quantity__minus {
        border-radius: 0px 0 0 3px;
    }

    .quantity__plus {
        border-radius: 0 3px 3px 0;
    }

    .quantity__input {
        width: 120px;
        height: 50px;
        margin: 0;
        padding: 0;
        text-align: center;
        border-top: 2px solid #dee0ee;
        border-bottom: 2px solid #dee0ee;
        border-left: 1px solid #dee0ee;
        border-right: 2px solid #dee0ee;
        background: #fff;
        color: #8184a1;
    }

    .quantity__minus:link,
    .quantity__plus:link {
        color: #8184a1;
    }

    .quantity__minus:visited,
    .quantity__plus:visited {
        color: #fff;
    }

    .form-check-input[type="checkbox"] {
        border-radius: 50%;
        width: 3.25em;
        height: 3.25em;
    }

    .form-check-input[type="checkbox"]:checked {
        background-color: #0d6efd;
        /* Warna saat dicentang */
        border-color: #0d6efd;
        /* Warna border saat dicentang */
    }

    .form-check-input[type="checkbox"]:checked::after {
        content: "";
        position: absolute;
        display: block;
        width: 0.625em;
        height: 0.625em;
        background-color: white;
        border-radius: 50%;
        top: 0.3125em;
        left: 0.3125em;
    }
</style>

<div class="modal-dialog modal-xl" id="productModal">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="btn btn-outline-secondary btn-xl" data-bs-dismiss="modal"
                id="btnCancelRefund">Batal</button>
            <h5 class="modal-title mx-auto text-center" id="productModalLabel">
                <strong>Refund Item</strong><br>
            </h5>
            <button id="btnRefund" type="button" class="btn btn-primary btn-xl">Simpan</button>
        </div>
        <div class="modal-body">
            <div class="container">
                <div class="row">
                    <div class="container">
                        <div class="row mb-3">
                            <p>Refund Method: </p>
                            <div class="col-6"><button
                                    class="btn btn-payment-method btn-outline-primary active btn-lg w-100"
                                    data-value="cash">Cash</button></div>
                            <div class="col-6"><button class="btn btn-payment-method btn-outline-primary btn-lg w-100"
                                    data-value="transfer">Transfer</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="mb-3">
                            <label for="catatanRefund" class="form-label"><strong>Catatan</strong></label>
                            <textarea style="height: 80px;" class="form-control" id="catatanRefund" placeholder="Catatan Wajib diisi" required></textarea>
                        </div>
                    </div>
                    <div class="col-12">
                        <div style="border: 2px solid black; border-radius: 50px;" class="p-3">
                            <div class="row ">
                                <div class="col-6">Jumlah yang direfund</div>
                                <div id="subtotal-refund" class="col-6 d-flex justify-content-end pe-4">Rp. 0</div>
                            </div>
                        </div>
                    </div>
                </div>

                <br>
                <hr class="mb-0" style="border-width: initial;">
                <p class="mb-0">Produk yang direfund</p>
                <hr class="mt-0" style="border-width: initial;">

                <hr>
                <p class="text-center">dine in</p>
                <hr>
                <br>

                <div class="container" id="list-refund-item">
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    var amountRefund = 0;
    var amountPajakRefund = 0;
    var jumlahQtyItemRefundTerpilih = 0;
    var transactionData = @json($dataItem);
    console.log(transactionData);

    $('.btn-payment-method').off().on('click', function() {
        $('.btn-payment-method').removeClass('active');
        $(this).addClass('active');

    });

    $("#btnCancelRefund").off().on('click', function(e) {
        // Tutup modal
        const modal = $('#itemModal');
        modal.modal('hide');
        amountRefund = 0;
        listItemRefund = [];
    });

    $('#btnRefund').off().on('click', function(e) {
        submitLoaderByIdBtn(this.id).show();

        let dataFormRefund = new FormData();
        dataFormRefund.append('transaction_id', transactionData.id);

        let activeButtonPaymentMethod = $('.btn-payment-method.active');
        let valuePaymentMethod = activeButtonPaymentMethod.attr('data-value');
        dataFormRefund.append('payment_method', valuePaymentMethod);

        let itemRefund = [];
        listItemRefund.forEach(function(item) {
            let tmpDataItemRefund = {
                harga: item.harga,
                quantity: item.quantity,
                variant_id: item.variant_id,
                discount: item.discount_id,
                modifier: item.modifier_id,
                catatan: item.catatan
            };

            itemRefund.push(tmpDataItemRefund);
        });

        dataFormRefund.append('list_item', JSON.stringify(itemRefund));
        dataFormRefund.append('nominal_refund', amountRefund);
        dataFormRefund.append('catatan', $('#catatanRefund').val());

        $.ajax({
            url: "{{ route('kasir/refund') }}",
            method: "POST",
            data: dataFormRefund,
            contentType: false,
            processData: false,
            beforeSend: function() {
                // submitLoader().show()
            },
            success: (res) => {
                submitLoaderByIdBtn(this.id).hide();
                console.log(res);

                const modal = $('#itemModal');
                modal.modal('hide');
                detailTransactionHandle(res.transaction);

            },
            complete: function() {
                // submitLoader().hide()
                submitLoaderByIdBtn(this.id).hide();
            },
            error: function(err) {
                submitLoaderByIdBtn(this.id).hide();
                const errors = err.responseJSON?.errors

                showToast('error', err.responseJSON?.message)
            }
        });
    })

    $('#catatanRefund').off().on('input', function() {
        validateBtnPisahkan();
    });

    function generateListItem() {
        $('#list-refund-item').empty();

        jumlahQtyBelanja = 0;
        transactionData.item_transaction.forEach(function(item) {
            let initials = '';
            let namaProduct = '';
            let namaVariant = '';

            if (item.product_id) {
                let text = item.variant.name;
                // Pisahkan kata dan ambil maksimal 2 kata pertama
                let words = text.split(' ');
                initials = words.slice(0, 2).map(word => word[0]).join('');

                namaProduct = item.product.name == item.variant.name ? item.variant.name : item.product.name +
                    " - " + item.variant.name;
                namaVariant = item.variant.name;
            } else {
                initials = 'C';
                namaProduct = 'custom';
                namaVariant = 'custom';
            }

            let modifierItem = JSON.parse(item.modifier_id);
            let diskonItem = JSON.parse(item.discount_id);
            let tmpIdItem = generateRandomID();

            item.tmpId = tmpIdItem;

            jumlahQtyBelanja += parseInt(item.quantity);

            let html = `<div class="row">
                        <div class="col-2 icon-box" data-text="${namaVariant}">${initials}</div>
                        <div class="col-4 pt-2">
                            <span>${namaProduct}</span>
                            <br>
                            <span>${formatRupiah(item.harga.toString(), "Rp. ")}</span>
                            ${modifierItem.map(function(modifier) {
                                return `<br>
                                        <span style="color: gray">${modifier.nama} - ${modifier.harga}</span>`;})
                            .join('')}

                            ${diskonItem.map(function(diskon) {
                                return `<br>
                                        <span style="color: red">${diskon.nama} - ${diskon.value}%</span>`;})
                            .join('')}

                        </div>
                        <div class="col-6 d-flex justify-content-end">
                            <div class="quantity ms-auto" data-tmpid="${tmpIdItem}" data-harga="${item.harga}" data-maxqty="${item.quantity}">
                                <button class="quantity__minus d-flex justify-content-center align-items-center" data-fungsi="decrement" onclick="handlerIncrementDecrementRefund(this)" disabled><span>-</span></button>
                                <input name="quantity" type="text" class="quantity__input" value="1" min="1" readonly>
                                <button class="quantity__plus d-flex justify-content-center align-items-center" data-fungsi="increment" onclick="handlerIncrementDecrementRefund(this)" disabled><span>+</span></button>
                            </div>

                            <div class="form-check d-flex justify-content-center align-items-center ms-5">
                                <input class="form-check-input" type="checkbox" onchange="handlerCheckItemRefund(this, 'list-item')" data-tmpid="${tmpIdItem}" id="flexCheckDefault">
                            </div>
                        </div>

                    </div>`
            $('#list-refund-item').append(html);
        });

    }

    function handlerIncrementDecrementRefund(widget) {
        let buttonElement = $(widget);
        let fungsiButton = buttonElement.attr('data-fungsi');

        // Cari element input quantity__input di dalam parent .quantity
        let containerItem = buttonElement.closest('.quantity');
        let maxQty = containerItem.attr("data-maxqty");
        let dataIdTmp = containerItem.attr('data-tmpid');

        let inputElement = containerItem.find('input.quantity__input');
        let hargaItem = containerItem.attr('data-harga');

        // Ambil value dan konversi ke number (integer)
        let valueInputExist = parseInt(inputElement.val());

        let dataSplitBill = listItemRefund.find(item => item.tmpId == dataIdTmp);

        if (fungsiButton === "increment") {
            if (valueInputExist < maxQty) {
                valueInputExist += 1;
            } else {

            }
        } else {
            if (valueInputExist > 1) {
                valueInputExist -= 1;
            }
        }

        dataSplitBill.quantity = valueInputExist;

        inputElement.val(valueInputExist);

        validateButtonIncDec(dataIdTmp, maxQty, true);
        subTotalRefund();
    }

    function handlerCheckItemRefund(widget) {
        let checkBoxElement = $(widget);
        let dataTmpId = checkBoxElement.attr('data-tmpid');


        // Cari div.quantity yang punya data-tmpid sama
        let quantityDiv = $('.quantity[data-tmpid="' + dataTmpId + '"]');
        let inputElement = quantityDiv.find('input.quantity__input');

        let dataItem = transactionData.item_transaction.find(item => item.tmpId == dataTmpId);

        let itemRefund = JSON.parse(JSON.stringify(dataItem));

        itemRefund.quantity = inputElement.val()

        let maxQty = quantityDiv.attr('data-maxqty');

        let checked = checkBoxElement.is(':checked');
        if (checked) {
            listItemRefund.push(itemRefund);
        } else {
            let index = listItemRefund.findIndex(item => item.tmpId === dataTmpId);
            if (index !== -1) {
                listItemRefund.splice(index, 1);
            }
        }

        validateButtonIncDec(dataTmpId, maxQty, checked);
        subTotalRefund();

        validateBtnPisahkan();
    }

    function subTotalRefund() {
        let amountPajak = 0;
        amountRefund = 0;
        let tmpDataPajak = [];
        let tmpTotalPajak = [];
        let tmpHargaAkhir = 0;

        listItemRefund.forEach(function(item) {
            let qty = parseInt(item.quantity);
            jumlahQtyItemRefundTerpilih += qty;

            let price = parseInt(item.harga);
            let hargaResultItem = qty * price;

            amountRefund += hargaResultItem;
            if (item.product) {
                if (!item.product.excludeTax) {
                    tmpHargaAkhir += hargaResultItem;
                }
            } else {
                tmpHargaAkhir += hargaResultItem;
            }

            let itemModifier = JSON.parse(item.modifier_id);
            itemModifier.forEach(function(modifier) {
                amountRefund += modifier.harga * qty;
                hargaResultItem += modifier.harga * qty;
                if (item.product) {
                    if (!item.product.excludeTax) {
                        tmpHargaAkhir += modifier.harga * qty;
                    }
                }
            });

            let itemDiskon = JSON.parse(item.discount_id);
            itemDiskon.forEach(function(diskon) {
                amountRefund -= (diskon.value * hargaResultItem) / 100;
                if (item.product) {
                    if (!item.product.excludeTax) {
                        tmpHargaAkhir -= (diskon.value * hargaResultItem) / 100;
                    }
                }
            })

        });

        let dataPajakTransaksi = JSON.parse(transactionData.total_pajak);
        dataPajakTransaksi.forEach(function(item) {
            let satuan = item.satuan; // Cek karakter terakhir (misalnya % atau lainnya)
            let amount = parseFloat(item.amount); // Ambil angka sebelum satuan

            let pajakValue = 0;

            // Hitung pajak berdasarkan satuan
            if (satuan === "%") {
                pajakValue = (tmpHargaAkhir * amount) / 100; // Hitung jika persentase
            } else {
                pajakValue = amount; // Jika satuan tetap (angka biasa)
            }

            let resultPajak = Math.round(pajakValue);

            let dataPajak = {
                id: item.id,
                name: item.name,
                amount: amount,
                satuan: satuan,
                total: resultPajak
            }

            tmpDataPajak.push(dataPajak);
            tmpTotalPajak.push(resultPajak);

            amountPajak += resultPajak;

        });


        let totalPajakRefund = tmpTotalPajak.reduce(function(acc, curr) {
            return acc + curr;
        }, 0);

        amountPajakRefund = totalPajakRefund;

        listPajakRefund = tmpDataPajak;

        amountRefund += amountPajak;

        $('#subtotal-refund').text(formatRupiah(amountRefund.toString(), "Rp. "));
    }

    function validateButtonIncDec(dataTmpId, maxValue, checked) {

        let quantityDiv = $('.quantity[data-tmpid="' + dataTmpId + '"]');

        let btnQuantityPlus = quantityDiv.find('.quantity__plus');
        let btnQuantityMinus = quantityDiv.find('.quantity__minus');
        let inputElement = quantityDiv.find('input.quantity__input');

        let valInputElement = inputElement.val();

        if (checked) {
            inputElement.css('border-color', '#0d6efd');

            if (valInputElement == maxValue) {
                btnQuantityPlus.css('background-color', '#dee0ee').css('color', '#8184a1').attr('disabled', true);
            } else {
                btnQuantityPlus.css('background-color', '#0d6efd').css('color', 'white').attr('disabled', false);
            }

            if (valInputElement == 1) {
                btnQuantityMinus.css('background-color', '#dee0ee').css('color', '#8184a1').attr('disabled', true);
            } else {
                btnQuantityMinus.css('background-color', '#0d6efd').css('color', 'white').attr('disabled', false);
            }
        } else {
            inputElement.css('border-color', '#dee0ee');
            btnQuantityPlus.css('background-color', '#dee0ee').css('color', '#8184a1').attr('disabled', true);
            btnQuantityMinus.css('background-color', '#dee0ee').css('color', '#8184a1').attr('disabled', true);
        }

    }

    function validateBtnPisahkan() {
        if (listItemRefund.length && $('#catatanRefund').val().trim() === '') {
            console.log('masuk');
            $('#btnRefund').attr('disabled', true);
        } else {
            $('#btnRefund').attr('disabled', false);
        }
    }

    generateListItem();
    $('#btnRefund').attr('disabled', true);
</script>
