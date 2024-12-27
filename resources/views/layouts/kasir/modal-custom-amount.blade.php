<div class="modal-dialog modal-dialog-centered" id="custom-diskon">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" id="applyBatal" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
            <h5 class="modal-title mx-auto text-center" id="productModalLabel">

            </h5>
            <button id="applyBtn" type="button" class="btn btn-primary" disabled>Apply</button>
        </div>
        <div class="modal-body">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label>Discount Amount</label>
                            @if ($data->satuan_discount_custom == 'rupiah')
                                <input type="text" id="customDiskonRupiah" name="customDiskonRupiah"
                                    class="form-control" placeholder="Rp. " required>
                            @else
                                <input type="number" id="customDiskonPercent" name="customDiskonPercent"
                                    class="form-control" placeholder="%" required>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    var customDiskonRupiahInput = document.getElementById("customDiskonRupiah");
    if(customDiskonRupiahInput){
        customDiskonRupiahInput.addEventListener("keyup", function(e) {
            this.value = formatRupiah(this.value, "Rp. ");
            if (this.value == '' || this.value == 'Rp. ') {
                $("#applyBtn").attr('disabled', true);
            } else {
                $("#applyBtn").removeAttr('disabled');
            }
        });
    }

    var customDiskonPercentInput = document.getElementById('customDiskonPercent');
    if(customDiskonPercentInput){
        customDiskonPercentInput.addEventListener('keyup', function(e){
            if (this.value == '') {
                $("#applyBtn").attr('disabled', true);
            } else {
                $("#applyBtn").removeAttr('disabled');
            }
        })
    }

    $(document).ready(function() {
        
        let diskonData = '{!! $data !!}';
        let dataDiskon = JSON.parse(diskonData);

        $('#applyBtn').on('click', function() {
            console.log(dataDiskon)
            if (dataDiskon.satuan_discount_custom == "rupiah") {
                let diskonAmount = $('#customDiskonRupiah').val();
                let diskonTrim = diskonAmount.trim();
                let diskonAngka = parseInt(diskonTrim.replace(/[^\d]/g, ""));


                let tmpDataDiskonRupiah = {
                    id: dataDiskon.id,
                    nama: dataDiskon.name,
                    satuan: dataDiskon.satuan_discount_custom,
                    value: diskonAngka,
                }

                listDiskonAllItem.push(tmpDataDiskonRupiah);

            } else {
                let diskonAmount = $('#customDiskonPercent').val();

                listItem.forEach(function(item, index) {
                    let result = item.harga / diskonAmount;
                    let tmpDataDiskonPercent = {
                        id: dataDiskon.id,
                        nama: dataDiskon.name,
                        result: result,
                        satuan: dataDiskon.satuan_discount_custom,
                        tmpIdProduct: item.tmpId,
                        value: diskonAmount,
                    };

                    item.diskon.push(tmpDataDiskonPercent);
                });
            }

            let diskonElement = $('#Diskon').find(`.list-diskon[data-id="${dataDiskon.id}"]`);

            // Tambahkan atribut disabled
            diskonElement.attr('disabled', true);

            // Tambahkan class text-muted pada span dengan id text-diskon-list
            diskonElement.find('#text-diskon-list').addClass('text-muted');

            const modal = $('#itemModal');
            // modal.html(res);
            modal.modal('hide');
            showToast('success', "Diskon Berhasil Ditambah");

            syncItemCart();
        });

        $('#applyBatal').on('click', function() {
            const modal = $('#itemModal');
            // modal.html(res);
            modal.modal('hide');
        })

    });
</script>
