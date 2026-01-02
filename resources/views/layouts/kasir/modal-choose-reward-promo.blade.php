<style>
    .btn-choice {
        width: 100px;
        height: 100px;
    }
</style>

<div class="modal-dialog modal-dialog-centered modal-lg" id="rewardModal">
    <div class="modal-content">
        <div class="modal-header d-flex">
            <div class="col-3">
                <button type="button" id="btnBackChooseItem" class="btn btn-outline-secondary btn-lg d-none">Back</button>
            </div>
            <div class="col-6 d-flex justify-content-center align-items-center">
                <h5 class="modal-title" id="productModalLabel">
                    <strong id="namaProduct">Choose Reward</strong><br>
                </h5>
            </div>
            <div class="col-3">

            </div>


        </div>
        <div class="modal-body">
            <!-- Payment Options -->
            <div class="container">
                <div class="row d-flex justify-content-center">
                    <div class="col-12">
                        <h4>Choose One Of The Following Rewards</h4> <br>
                        <p>There are more than 1 rewards for the selected promotion, choose the desired reward and click
                            button "Apply"</p>
                    </div>
                </div>

                <div class="row" id="rowListReward">
                    <div class="col-12">

                        <button type="button" id="btnApplyReward"
                            class="btn btn-success btn-lg mt-4 w-100">Apply</button>
                    </div>
                </div>



                <div class="row d-none" id="rowListVariant">
                    <button type="button" id="btnApplyRewardPromo"
                            class="btn btn-success btn-lg mt-4 w-100">Apply</button>
                </div>
            </div>
        </div>
    </div>
</div>



<script>
    var dataPromo = @json($dataPromo);
    console.log(dataPromo);

    var queue = @json($queue);
    console.log(queue);

    var listReward = @json($reward);
    console.log(listReward);

    listReward.forEach(function(item, index) {
        let html = '';
        let divContainer = `<div id="reward-container-${index+1}" class="mt-4"></div>`
        $('#btnApplyReward').before(divContainer);

        if (item.length > 1) {
            item.forEach(function(product) {
                html += `
                    <button type="button" class="btn mt-2 btn-outline-primary w-100 btn-lg btn-reward-product btn-product-${index+1}" data-idproduct="${product[0][0]}">
                        <div>
                            <p>${product[0][1]}</p>

                        </div>
                    </button>
                `
            })
            $(`#reward-container-${index+1}`).append(html)
        } else {
            item.forEach(function(product) {
                html += `
                    <button type="button" class="active btn mt-2 btn-outline-primary w-100 btn-reward-product btn-lg btn-product-${index+1}" data-idproduct="${product[0][0]}">
                        <div>
                            <p>${product[0][1]}</p>

                        </div>
                    </button>
                `
            })
            $(`#reward-container-${index+1}`).append(html)
        }

        $(`.btn-product-${index+1}`).first().addClass('active');

        $(`.btn-product-${index+1}`).off().on('click', function() {
            $(`.btn-product-${index+1}`).removeClass('active');
            $(this).addClass('active')

            let id = $(this).data('idproduct');
            console.log(id);
        });

    });

    $('#btnApplyReward').off().on('click', function() {
        var activeButtons = $('#rowListReward .active');
        let rewardTerpilih = [];

        activeButtons.each(function(index) {
            let idProduct = $(this).data('idproduct');
            console.log(idProduct);

            let dataRewardTerpilih = listReward.flat().find(item => item[0][0] === idProduct);
            if (dataRewardTerpilih) {
                rewardTerpilih.push(dataRewardTerpilih);
            }
        });
        console.log(rewardTerpilih);

        rewardTerpilih.forEach(function(item, index) {
            let variantRewardContainer =
                `<div id="variant-reward-container-${index+1}" class="mt-4 container-variant"></div>`;
            $('#btnApplyRewardPromo').before(variantRewardContainer); // Pindahkan ini ke atas

            console.log(item);
            let htmlVariant = `
            <div class="container">
                <div class="row">
                    <p>Item - ${item[0][1]}</p>
                    <div class="col-12">
                        ${item[1].map(function(variant, indexVariant) {
                            return `
                                <button type="button" class="btn mt-2 btn-outline-primary w-100 btn-lg btn-variant-product btn-variant-${index+1}" data-idproduct="${item[0][0]}" data-idvariant="${variant[0]}"
                                data-namaproduct="${item[0][1]}" data-namavariant="${variant[1]}" data-quantity="${item[2]}">
                                    <div>
                                        <p>${variant[1]}</p>
                                    </div>
                                </button>`;
                        }).join('')}
                    </div>
                </div>
            </div>
        `;

            $(`#variant-reward-container-${index+1}`).append(
                htmlVariant); // Pastikan ini setelah container ditambahkan

            $(`.btn-variant-${index+1}`).first().addClass('active');

            $(`.btn-variant-${index+1}`).off().on('click', function() {
                $(`.btn-variant-${index+1}`).removeClass('active');
                $(this).addClass('active')

                let id = $(this).data('idproduct');
                console.log(id);
            });
        });

        $('#rowListReward').addClass('d-none');
        $('#rowListVariant').removeClass('d-none');
        $('#btnBackChooseItem').removeClass('d-none');
    });

    $('#btnBackChooseItem').on('click', function(){
        $('#rowListReward').removeClass('d-none');
        $('#rowListVariant').addClass('d-none');
        $('.container-variant').remove();

        $(this).addClass('d-none');
    });

    $('#btnApplyRewardPromo').on('click', function(){
        let itemVariantActive = $('#rowListVariant .active');

        let getItemPromoByQueue = listItemPromo.filter((item) => item.queueItemId == queue);
        console.log(getItemPromoByQueue);

        getItemPromoByQueue.forEach(function(item){
            itemVariantActive.each(function(index){
                let dataIdProduct = $(this).data('idproduct');
                let dataIdVariant = $(this).data('idvariant');
                let dataNamaProduct = $(this).data('namaproduct');
                let dataNamaVariant = $(this).data('namavariant');
                let dataQuantity = $(this).data('quantity');
                console.log(dataQuantity);

                let fromPromo = [];
                fromPromo.push(dataPromo);

                let randomId = generateRandomID();

                let data = {
                    catatan: '',
                    diskon: [],
                    harga: 0,
                    idProduct: dataIdProduct,
                    idVariant: dataIdVariant,
                    modifier: [],
                    namaProduct: dataNamaProduct,
                    namaVariant: dataNamaVariant,
                    promo: fromPromo,
                    quantity: dataQuantity,
                    queueItemId: queue,
                    resultTotal: 0,
                    tmpId: randomId,
                    idItemPromo: item.tmpId
                }
                listRewardItem.push(data);
            });
        })

        const modal = $('#promoModal');
        // modal.html(res);
        modal.modal('hide');

        syncAllItemInCart();
        showToast('success', "Reward Promo Berhasil Ditambahkan");
    })
</script>
