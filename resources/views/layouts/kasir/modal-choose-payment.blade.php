<style>
.btn-choice{
    width: 100px;
    height: 100px;
}
</style>

<div class="modal-dialog modal-dialog-centered modal-lg" id="choosePayment">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
            <h5 class="modal-title mx-auto text-center" id="productModalLabel">
                <strong id="namaProduct">Rp 80.000</strong><br>
            </h5>
            <button id="saveItemToCart" type="button" class="btn btn-primary">Simpan</button>
        </div>
        <div class="modal-body">
            <!-- Payment Options -->
            <div class="row">
                <div class="col-3">
                    <h6 class="d-flex text-center">Cash</h6>
                </div>
                <div class="col-9">
                    <div class="row"></div>
                    <div class="btn-group w-100 d-flex" role="group">
                        <button type="button" class="btn btn-lg btn-choice btn-outline-primary">Rp 50.000</button>
                        <button type="button" class="btn btn-lg btn-choice btn-outline-primary">Rp 100.000</button>
                        <button type="button" class="btn btn-lg btn-choice btn-outline-primary">Rp 150.000</button>
                    </div>
                    <input type="number" class="form-control mt-2" placeholder="Cash Amount">
                </div>
            </div>

            <hr>
            <div class="row">
                <div class="col-3">
                    <h6 class="d-flex text-center">EDC</h6>
                </div>
                <div class="col-9">
                    <div class="btn-group w-100 d-flex" role="group">
                        <button type="button" class="btn btn-lg btn-choice btn-outline-primary">BCA</button>
                        <button type="button" class="btn btn-lg btn-choice btn-outline-primary">BRI</button>
                        <button type="button" class="btn btn-lg btn-choice btn-outline-primary">MANDIRI</button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
