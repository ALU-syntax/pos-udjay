<style>
    .custom-card {
        border: 1px solid #ccc;
        border-radius: 8px;
        padding: 10px 15px;
        display: flex;
        height: 100px;
        font-size: 12px;
        align-items: center;
        justify-content: space-between;
    }

    .custom-card .form-check-input {
        width: 1.5rem;
        height: 1.5rem;
    }

    .btn-variant {
        height: 100px;
        font-size: 12px;
        color: black;
    }

    .btn-sales-type {
        height: 100px;
        font-size: 12px;
        color: black;
    }

    .callout {
        padding: 20px;
        margin: 20px 0;
        border: 1px solid #5bc0de;
        border-left-width: 5px;
        border-radius: 3px;
        h4 {
            margin-top: 0;
            margin-bottom: 5px;
        }
        p:last-child {
            margin-bottom: 0;
        }
        code {
            border-radius: 3px;
        }
        & + .bs-callout {
            margin-top: -5px;
        }
    }
</style>

<div class="modal-dialog modal-xl" id="productModal">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="btn btn-outline-secondary btn-lg" data-bs-dismiss="modal"
                id="btnBatal">Batal</button>
            <h5 class="modal-title mx-auto text-center" id="productModalLabel">
                <strong id="namaProduct">{{ $data->name }}</strong><br>
                <span id="totalHargaItem">{{ formatRupiah($variants[0]->harga, 'Rp. ') }}</span>
            </h5>
            <button id="saveItemToCart" type="button" class="btn btn-primary btn-lg">Simpan</button>
        </div>
        <div class="modal-body">
            <input type="text" value="{{ $data->id }}" name="idProduct" id="idProduct" hidden>

            @if($data->description)
                <div class="callout callout-info ">
                    <div class="container">
                        <div class="row">
                            <div class="col-1"><i class="d-flex justify-content-center align-items-center fa-solid fa-circle-info" style="color: #5bc0de; font-size: 30px;"></i></div>
                            <div class="col-11">
                                <h4>Deskripsi Produk</h4>
                                {{$data->description}}
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Variant --}}
            @if (count($variants) > 1)
                <div class="mb-4">
                    <label for="quantity" class="form-label"><strong>Variants</strong></label> |
                    <small>Single Choose</small>
                    <div class="row mt-1">
                        @foreach ($variants as $item)
                            <div class="form-group col-6 mt-2">
                                <button class="btn w-100 btn-xl btn-outline-primary btn-variant"
                                    data-variantid="{{ $item->id }}" data-harga="{{ $item->harga }}"
                                    data-name="{{ $item->name }}">
                                    <div class="row">
                                        <div class="col-6 me-auto">
                                            {{ $item->name }} ({{ $item->stok }})
                                        </div>
                                        <div class="col-4 ms-auto pe-0">
                                            {{ formatRupiah($item->harga, 'Rp. ') }}
                                        </div>
                                    </div>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Pilihan --}}
            @foreach ($pilihans as $dataPilihan)
                <div class="mb-4">
                    <label for="quantity" class="form-label"><strong>{{ $dataPilihan->name }}</strong></label> |
                    <small>Choose Many</small>
                    <div class="row mt-1">
                        @foreach ($dataPilihan->pilihans as $data)
                            <div class="col-6 mt-2">
                                <div class="custom-card">
                                    <span>{{ $data->name }}
                                        <small>{{ '(' . formatRupiah($data->harga, 'Rp. ') . ')' }}</small></span>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input form-pilihan form-switch"
                                            value="{{ $data->harga }}" type="checkbox" data-id="{{ $data->id }}"
                                            data-name="{{ $data->name }}">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <!-- Jumlah -->
            <div class="mb-4">
                <label for="quantity" class="form-label"><strong>Jumlah</strong></label>
                <div class="row">
                    <div class="col-6">
                        <input type="number" class="form-control text-center form-control-lg" id="quantity"
                            style="height: 75px; font-size: 17px;" value="1" min="1" readonly>
                    </div>
                    <div class="col-3">
                        <button
                            class="text-center align-items-center justify-content-center btn btn-lg btn-outline-primary w-100 d-flex"
                            id="decrement" style="height: 75px; font-size:25px;"><span
                                class="text-center"></span>-</button>
                    </div>
                    <div class="col-3">
                        <button
                            class="text-center btn btn-lg align-items-center justify-content-center btn-outline-primary w-100 d-flex"
                            id="increment" style="height: 75px; font-size:25px;"><span
                                class="text-center">+</span></button>
                    </div>
                </div>
            </div>


            @if (count($salesType) > 0)
                <div class="mb-4">
                    <label for="quantity" class="form-label"><strong>Sales Type</strong></label> |
                    <small>Single Choose</small>
                    <div class="row mt-1">
                        @foreach ($salesType as $item)
                            <div class="form-group col-md-6 mt-2">
                                <button class="btn w-100 btn-xl btn-outline-primary btn-sales-type"
                                    data-salestypeid="{{ $item->id }}" data-salestypename="{{ $item->name }}">
                                    <div class="row">
                                        <div class="col-6 me-auto">
                                            {{ $item->name }}
                                        </div>
                                    </div>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @foreach ($modifiers as $dataModifier)
                <div class="mb-4">
                    <label for="quantity" class="form-label"><strong>{{ $dataModifier->name }}</strong></label> |
                    <small>Choose Many</small>
                    <div class="row mt-1">
                        @foreach ($dataModifier->modifier as $data)
                            <div class="col-md-6 mt-2">
                                <div class="custom-card">
                                    <span>{{ $data->name }}
                                        <small>{{ '(' . formatRupiah($data->harga, 'Rp. ') . ')' }}</small></span>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input form-modifier form-switch"
                                            value="{{ $data->harga }}" type="checkbox" data-id="{{ $data->id }}"
                                            data-name="{{ $data->name }}">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <!-- Diskon -->
            @if (count($discounts) > 0)
                <div class="mb-4">
                    <label class="form-label"><strong>Diskon</strong></label>
                    <div class="row mt-1">
                        @foreach ($discounts as $discount)
                            <div class="col-md-6 mt-2">
                                <div class="custom-card">
                                    <span>{{ $discount->name }} @if ($discount->satuan == 'rupiah')
                                            {{ '(' . formatRupiah($discount->amount, 'Rp. ') . ')' }}
                                        @else
                                            {{ '(%' . $discount->amount . ')' }}
                                        @endif
                                    </span>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input form-diskon form-switch"
                                            value="{{ $discount->amount }}" data-type="{{ $discount->satuan }}"
                                            data-name="{{ $discount->name }}" type="checkbox"
                                            data-id="{{ $discount->id }}" id="discount-{{ $discount->id }}">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
            @endif
            <!-- Tipe Penjualan -->
            {{-- <div class="mb-4">
                    <label class="form-label"><strong>Tipe Penjualan</strong> | Pilih Satu</label>
                    <button class="btn btn-primary w-100">dine in</button>
                </div> --}}
            <!-- Catatan -->
            <div class="mb-4 mt-2">
                <label for="note" class="form-label"><strong>Catatan</strong></label>
                <textarea style="height: 220px;" class="form-control" id="catatan" rows="3"></textarea>
            </div>
        </div>
    </div>
</div>

<script>
    // Ambil elemen-elemen yang diperlukan
    var decrement = document.getElementById('decrement');
    var increment = document.getElementById('increment');
    var quantity = document.getElementById('quantity');
    var hargaTag = document.getElementById('totalHargaItem');
    var diskonCheckboxes = document.querySelectorAll('.form-diskon');
    var modifierCheckboxes = document.querySelectorAll('.form-modifier');
    var pilihanCheckboxes = document.querySelectorAll('.form-pilihan');

    // Ambil harga asli dari produk
    var stringHarga = hargaTag.textContent;
    var angkaSaja = stringHarga.replace(/[^\d]/g, "");
    var dataHarga = parseInt(angkaSaja, 10);
    var hargaAkhir = dataHarga;

    var variantId = '{{ $variants[0]->id }}';
    var variantName = '{{ $variants[0]->name }}';

    var dataSalesType = @json($salesType);
    if (dataSalesType.length > 0) {
        var salesTypeId = dataSalesType[0].id;
        var salesTypeName = dataSalesType[0].name;
    } else {
        var salesTypeId = null;
        var salesTypeName = null;
    }

    var listModifierId = [];
    var listModifierName = [];
    var listModifierHarga = [];

    var listPilihanId = [];
    var listPilihanName = [];
    var listPilihanHarga = [];

    var listDiskonId = [];
    var listDiskonName = [];
    var listDiskonType = [];
    var listDiskonValue = [];
    var listDiskonAmount = [];

    function hitungModifier() {
        let totalModifier = 0;
        let totalModifierId = [];
        let totalModifierName = [];
        let totalModifierHarga = [];

        modifierCheckboxes.forEach((checkbox) => {
            if (checkbox.checked) {
                const amount = parseFloat(checkbox.value);
                const id = checkbox.dataset.id;
                const name = checkbox.dataset.name;
                totalModifier += parseInt(quantity.value) * amount;

                totalModifierId.push(id);
                totalModifierHarga.push(amount);
                totalModifierName.push(name);
            }
        });

        listModifierId = totalModifierId;
        listModifierHarga = totalModifierHarga;
        listModifierName = totalModifierName;
        return totalModifier;
    }

    function hitungPilihan(){
        let totalPilihan = 0;
        let totalPilihanId = [];
        let totalPilihanName = [];
        let totalPilihanHarga = [];

        pilihanCheckboxes.forEach((pilihan) => {
            if(pilihan.checked){
                const amount = parseFloat(pilihan.value);
                const id = pilihan.dataset.id;
                const name = pilihan.dataset.name;
                totalPilihan += parseInt(quantity.value) * amount;

                totalPilihanId.push(id);
                totalPilihanHarga.push(amount);
                totalPilihanName.push(name);
            }
        });

        listPilihanId = totalPilihanId;
        listPilihanHarga = totalPilihanHarga;
        listPilihanName = totalPilihanName;
        return totalPilihan;
    }

    $('.btn-variant').first().addClass('active');
    $('.btn-sales-type').first().addClass('active');

    // Fungsi untuk menghitung total diskon
    function hitungDiskon() {
        let totalDiskon = 0;
        let totalDiskonId = [];
        let totalDiskonNama = [];
        let totalDiskonHarga = [];
        let totalDiskonValue = [];
        let totalDiskonType = [];

        diskonCheckboxes.forEach((checkbox) => {

            if (checkbox.checked) {
                const amount = parseFloat(checkbox.value);
                const type = checkbox.dataset.type;
                const id = checkbox.dataset.id;
                const name = checkbox.dataset.name;

                if (type === "rupiah") {
                    totalDiskon += amount;
                } else if (type === "percent") {
                    if (listModifierHarga.length > 0) {
                        let hargaBarang = dataHarga;
                        listModifierHarga.forEach(function(itemModifier) {
                            hargaBarang += itemModifier;
                        });
                        totalDiskon += (hargaBarang * parseInt(quantity.value) * amount) / 100;
                    } else {
                        totalDiskon += (dataHarga * parseInt(quantity.value) * amount) / 100;
                    }
                }

                totalDiskonId.push(id);
                totalDiskonValue.push(amount);
                totalDiskonHarga.push(totalDiskon);
                totalDiskonType.push(type);
                totalDiskonNama.push(name);
            }
        });

        listDiskonAmount = totalDiskonHarga;
        listDiskonId = totalDiskonId;
        listDiskonName = totalDiskonNama;
        listDiskonValue = totalDiskonValue;
        listDiskonType = totalDiskonType;
        return totalDiskon;
    }

    // Fungsi untuk menghitung harga akhir
    function updateHargaAkhir() {
        const quantityValue = parseInt(quantity.value);
        const totalModifier = hitungModifier();
        const totalPilihan = hitungPilihan();
        const totalDiskon = hitungDiskon();

        // Hitung harga akhir setelah diskon
        const hargaSebelumDiskon = dataHarga * quantityValue;
        hargaAkhir = hargaSebelumDiskon - totalDiskon + totalModifier + totalPilihan;

        // Pastikan harga tidak negatif

        if (hargaAkhir < 0) {
            hargaAkhir = 0;
        }

        let resultHargaAkhir = Math.round(hargaAkhir);
        // Update harga pada elemen HTML
        hargaTag.innerText = formatRupiah(resultHargaAkhir.toString(), "Rp. ");
    }

    // Event listener untuk tombol decrement
    decrement.addEventListener('click', () => {
        const value = parseInt(quantity.value);
        if (value > 1) {
            quantity.value = value - 1;
            updateHargaAkhir();
        }
    });

    // Event listener untuk tombol increment
    increment.addEventListener('click', () => {
        const value = parseInt(quantity.value);
        quantity.value = value + 1;
        updateHargaAkhir();
    });

    // Event listener untuk setiap checkbox diskon
    diskonCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', () => {
            updateHargaAkhir();
        });
    });

    modifierCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', () => {
            updateHargaAkhir();
        });
    })

    pilihanCheckboxes.forEach((pilihan) => {
        pilihan.addEventListener('change', () => {
            updateHargaAkhir();
        });
    })

    $('.btn-variant').off().on('click', function() {
        $('.btn-variant').removeClass('active');
        $(this).addClass('active')

        let id = $(this).data('variantid');
        let harga = $(this).data('harga');
        let name = $(this).data('name');

        variantId = id;
        dataHarga = harga;
        variantName = name;

        updateHargaAkhir();
    });

    $('.btn-sales-type').off().on('click', function() {
        $('.btn-sales-type').removeClass('active');
        $(this).addClass('active')

        let id = $(this).attr('data-salestypeid');
        let name = $(this).attr('data-salestypename');

        salesTypeId = id;
        salesTypeName = name;

        updateHargaAkhir();
    });

    $("#btnBatal").off().on('click', function(e) {
        // Tutup modal
        const modal = $('#itemModal');
        modal.modal('hide');
    });

    $('#saveItemToCart').on('click', function(e) {
        let tmpRandomId = generateRandomID();

        let dataNama = document.getElementById("namaProduct").textContent;
        let dataIdProduct = document.getElementById("idProduct").value;
        let dataHargaProduct = dataHarga;
        let quantityProduct = quantity.value;
        let totalHargaProduct = dataHargaProduct * quantityProduct;

        // MODIFIER
        let dataModifierId = listModifierId;
        let dataModifierNama = listModifierName;
        let dataModifierHarga = listModifierHarga;

        let dataModifier = [];
        for (let x = 0; x < dataModifierId.length; x++) {
            let tmpDataModifier = {
                tmpIdProduct: tmpRandomId,
                id: dataModifierId[x],
                nama: dataModifierNama[x],
                harga: dataModifierHarga[x],
            }

            dataModifier.push(tmpDataModifier);
        }

        // Pilihan
        let dataPilihanId = listPilihanId;
        let dataPilihanNama = listPilihanName;
        let dataPilihanHarga = listPilihanHarga;

        let dataPilihan = [];
        for(let i = 0; i < dataPilihanId.length; i++){
            let tmpDataPilihan = {
                tmpIdProduct: tmpRandomId,
                id: dataPilihanId[i],
                nama: dataPilihanNama[i],
                harga: dataPilihanHarga[i],
            }

            dataPilihan.push(tmpDataPilihan);
        }

        // DISKON
        let dataDiskonId = listDiskonId;
        let dataDiskonNama = listDiskonName;
        let dataDiskonHarga = listDiskonAmount;
        let dataDiskonValue = listDiskonValue;
        let dataDiskonType = listDiskonType;

        let dataDiskon = [];

        // console.log(dataDiskonNama);
        for (let i = 0; i < dataDiskonId.length; i++) {
            let tmpDataDiskon = {
                tmpIdProduct: tmpRandomId,
                id: dataDiskonId[i],
                nama: dataDiskonNama[i],
                satuan: dataDiskonType[i],
                value: dataDiskonValue[i],
                result: dataDiskonHarga[i],
            };

            dataDiskon.push(tmpDataDiskon);
        }


        let catatanTextArea = document.getElementById('catatan');
        let catatan = catatanTextArea.value;

        subTotal.push(totalHargaProduct);

        let resultModifierTotal = dataModifierHarga.reduce(function(acc, curr) {
            return acc + curr;
        }, 0);
        subTotal.push(resultModifierTotal);


        // console.log(totalHargaProduct)
        // console.log(resultModifierTotal);

        let data = {
            tmpId: tmpRandomId,
            idProduct: dataIdProduct,
            namaProduct: dataNama,
            harga: dataHargaProduct,
            quantity: quantityProduct,
            diskon: dataDiskon,
            salesType: salesTypeId,
            promo: [],
            idVariant: variantId,
            namaVariant: variantName,
            modifier: dataModifier,
            pilihan: dataPilihan,
            catatan: catatan,
            resultTotal: totalHargaProduct, //result
            listVariant: @json($variants),
            listPilihan: @json($pilihans),
            listSalesType: @json($salesType),
            listModifier: @json($modifiers),
            listDiskon: @json($discounts)
        }

        listItem.push(data);
        // updateSubTotal();
        // updatePajak();


        let html = ''
        if (dataModifierId.length > 0 && dataDiskonId.length > 0) {
            // HTML baru yang akan ditambahkan ke dalam form
            // console.log("masok modifier diskon")
            totalDiskon.push(...dataDiskonHarga);
            html = `
            <div class="row mb-0 mt-2">
                <div class="col-6">${dataNama}</div>
                <input type="text" name="nama[]" value="${dataNama}" hidden>
                <div class="col-5 text-end">${formatRupiah(totalHargaProduct.toString(), "Rp. ")}</div>
                <input type="text" name="harga[]" value="${totalHargaProduct}" hidden>
                <input type="text" name="quantity[]" value="${quantityProduct}" hidden>
                <input type="text" name="idProduct[]" value="${dataIdProduct}" hidden>
                <div class="col-1 text-end text-danger">
                    <button type="button" onclick="deleteItem(this)" data-tmpId="${tmpRandomId}" class="btn btn-link btn-sm text-danger p-0 w-100">&times;</button>
                </div>
            </div>
            `;

            for (let x = 0; x < dataModifierId.length; x++) {
                html += `
                <div class="row mb-0 modifier" data-tmpId="${tmpRandomId}">
                    <div class="col-7"><small style="color:gray;">${dataModifierNama[x]}</small></div>
                    <input type="text" name="namaModifier[]" value="${dataModifierNama[x]}" hidden>
                    <div class="col-4 text-end" style="color:gray;">${formatRupiah(dataModifierHarga[x].toString(), "Rp. ")}</div>
                    <input type="text" name="hargaModifier[]" value="${dataModifierHarga[x]}" hidden>
                    <input type="text" name="idModifier[]" value="${dataModifierId[x]}" hidden>
                </div>
                `;
            }
            for (let x = 0; x < dataDiskonId.length; x++) {
                html += `
                <div class="row mb-0 diskon" data-tmpId="${tmpRandomId}">
                    <input type="text" name="idDiskon[]" value="${dataDiskonId[x]}" hidden>
                    <input type="text" name="nominalDiskon[]" value="${dataDiskonHarga[x]}" hidden>
                </div>
                `;
            }
        } else if (dataModifierId.length > 0) {
            // console.log("masok modifier")
            html = `
            <div class="row mb-0 mt-2">
                <div class="col-6">${dataNama}</div>
                <input type="text" name="nama[]" value="${dataNama}" hidden>
                <div class="col-5 text-end">${formatRupiah(totalHargaProduct.toString(), "Rp. ")}</div>
                <input type="text" name="harga[]" value="${totalHargaProduct}" hidden>
                <input type="text" name="quantity[]" value="${quantityProduct}" hidden>
                <input type="text" name="idProduct[]" value="${dataIdProduct}" hidden>
                <div class="col-1 text-end text-danger">
                    <button type="button" onclick="deleteItem(this)" data-tmpId="${tmpRandomId}" class="btn btn-link btn-sm text-danger p-0 w-100">&times;</button>
                </div>
            </div>
            `;
            for (let x = 0; x < dataModifierId.length; x++) {
                html += `
                <div class="row mb-0 modifier" data-tmpId="${tmpRandomId}">
                    <div class="col-7"><small style="color:gray;">${dataModifierNama[x]}</small></div>
                    <input type="text" name="namaModifier[]" value="${dataModifierNama[x]}" hidden>
                    <div class="col-4 text-end" style="color:gray;">${formatRupiah(dataModifierHarga[x].toString(), "Rp. ")}</div>
                    <input type="text" name="hargaModifier[]" value="${dataModifierHarga[x]}" hidden>
                    <input type="text" name="idModifier[]" value="${dataModifierId[x]}" hidden>
                </div>
                `;
            }
        } else if (dataDiskonId.length > 0) {
            // console.log("masok diskon")
            totalDiskon.push(...dataDiskonHarga);
            html = `
            <div class="row mb-0 mt-2" data-tmpId="${tmpRandomId}">
                <div class="col-6">${dataNama}</div>
                <input type="text" name="nama[]" value="${dataNama}" hidden>
                <div class="col-5 text-end">${formatRupiah(totalHargaProduct.toString(), "Rp. ")}</div>
                <input type="text" name="harga[]" value="${totalHargaProduct}" hidden>
                <input type="text" name="quantity[]" value="${quantityProduct}" hidden>
                <input type="text" name="idProduct[]" value="${dataIdProduct}" hidden>
                <div class="col-1 text-end text-danger">
                    <button type="button" onclick="deleteItem(this)" data-tmpId="${tmpRandomId}" class="btn btn-link btn-sm text-danger p-0 w-100">&times;</button>
                </div>
            </div>
            `;

            for (let x = 0; x < dataDiskonId.length; x++) {
                html += `
                <div class="row mb-0 modifier" data-tmpId="${tmpRandomId}">
                    <input type="text" name="idDiskon[]" value="${dataDiskonId[x]}" hidden>
                    <input type="text" name="nominalDiskon[]" value="${dataDiskonHarga[x]}" hidden>
                </div>
                `;
            }
        } else {
            // console.log("masok")
            html = `
            <div class="row mb-0 mt-2">
                <div class="col-7">${dataNama}</div>
                <input type="text" name="nama[]" value="${dataNama}" hidden>
                <div class="col-4 text-end">${formatRupiah(totalHargaProduct.toString(), "Rp. ")}</div>
                <input type="text" name="harga[]" value="${totalHargaProduct}" hidden>
                <input type="text" name="quantity[]" value="${quantityProduct}" hidden>
                <input type="text" name="idProduct[]" value="${dataIdProduct}" hidden>
                <div class="col-1 text-end text-danger">
                    <button type="button" onclick="deleteItem(this)" data-tmpId="${tmpRandomId}" class="btn btn-link btn-sm text-danger p-0 w-100">&times;</button>
                </div>
            </div>
            `;
        }

        // console.log(totalDiskon);
        // updateHargaTotal();
        // Tambahkan elemen ke dalam form di dalam #order-list
        // $('#order-list').append(html);
        syncItemCart()

        // Tutup modal
        const modal = $('#itemModal');
        modal.modal('hide');
    });
</script>
