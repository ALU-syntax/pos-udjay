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
                id="btnCancelSplitBill">Batal</button>
            <h5 class="modal-title mx-auto text-center" id="productModalLabel">
                <strong>Pisah Bill</strong><br>
            </h5>
            <button id="btnSplitBill" type="button" class="btn btn-primary btn-xl">Pisahkan</button>
        </div>
        <div class="modal-body">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div style="border: 2px solid black; border-radius: 50px;" class="p-3">
                            <div class="row ">
                                <div class="col-6">Jumlah yang dipisahkan</div>
                                <div id="subtotal-split-bill" class="col-6 d-flex justify-content-end pe-4">Rp. 0</div>
                            </div>
                        </div>
                    </div>
                </div>

                <br>
                <hr class="mb-0" style="border-width: initial;">
                <p class="mb-0">Produk yang dipisahkan</p>
                <hr class="mt-0" style="border-width: initial;">

                <hr>
                <p class="text-center">dine in</p>
                <hr>
                <br>

                <div class="container" id="list-split-item">
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    var amountSplitBill = 0;
    var amountPajakSplitBill = 0;
    var jumlahQtyItemSplit = 0;
    var jumlahQtyItemSplitTerpilih = 0;

    $("#btnCancelSplitBill").off().on('click', function(e) {
        // Tutup modal
        const modal = $('#itemModal');
        modal.modal('hide');
        amountSplitBill = 0;
        listItemSplitBill = [];
    });

    $('#btnSplitBill').off().on('click', function(e) {
        handleAjax("{{ route('kasir/choosePaymentSplitBill') }}").excute();
    })

    function generateListItem() {
        $('#list-split-item').empty();
        jumlahQtyBelanja = 0;
        listItem.forEach(function(item) {
            console.log(item)
            jumlahQtyBelanja += parseInt(item.quantity);
            let namaProduct = item.namaProduct == item.namaVariant ? item.namaVariant : item.namaProduct +
                " - " + item.namaVariant;
            let html = `<div class="row">
                        <div class="col-2 icon-box" data-text="${item.namaVariant}">KS</div>
                        <div class="col-4 pt-2">
                            <span>${namaProduct}</span>
                            <br>
                            <span>${formatRupiah(item.harga.toString(), "Rp. ")}</span>
                            ${item.modifier.map(function(modifier) {
                                return `<br>
                                        <span style="color: gray">${modifier.nama} - ${modifier.harga}</span>`;})
                            .join('')}

                            ${item.diskon.map(function(diskon) {
                                return `<br>
                                        <span style="color: red">${diskon.nama} - ${diskon.value}%</span>`;})
                            .join('')}

                        </div>
                        <div class="col-6 d-flex justify-content-end">
                            <div class="quantity ms-auto" data-tmpid="${item.tmpId}" data-harga="${item.harga}" data-maxqty="${item.quantity}">
                                <button class="quantity__minus d-flex justify-content-center align-items-center" data-fungsi="decrement" onclick="handlerIncrementDecrement(this)" disabled><span>-</span></button>
                                <input name="quantity" type="text" class="quantity__input" value="1" min="1" readonly>
                                <button class="quantity__plus d-flex justify-content-center align-items-center" data-fungsi="increment" onclick="handlerIncrementDecrement(this)" disabled><span>+</span></button>
                            </div>

                            <div class="form-check d-flex justify-content-center align-items-center ms-5">
                                <input class="form-check-input" type="checkbox" onchange="handlerCheckItem(this, 'list-item')" data-tmpid="${item.tmpId}" id="flexCheckDefault">
                            </div>
                        </div>

                    </div>`
            $('#list-split-item').append(html);
        });

    }

    function handlerIncrementDecrement(widget) {
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

        let dataSplitBill = listItemSplitBill.find(item => item.tmpId == dataIdTmp);

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
        subTotalSplitBill();
    }

    function handlerCheckItem(widget) {
        let checkBoxElement = $(widget);
        let dataTmpId = checkBoxElement.attr('data-tmpid');

        // Cari div.quantity yang punya data-tmpid sama
        let quantityDiv = $('.quantity[data-tmpid="' + dataTmpId + '"]');
        let inputElement = quantityDiv.find('input.quantity__input');

        let dataItem = listItem.find(item => item.tmpId == dataTmpId);

        let itemSplit = JSON.parse(JSON.stringify(dataItem));
        itemSplit.quantity = inputElement.val()

        let maxQty = quantityDiv.attr('data-maxqty');

        let checked = checkBoxElement.is(':checked');
        if (checked) {
            listItemSplitBill.push(itemSplit);
        } else {
            let index = listItemSplitBill.findIndex(item => item.tmpId === dataTmpId);
            if (index !== -1) {
                listItemSplitBill.splice(index, 1);
            }
        }

        validateButtonIncDec(dataTmpId, maxQty, checked);
        subTotalSplitBill();

        validateBtnPisahkan();
    }

    function subTotalSplitBill() {
        let amountPajak = 0;
        amountSplitBill = 0;
        let tmpDataPajak = [];
        let tmpTotalPajak = [];

        listItemSplitBill.forEach(function(item) {
            let qty = parseInt(item.quantity);
            jumlahQtyItemSplitTerpilih += qty;

            let price = parseInt(item.harga);
            let hargaResultItem = qty * price;

            amountSplitBill += hargaResultItem;

            item.modifier.forEach(function(modifier) {
                amountSplitBill += modifier.harga * qty;
                hargaResultItem += modifier.harga * qty;
            });

            item.diskon.forEach(function(diskon) {
                amountSplitBill -= (diskon.value * hargaResultItem) / 100;
            })



        });

        listPajak.forEach(function(item) {
            let satuan = item.satuan; // Cek karakter terakhir (misalnya % atau lainnya)
            let amount = parseFloat(item.amount); // Ambil angka sebelum satuan

            let pajakValue = 0;

            // Hitung pajak berdasarkan satuan
            if (satuan === "%") {
                pajakValue = (amountSplitBill * amount) / 100; // Hitung jika persentase
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

        let totalPajakSplitBill = tmpTotalPajak.reduce(function(acc, curr) {
            return acc + curr;
        }, 0);

        amountPajakSplitBill = totalPajakSplitBill;

        listPajakSplitBill = tmpDataPajak;

        amountSplitBill += amountPajak;

        $('#subtotal-split-bill').text(formatRupiah(amountSplitBill.toString(), "Rp. "));
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
        if (listItemSplitBill.length) {
            $('#btnSplitBill').attr('disabled', false);
        } else {
            $('#btnSplitBill').attr('disabled', true);
        }
    }

    generateListItem();
    $('#btnSplitBill').attr('disabled', true);
</script>
