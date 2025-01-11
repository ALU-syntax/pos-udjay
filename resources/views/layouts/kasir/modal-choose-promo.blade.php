<style>
    .btn-choice {
        width: 100px;
        height: 100px;
    }
</style>

<div class="modal-dialog modal-dialog-centered modal-lg" id="promoModal">
    <div class="modal-content">
        <div class="modal-header d-flex justify-content-center align-items-center">
            <h5 class="modal-title" id="productModalLabel">
                <strong id="namaProduct">Choose Promo</strong><br>
            </h5>
        </div>
        <div class="modal-body">
            <!-- Payment Options -->
            <div class="container">
                <div class="row d-flex justify-content-center">
                    <div class="col-12">
                        <h4>Choose One Of The Following Promos</h4> <br>
                        <p>Ther eare more than 1 active promos, choose the desired promo and click button "Apply". Promo
                            will be applied automatically to the selected Items.</p>
                    </div>
                </div>

                <div class="row" id="rowListPromoCocok">
                    <div class="col-12">

                        <button type="button" id="btnApplyPromo"
                            class="btn btn-primary btn-lg mt-4 w-100">Apply</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    promoCocok.forEach(function(itemPromo, indexPromo) {
        let html = '';
        let reward = JSON.parse(itemPromo.reward);

        // console.log(reward)

        if (itemPromo.type == "discount") {
            let satuanReward = Object.keys(reward[0])[0];

            console.log(satuanReward);
            if (satuanReward == "rupiah") {
                html = `
                    <button type="button" class="btn mt-2 btn-outline-primary w-100 btn-lg ">
                        <div>
                            <p>${itemPromo.name}</p>
                            <p>(Diskon ${formatRupiah(reward[0].rupiah.toString(), "Rp. ")})</p>
                        </div>
                    </button>
                `
            } else {
                html = `
                    <button type="button" class="btn mt-2 btn-outline-primary w-100 btn-lg ">
                        <div>
                            <p>${itemPromo.name}</p>
                            <p>(Diskon %${reward[0].percent})</p>
                        </div>
                    </button>
                `
            }
        } else {

            // <select name="item_requirement[]" data-tmpid="${tmpId}" class="item_requirement select2InsideModal form-select w-100" style="width: 100% !important;" required>
            //                         <option selected disabled>Pilih Item</option>
            //                         ${listProductBaseOnOutlet.map(function(item) {
            //                             return `<option value="${item.name}">${item.name}</option>`;
            //                         }).join('')}
            //                     </select>

            html = `
                    <button type="button" class="btn mt-2 btn-outline-primary w-100 btn-lg ">
                        <div>
                            <p>${itemPromo.name}</p>
                            
                            
                        </div>
                    </button>
                `
        }

        $('#btnApplyPromo').before(html);
    });

    $('#btnApplyPromo').on('click', function() {
        const modal = $('#promoModal');
        // modal.html(res);
        modal.modal('hide');
    })
</script>
