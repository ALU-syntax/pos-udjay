<style>
    .radio-card {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: border-color 0.3s ease;
    }

    .radio-card:hover {
        border-color: #007bff;
    }

    .radio-card input[type="radio"] {
        display: none;
    }

    .radio-card input[type="radio"]:checked+.radio-label {
        border-color: #007bff;
        box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
    }

    .radio-label {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border: 2px solid transparent;
        border-radius: 8px;
        padding: 10px;
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }

    .radio-label img {
        width: 50px;
        height: 50px;
    }

    .radio-label h5 {
        margin-top: 10px;
        font-size: 16px;
        font-weight: bold;
    }

    .radio-label p {
        font-size: 14px;
        color: #666;
    }

    .radio-label a {
        font-size: 14px;
        color: #007bff;
        text-decoration: underline;
    }
</style>
<x-modal title="Tambah Promo" addStyle="modal-lg" action="{{ $action }}" method="POST" customSubmit="true">
    @if ($data->id)
        @method('put')
    @endif
    <div class="accordion" id="accordionExample">
        <!-- Promo Information -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingOne">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne"
                    aria-expanded="true" aria-controls="collapseOne" disabled>
                    Promo Information
                </button>
            </h2>
            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne"
                data-bs-parent="#accordionExample">
                <div class="accordion-body">
                    <div class="container mt-2">
                        <h4>Select Promo Type</h4>
                        <div class="form-grup">
                            <small id="promo_type_feedback" class="form-text text-danger d-none"> *Pilih salah satu dari
                                Promo Type.</small>
                            <div class="row mt-3">
                                <!-- Discount per Item -->
                                <div class="col-md-6">
                                    <label class="radio-card">
                                        <input type="radio" id="promo_type" name="promo_type" value="discount"
                                            @if ($data->type == 'discount') checked @endif
                                            @if ($data->id) disabled @endif>
                                        <div class="radio-label">
                                            <img src="{{ asset('img/icon-discount-item.png') }}" alt="Discount Icon">
                                            <h5>Discount per Item</h5>
                                            <p>Customers get a <strong>discount (by % or amount)</strong> automatically
                                                when
                                                they buy the specified item and quantity.</p>
                                        </div>
                                    </label>
                                </div>
                                <!-- Free Item -->
                                <div class="col-md-6">
                                    <label class="radio-card">
                                        <input type="radio" id="promo_type" name="promo_type" value="free-item"
                                            @if ($data->type == 'free-item') checked @endif
                                            @if ($data->id) disabled @endif>
                                        <div class="radio-label">
                                            <img src="{{ asset('img/icon-free-item.png') }}" alt="Free Item Icon">
                                            <h5>Free Item</h5>
                                            <p>Customers get a <strong>free item automatically</strong> when they buy
                                                the
                                                specified item and quantity.</p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="form-grup">
                                    <label for="promo-name">Nama Promo <span class="text-danger ">*</span></label>
                                    <input type="text" id="promo-name" name="name" class="form-control"
                                        value="{{ $data->name }}" placeholder="Masukan nama promo" required>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="form-group p-0">
                                    <label for="outlet_id">Outlet <span class="text-danger ">*</span></label>
                                    <select name="outlet_id[]" id="outlet_id"
                                        class="select2MultipleInsideModal form-select w-100"
                                        style="width: 100% !important;" required multiple
                                        @if ($data->id) disabled @endif>
                                        <option disabled>Pilih Outlet</option>
                                        @foreach (json_decode($outlets) as $outlet)
                                            <option value="{{ $outlet->id }}"
                                                @if ($data->outlet_id == $outlet->id) selected @endif>
                                                {{ $outlet->name }}</option>
                                        @endforeach
                                    </select>
                                    <small id="outlet_id_feedback" class="d-none text-danger"><i>*Pilih Outlet Terlebih
                                            Dahulu</i></small>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div class="form-group p-0">
                                    <label>Assign Sales Type <span class="text-danger">*</span></label><br>
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="sales_type"
                                                    id="all_sales_type" value="all_sales_type"
                                                    @if ($data->id) disabled @endif
                                                    @if ($data->id) @if (count(json_decode($data->sales_type)) == 0)checked @endif
                                                @else checked @endif>
                                                <label class="form-check-label" for="all_sales_type">All Sales
                                                    Type</label>
                                                <br>
                                                <small style="color: gray">Promo will apply to current and upcoming
                                                    sales type.</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="sales_type"
                                                    id="specific_sales_type"
                                                    @if ($data->id) disabled @endif
                                                    @if ($data->id) @if (count(json_decode($data->sales_type)) > 0)checked @endif
                                                    @endif
                                                value="specific_sales_type">
                                                <label class="form-check-label" for="specific_sales_type">
                                                    Specific Sales Type
                                                </label> <br>
                                                <small style="color: gray">Choose the selected sales type for this
                                                    promo.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 d-none" id="salesTypeSelect">

                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- <p>Complete this task to proceed:</p> --}}

                    <button type="button" class="btn btn-primary mt-3 next-btn" id="btnPromoInformationNext"
                        data-session="promo-information" data-target="#collapseTwo">Next</button>
                </div>
            </div>
        </div>

        <!-- Purchase Requirement -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingTwo">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo" disabled>
                    Purchase Requirement
                </button>
            </h2>
            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo"
                data-bs-parent="#accordionExample">
                <div class="accordion-body">
                    <div class="container">
                        <div class="col-md-12">
                            <div class="form-group p-0">
                                <label>Customers must add items and quantity specified below to their cart <span
                                        class="text-danger">*</span></label><br>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="requirement_type"
                                                id="specific_item_requirement" value="specific_item_requirement"
                                                @if ($data->id) @if ($data->purchase_requirement == 'any_item') checked @endif
                                            @else checked @endif
                                            @if ($data->id) disabled @endif>
                                            <label class="form-check-label"
                                                for="specific_item_requirement">Item</label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="requirement_type"
                                                id="all_item_from_category_requirement"
                                                @if ($data->purchase_requirement == 'any_category') checked @endif
                                                @if ($data->id) disabled @endif
                                                value="all_item_from_category_requirement">
                                            <label class="form-check-label" for="all_item_from_category_requirement">
                                                Any item from a category
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="container" id="specific_item_list">

                        @if (!$data->id)
                            <div class="row mt-3">
                                <button type="button" class="btn btn-primary " id="add_specific_item">Add
                                    Item</button>
                            </div>
                        @endif
                    </div>

                    <div class="container d-none" id="any_item_from_category">

                    </div>

                    <button type="button" class="btn btn-round btn-outline-secondary mt-3 next-btn"
                        id="backToCollapseOne" data-target="#collapseOne">Previous</button>
                    <button type="button" class="btn btn-round btn-primary mt-3 next-btn"
                        id="btnPurchasRequirementNext" data-session="promo-information" data-target="#collapseThree"
                        disabled>Next</button>
                </div>
            </div>
        </div>

        <!-- Reward -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingThree">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree" disabled>
                    Reward
                </button>
            </h2>
            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree"
                data-bs-parent="#accordionExample">
                <div class="accordion-body">

                    <div id="reward-discount" class="row">

                    </div>

                    <div class="row d-none" id="reward-free-item">
                        <p>Customers will get free items specified below to their cart</p>

                        @if (!$data->id)
                            <div class="row mt-3">
                                <button class="btn btn-primary w-100" type="button"
                                    id="add_specific_item_reward">Add
                                    Item</button>
                            </div>
                        @endif
                    </div>

                    <button type="button" class="btn btn-round btn-outline-secondary mt-3 next-btn"
                        id="backToCollapseTwo" data-target="#collapseTwo">Previous</button>
                    <button type="button" class="btn btn-round btn-primary mt-3 next-btn" id="btnRewardNext"
                        data-session="promo-information" data-target="#collapseFour" disabled>Next</button>
                </div>
            </div>
        </div>

        {{-- Promo Configuration --}}
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingFour">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour" disabled>
                    Promo Configuration
                </button>
            </h2>
            <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingThree"
                data-bs-parent="#accordionExample">
                <div class="accordion-body">
                    <div class="row">
                        <div class="form-check col-12">
                            <input class="form-check-input" type="checkbox" value="true" name="apply_multiple"
                                id="apply_multiple" placeholder="Tanggal">
                            <label class="form-check-label" for="apply_multiple">
                                Applies in multiple
                            </label> <br>
                            <small class="text-muted">
                                (e.g. if there is a '5% off', customer who buy 2 will get 5% off for 2 items, buy 3 will
                                get 5% off for 3 items, etc.)
                            </small>
                        </div>

                        <div class="form-check col-12">
                            <input class="form-check-input" type="checkbox" value="true" name="promo_time_period"
                                id="promo_time_period">
                            <label class="form-check-label" for="promo_time_period">
                                Set promo time period
                            </label> <br>
                            <small class="text-muted">By not setting a promo time period, this promo will run
                                forever
                                starting tomorrow. You can setup it later.</small>
                        </div>

                        <div class="row d-none" id="row_promo_time_periode">

                            <div class="form-check">
                                <div class="form-group">
                                    <label for="schedule_promo">Select Date</label>
                                    <div class="input-group mb-3 mt-3">
                                        <div class="input-group-prepend">
                                            <button class="btn btn-outline-secondary" type="button"
                                                id="prevDate">-</button>
                                        </div>
                                        <input type="text" id="schedule_promo" name="schedule_promo"
                                            class="form-control">
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary" type="button"
                                                id="nextDate">+</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="schedule_promo">Select Hour</label>
                                    <div class="col-2">
                                        <div class="input-group date" id="timePicker">
                                            <input type="time" class="form-control timePicker" value="00:00"
                                                name="start_hour" id="start_hour" required>
                                            <span class="input-group-addon"><i class="fa fa-clock-o"
                                                    aria-hidden="true"></i></span>
                                        </div>
                                    </div>

                                    <div class="col-1 d-flex justify-content-center align-items-center">
                                        <span style="font-size: 30px">-</span>
                                    </div>

                                    <div class="col-2">
                                        <div class="input-group date" id="timePicker">
                                            <input type="time" class="form-control timePicker" value="23:59"
                                                name="end_hour" id="end_hour" required>
                                            <span class="input-group-addon"><i class="fa fa-clock-o"
                                                    aria-hidden="true"></i></span>
                                        </div>
                                    </div>

                                </div>

                                <div class="form-group">
                                    <input class="form-check-input " type="checkbox" value="check_all_day"
                                        id="check_all_day">
                                    <label class="form-check-label" for="check_all_day">
                                        Select All
                                    </label> <br>

                                    <input class="form-check-input day_check" type="checkbox" value="minggu"
                                        name="day[]" id="minggu">
                                    <label class="form-check-label" for="flexCheckChecked">
                                        Minggu
                                    </label> <br>

                                    <input class="form-check-input day_check" type="checkbox" value="senin"
                                        name="day[]" id="senin">
                                    <label class="form-check-label" for="flexCheckChecked">
                                        Senin
                                    </label> <br>

                                    <input class="form-check-input day_check" type="checkbox" value="selasa"
                                        name="day[]" id="selasa">
                                    <label class="form-check-label" for="flexCheckChecked">
                                        Selasa
                                    </label> <br>

                                    <input class="form-check-input day_check" type="checkbox" value="rabu"
                                        name="day[]" id="rabu">
                                    <label class="form-check-label" for="flexCheckChecked">
                                        Rabu
                                    </label> <br>

                                    <input class="form-check-input day_check" type="checkbox" value="kamis"
                                        name="day[]" id="kamis">
                                    <label class="form-check-label" for="flexCheckChecked">
                                        Kamis
                                    </label> <br>

                                    <input class="form-check-input day_check" type="checkbox" value="jumat"
                                        name="day[]" id="jumat">
                                    <label class="form-check-label" for="flexCheckChecked">
                                        Jumat
                                    </label> <br>

                                    <input class="form-check-input day_check" type="checkbox" value="sabtu"
                                        name="day[]" id="sabtu">
                                    <label class="form-check-label" for="flexCheckChecked">
                                        Sabtu
                                    </label> <br>

                                </div>



                            </div>

                        </div>

                    </div>
                    <button type="button" class="btn btn-round btn-outline-secondary mt-3 next-btn"
                        id="backToCollapseThree" data-target="#collapseThree">Previous</button>
                    <button type="submit" class="btn btn-round btn-success mt-3 finish-btn"
                        id="btnFinish">Finish</button>

                </div>
            </div>
        </div>
    </div>


    <script>
        var _totalSpesificItemPurchaseRequirement = 0;
        var _totalRewardItem = 0;
        var listProductBaseOnOutlet = [];
        var outletTerpilih = [];

        function toggleSalesTypeSelect() {
            if ($('#specific_sales_type').is(':checked')) {
                $('#salesTypeSelect').removeClass('d-none'); // Tampilkan select

                @if (!$data->id)
                    initializeSalesTypeSelect();
                @endif
            } else {
                $('#salesTypeSelect').addClass('d-none'); // Sembunyikan select
                $('#salesTypeSelect').empty();
            }
        }

        function initializeSalesTypeSelect() {
            $('#salesTypeSelect').empty();
            let html = `<div class="form-group p-0">
                                <label for="sales_type_choose">Sales Type<span
                                        class="text-danger ">*</span></label>
                                <select name="sales_type_choose[]" id="sales_type_choose"
                                    class="select2MultipleInsideModal form-select w-100"
                                    style="width: 100% !important;" required multiple>
                                    <option disabled>Pilih Sales Type</option>
                                </select>
                                <small id="sales_type_choose_feedback" class="d-none text-danger"><i>*Pilih
                                        Sales Type Terlebih
                                        Dahulu</i></small>
                            </div>`

            $('#salesTypeSelect').append(html);

            $.ajax({
                url: `{{ route('library/salestype/getSalesTypeByOutlet') }}`, // URL endpoint Laravel
                type: 'GET',
                data: {
                    idOutlet: outletTerpilih // Kirim data array ke server
                },
                success: function(response) {
                    // Bersihkan opsi yang ada di select
                    const selectElement = $('#sales_type_choose');
                    selectElement.empty(); // Menghapus semua opsi yang ada

                    console.log(response)
                    // Tambahkan opsi default
                    selectElement.append(
                        '<option disabled="">Pilih Sales Type</option>');

                    if (outletTerpilih.length > 1) {
                        response.forEach(function(item) {
                            selectElement.append(
                                `<option value="${item.name}">${item.name}</option>`
                            );
                        });
                    } else {
                        // Tambahkan opsi baru berdasarkan respons
                        response.forEach(function(category) {
                            selectElement.append(
                                `<option value="${category.name}">${category.name}</option>`
                            );
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Terjadi kesalahan:", error);
                }
            });

            intializeSalesTypeSelect2();

        }

        function intializeSalesTypeSelect2() {
            $("#sales_type_choose").off().select2({
                    dropdownParent: $("#modal_action"), // Pastikan parent diatur untuk modal
                    // Callback setelah dropdown dibuka
                    closeOnSelect: false,
                })
                .on("select2:open", function() {
                    const selectElement = $(this);
                    const dropdown = $(".select2-container--open");

                    // Hitung posisi elemen input
                    const offset = selectElement.offset();
                    const height = selectElement.outerHeight();

                    // Atur posisi dropdown ke posisi fixed
                    dropdown.css({
                        position: "fixed",
                        top: offset.top + height - $(window)
                            .scrollTop(), // Hitung posisi relatif terhadap layar
                        left: offset.left,
                        width: selectElement.outerWidth() - 85,
                        zIndex: 9999, // Pastikan lebih tinggi dari modal
                    });
                }).on("select2:close", function() {
                    const dropdown = $(".select2-container");

                    // Hapus style yang diterapkan saat dropdown ditutup
                    dropdown.css({
                        position: "",
                        top: "",
                        left: "",
                    });
                });

        }

        function getCategoryByOutlet() {
            $.ajax({
                url: `{{ route('library/product/getCategoryByOutlet') }}`, // URL endpoint Laravel
                type: 'GET',
                data: {
                    idOutlet: outletTerpilih // Kirim data array ke server
                },
                success: function(response) {
                    // Bersihkan opsi yang ada di select
                    const selectElement = $('#category_requirement');
                    selectElement.empty(); // Menghapus semua opsi yang ada

                    // Tambahkan opsi default
                    selectElement.append(
                        '<option selected="" disabled="">Pilih Category</option>');

                    if (outletTerpilih.length > 1) {
                        response.forEach(function(item) {
                            selectElement.append(
                                `<option value="${item.category.id}">${item.category.name}</option>`
                            );
                        });
                    } else {
                        // Tambahkan opsi baru berdasarkan respons
                        response.forEach(function(category) {
                            selectElement.append(
                                `<option value="${category.id}">${category.name}</option>`
                            );
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Terjadi kesalahan:", error);
                }
            });
        }

        function togglePurchaseRequirementType() {
            const selectedValue = $("input[name='requirement_type']:checked").val();

            if (selectedValue === "specific_item_requirement") {
                $("#specific_item_list").removeClass("d-none");
                $("#any_item_from_category").addClass("d-none");
                $("#row_any_item_from_category").remove();
                $('#btnPurchasRequirementNext').attr('disabled', true);

            } else if (selectedValue === "all_item_from_category_requirement") {
                _totalSpesificItemPurchaseRequirement = 0;
                $('#btnPurchasRequirementNext').attr('disabled', true);
                $("#any_item_from_category").removeClass("d-none");
                $("#specific_item_list").addClass("d-none");
                $(".row_condition_purchase_requirement").remove();
                $('.specific_item').remove();
                $('.condition_purchase_requirement').remove();
                $('.pembatas').remove();
                let html = `<div class="row" id="row_any_item_from_category">
                            <div class="col-3">
                                <input type="number" name="qty_requirement_category_item" class="form-control" min="1"
                                    placeholder="Qty" required>
                            </div>
                            <div class="col-1 d-flex align-items-center justify-content-center">
                                <strong class="text-muted">Of</strong>
                            </div>
                            <div class="col-8 mt-2">
                                <div class="form-group p-0">
                                    <select name="category_requirement" id="category_requirement" class="select2InsideModal form-select w-100"
                                        style="width: 100% !important;" required>
                                        <option selected disabled>Pilih Category</option>
                                        <!-- Anda bisa mengganti dengan opsi dinamis -->
                                        
                                    </select>
                                </div>
                            </div>
                        </div>`;
                $("#any_item_from_category").append(html);

                $('#category_requirement').off().on('change', function(e) {
                    const selectedValue = $(this).val();
                    if (selectedValue) {
                        $('#btnPurchasRequirementNext').removeAttr('disabled');
                    } else {
                        $('#btnPurchasRequirementNext').attr('disabled', 'disabled');
                    }
                })

                @if (!$data->id)
                    getCategoryByOutlet();
                @endif
            }
        }

        function initializeSelect2() {
            $(".select2InsideModal").select2({
                dropdownParent: $("#modal_action")
            }).on("select2:open", function() {
                const selectElement = $(this);
                const dropdown = $(".select2-container--open");

                // Hitung posisi elemen input
                const offset = selectElement.offset();
                const height = selectElement.outerHeight();

                // Atur posisi dropdown ke posisi fixed
                dropdown.css({
                    position: "fixed",
                    top: offset.top + height - $(window)
                        .scrollTop(), // Hitung posisi relatif terhadap layar
                    left: offset.left,
                    width: selectElement.outerWidth() - 392,
                    zIndex: 9999, // Pastikan lebih tinggi dari modal
                });
            }).on("select2:close", function() {
                const dropdown = $(".select2-container");

                // Hapus style yang diterapkan saat dropdown ditutup
                dropdown.css({
                    position: "",
                    top: "",
                    left: "",
                });
            });
        }

        function initializeSelect2Multiple() {
            $(".select2MultipleInsideModal").off().select2({
                    dropdownParent: $("#modal_action"), // Pastikan parent diatur untuk modal
                    // Callback setelah dropdown dibuka
                    closeOnSelect: false,
                })
                .on("select2:open", function() {
                    const selectElement = $(this);
                    const dropdown = $(".select2-container--open");

                    // Hitung posisi elemen input
                    const offset = selectElement.offset();
                    const height = selectElement.outerHeight();

                    // Atur posisi dropdown ke posisi fixed
                    dropdown.css({
                        position: "fixed",
                        top: offset.top + height - $(window)
                            .scrollTop(), // Hitung posisi relatif terhadap layar
                        left: offset.left,
                        width: selectElement.outerWidth() - 85,
                        zIndex: 9999, // Pastikan lebih tinggi dari modal
                    });
                }).on("select2:close", function() {
                    const dropdown = $(".select2-container");

                    // Hapus style yang diterapkan saat dropdown ditutup
                    dropdown.css({
                        position: "",
                        top: "",
                        left: "",
                    });
                });
        }

        // Handle choice and input
        function satuanClicked() {
            const radios = document.querySelectorAll('input[name="satuan"]');
            var amountInput = document.getElementById("amountInput");


            // Add event listener to radio buttons
            radios.forEach((radio) => {
                radio.addEventListener("change", () => {
                    const satuanChoice = document.querySelector('input[name="satuan"]:checked').value;
                    console.log(`Satuan selected: ${satuanChoice}`);
                    amountInput.value = ""; // Reset input when selection changes

                    $('#btnRewardNext').attr('disabled', true);
                    // Handle keyup based on selected choice
                    amountInput.removeEventListener("keyup", handleKeyup);
                    amountInput.addEventListener("keyup", handleKeyup);
                });
            });

            amountInput.addEventListener("keyup", handleKeyup);

            function handleKeyup(e) {
                const satuanChoice = document.querySelector('input[name="satuan"]:checked').value;
                if (satuanChoice === "rupiah") {
                    amountInput.type = "text";
                    this.value = formatRupiah(this.value, "Rp. ");
                    console.log(this.value);

                    if (this.value != '' || this.value > 0 || this.value == "Rp. ") {
                        $('#btnRewardNext').removeAttr('disabled');
                    } else {
                        $('#btnRewardNext').attr('disabled', true);
                    }
                } else if (satuanChoice === "percent") {
                    // Set input type to number and add min attribute
                    amountInput.type = "number";
                    amountInput.min = "1";


                    console.log(this.value)

                    if (this.value != '' || this.value > 0) {
                        $('#btnRewardNext').removeAttr('disabled');
                    } else {
                        $('#btnRewardNext').attr('disabled', true);
                    }
                }
            }

            // Define keyup handler
        }

        $(document).ready(function() {
            // Pengecekan awal saat halaman dimuat
            toggleSalesTypeSelect();

            // Jalankan fungsi saat halaman dimuat untuk memastikan keadaan awal
            togglePurchaseRequirementType();

            // Event listener ketika radio button berubah
            $('input[name="sales_type"]').on('change', function() {
                toggleSalesTypeSelect();
            });

            initializeSelect2();

            initializeSelect2Multiple();

            $("#btnPromoInformationNext").on('click', function() {
                const target = $(this).data('target');
                let isValid = 0; // Flag untuk mengecek apakah semua input sudah valid

                // Loop semua input dan select di dalam collapseOne
                $("#collapseOne input, #collapseOne select").each(function() {
                    const $this = $(this);

                    @if (!$data->id)
                        if ($this.is("select")) {
                            if ($this.attr('id') == 'sales_type_choose') {
                                if ($('input[name="sales_type"]:checked').val() ==
                                    'specific_sales_type') {
                                    console.log($this.val());
                                    if ($this.val().length == 0) {
                                        $(`#${$this.attr('id')}_feedback`).removeClass('d-none');
                                        isValid++
                                    } else {
                                        $(`#${$this.attr('id')}_feedback`).addClass('d-none');
                                        // isValid = true
                                    }
                                }
                            } else {
                                if ($this.val().length == 0) {
                                    $(`#${$this.attr('id')}_feedback`).removeClass('d-none');
                                    isValid++
                                } else {
                                    $(`#${$this.attr('id')}_feedback`).addClass('d-none');
                                    // isValid = true
                                }
                            }

                        }
                    @endif

                    // radio button
                    if ($this.is("input") == true && $this.is(":radio") == true) {
                        if ($(`input[name="${$this.attr('name')}"]:checked`).length == 0) {
                            isValid++;
                            $("#" + `${$this.attr('name')}_feedback`).removeClass('d-none');
                        } else {
                            $(`#${$this.attr('name')}_feedback`).addClass('d-none');
                            // isValid = true;
                        }
                    }

                    //input biasa
                    if ($this.is("input") == true && $this.is(":radio") == false) {
                        if (!$this.val()) {
                            isValid++
                            $this.addClass("is-invalid");
                        } else {
                            $this.removeClass("is-invalid").addClass("is-valid");
                            // isValid = true;
                        }
                    }
                });

                // Jika semua input valid, lanjutkan ke tahap berikutnya
                console.log(isValid)
                if (isValid == 0) {
                    $(target).collapse('show'); // Tampilkan accordion berikutnya
                    $("#collapseOne").collapse('hide'); // Sembunyikan accordion saat ini
                } else {
                    // Tampilkan pesan error (opsional)
                    // alert("Harap lengkapi semua input sebelum melanjutkan.");
                }
            });

            $('#btnRewardNext').on('click', function(e) {
                const target = $(this).data('target');

                e.preventDefault(); // Mencegah submit form atau aksi default tombol
                let isValid = true; // Flag untuk validasi

                // Loop melalui semua input dan select yang memiliki atribut "required"
                $('input[required], select[required]').each(function() {
                    if ($(this).val() === '' || $(this).val() === null) {
                        isValid = false;
                        $(this).addClass('is-invalid'); // Tambahkan kelas untuk styling kesalahan
                        $(this).focus(); // Fokus pada elemen yang belum terisi
                        return false; // Hentikan loop setelah menemukan elemen kosong
                    } else {
                        $(this).removeClass('is-invalid'); // Hapus kelas jika sudah valid
                    }
                });

                if (isValid) {
                    // Jika valid, lakukan aksi selanjutnya
                    $(target).collapse('show'); // Tampilkan accordion berikutnya
                    $("#collapseTwo").collapse('hide'); // Sembunyikan accordion saat ini
                } else {
                    showToast("error", "Harap lengkapi semua input yang wajib diisi.");
                }
            })

            $('#backToCollapseOne').on('click', function() {
                const target = $(this).data('target');
                $(target).collapse('show');
            });

            $('#backToCollapseTwo').on('click', function() {
                const target = $(this).data('target');
                $(target).collapse('show');
            });

            $('#backToCollapseThree').on('click', function() {
                const target = $(this).data('target');
                $(target).collapse('show');
            })

            // Handle button click to add a new specific item
            $('#add_specific_item').on('click', function() {
                $('#btnPurchasRequirementNext').removeAttr('disabled');
                let tmpId = generateRandomID();

                var newItem = '';
                if (_totalSpesificItemPurchaseRequirement < 1) {
                    newItem = `
                    <div class="row specific_item mt-3">
                        <div class="col-3">
                            <input type="number" name="qty_requirement_item[]" class="form-control" placeholder="Qty" min="1" required>
                        </div>
                        <div class="col-1 d-flex align-items-center justify-content-center">
                            <strong class="text-muted">Of</strong>
                        </div>
                        <div class="col-7 mt-2">
                            <div class="form-group p-0">
                                <select name="item_requirement[]" data-tmpid="${tmpId}" class="item_requirement select2InsideModal form-select w-100" style="width: 100% !important;" required>
                                    <option selected disabled>Pilih Item</option>
                                    ${listProductBaseOnOutlet.map(function(item) {
                                        return `<option value="${item.name}">${item.name}</option>`;
                                    }).join('')}
                                </select>
                            </div>
                            <div class="form-group p-0 mt-3">
                                <select name="variant_item_requirement[]" data-tmpid="${tmpId}" class="variant_item_requirement select2InsideModal form-select w-100"
                                    style="width: 100% !important;" required>
                                    <option selected disabled>Pilih Variant</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-1 d-flex align-items-center justify-content-center">
                            <button type="button" class="btn btn-danger btn-sm remove_specific_item">Remove</button>
                        </div>
                    </div>
                `;
                } else {
                    newItem = `
                    <div class="row row_condition_purchase_requirement">
                        <div class="col-3 ">
                            <div class="form-group">
                                <select class="form-select form-control condition_purchase_requirement" data-tmpid="${tmpId}" id="condition_purchase_requirement" name="condition_purchase_requirement[]">
                                    <option selected>AND</option>
                                    <option>OR</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-9 pt-3">
                            <hr class="pembatas">
                        </div>
                    </div>
                    <div class="row specific_item mt-3">
                        <div class="col-3">
                            <input type="number" name="qty_requirement_item[]" class="form-control" placeholder="Qty" required>
                        </div>
                        <div class="col-1 d-flex align-items-center justify-content-center">
                            <strong class="text-muted">Of</strong>
                        </div>
                        <div class="col-7 mt-2">
                            <div class="form-group p-0">
                                <select name="item_requirement[]" data-tmpid="${tmpId}" class="select2InsideModal item_requirement form-select w-100" style="width: 100% !important;" required>
                                    <option selected disabled>Pilih Item</option>
                                    ${listProductBaseOnOutlet.map(function(item) {
                                        return `<option value="${item.name}">${item.name}</option>`;
                                    }).join('')}
                                </select>
                            </div>
                            <div class="form-group p-0 mt-3">
                                <select name="variant_item_requirement[]" data-tmpid="${tmpId}" class="variant_item_requirement select2InsideModal form-select w-100"
                                    style="width: 100% !important;" required>
                                    <option selected disabled>Pilih Variant</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-1 d-flex align-items-center justify-content-center">
                            <button type="button" class="btn btn-danger btn-sm remove_specific_item">Remove</button>
                        </div>
                    </div>
                `;
                }

                _totalSpesificItemPurchaseRequirement++;

                $('#specific_item_list').append(newItem);
                initializeSelect2();
            });

            $('#outlet_id').on('select2:select select2:unselect', function(e) {
                // hapus list item requirement
                $('.specific_item').remove();
                $('.condition_purchase_requirement').remove();
                $('.pembatas').remove();
                _totalSpesificItemPurchaseRequirement = 0;
                // Ambil semua nilai yang masih dipilih
                var selectedOptions = $(this).val();
                outletTerpilih = selectedOptions;

                // Panggil AJAX hanya jika ada nilai yang dipilih
                if (selectedOptions && selectedOptions.length > 0) {
                    $.ajax({
                        url: `{{ route('library/product/getProductByOutlet') }}`, // URL endpoint Laravel
                        type: 'GET',
                        data: {
                            idOutlet: selectedOptions // Kirim data array ke server
                        },
                        success: function(response) {
                            listProductBaseOnOutlet = response;
                            // Lakukan sesuatu dengan data produk (response)
                        },
                        error: function(xhr, status, error) {
                            console.error("Terjadi kesalahan:", error);
                        }
                    });

                    getCategoryByOutlet();
                    // initializeSalesTypeSelect();
                    toggleSalesTypeSelect();
                } else {
                    console.log('Tidak ada outlet yang dipilih');
                    // Lakukan sesuatu jika tidak ada outlet yang dipilih (opsional)
                }
            });

            $('#modal_action').on('select2:select', '.item_requirement', function(e) {
                let productValue = $(this).val();
                let tmpIdSelect = $(this).data('tmpid');
                let namaProduct = $(this).find(":selected").text();

                let variantSelect = $(`.variant_item_requirement[data-tmpid="${tmpIdSelect}"]`);
                let baseUrl =
                    `{{ route('library/product/findVariantByProductName', ':name') }}`;
                let url = baseUrl.replace(':name', productValue);

                if (productValue) {
                    // Lakukan AJAX
                    $.ajax({
                        url: url,
                        type: 'GET',
                        data: {
                            idOutlet: outletTerpilih
                        },
                        success: function(response) {
                            console.log(response)
                            variantSelect.empty();
                            if (outletTerpilih.length > 1) {
                                // Langkah 1: Buat objek untuk menghitung jumlah kemunculan nama
                                var nameCount = response.reduce((acc, item) => {
                                    acc[item.name] = (acc[item.name] || 0) + 1;
                                    return acc;
                                }, {});

                                // Langkah 2: Filter response untuk mendapatkan item yang memiliki nama muncul lebih dari 1 kali
                                var duplicates = response.filter(item => nameCount[item.name] >
                                    1);

                                // Langkah 3: Ambil salah satu dari setiap nama yang muncul lebih dari sekali
                                var uniqueDuplicates = duplicates.reduce((acc, item) => {
                                    if (!acc.some(dup => dup.name === item.name)) {
                                        acc.push(
                                            item
                                        ); // Ambil salah satu item dari setiap nama
                                    }
                                    return acc;
                                }, []);

                                variantSelect.append(
                                    '<option value="all" selected>All Variant</option>'
                                );

                                variantSelect.removeAttr('disabled');
                                if (uniqueDuplicates.length != 0) {
                                    uniqueDuplicates.forEach(function(item, index) {
                                        variantSelect.append(
                                            `<option value="${item.name}" >${item.name}</option>`
                                        );
                                    })
                                }
                            } else {
                                if (response.length == 1 && response[0].name == namaProduct) {
                                    variantSelect.append(
                                        // `<option value="${response[0].name}" disabled selected>Tidak Punya Varian</option>`
                                        `<option value="${response[0].name}" selected>All Varian</option>`
                                    );

                                    variantSelect.prop('disabled', false);
                                    variantSelect.prop('required', false);
                                } else {
                                    variantSelect.append(
                                        '<option value="all" selected>All Variant</option>'
                                    );


                                    $.each(response, function(key, variant) {
                                        variantSelect.append('<option value="' + variant
                                            .name +
                                            '">' + variant.name + '</option>');
                                    });
                                    // variantSelect.prop('disabled', false);
                                    variantSelect.prop('required', true);
                                }
                            }

                        },
                        error: function() {
                            console.error('Gagal mengambil data variant');
                        }
                    });
                } else {
                    // Reset select varian
                    variantSelect.empty();
                    variantSelect.append('<option disabled selected>Pilih Variant</option>');
                    variantSelect.prop('disabled', true);
                }
            });


            // Handle click event untuk menghapus item tertentu
            $(document).on('click', '.remove_specific_item', function() {
                // Hapus row dengan class `specific_item`
                const specificItem = $(this).closest('.specific_item');

                // Hapus row di atas elemen `specific_item`
                const previousRow = specificItem.prev('.row_condition_purchase_requirement');
                const nextRow = specificItem.next('.row_condition_purchase_requirement');

                if (previousRow.length) {
                    previousRow.remove();
                } else {
                    if (nextRow.length) {
                        nextRow.remove();
                    }
                }

                _totalSpesificItemPurchaseRequirement--;

                // Hapus elemen `specific_item`
                specificItem.remove();

            });

            $('#category_requirement').off().on('change', function(e) {
                console.log(e)
                const selectedValue = $(this).val();
                console.log(selectedValue);
                if (selectedValue) {
                    $('#btnPurchasRequirementNext').removeAttr('disabled');
                } else {
                    $('#btnPurchasRequirementNext').attr('disabled', 'disabled');
                }
            })

            $('#btnPurchasRequirementNext').on('click', function(e) {
                const target = $(this).data('target');

                e.preventDefault(); // Mencegah submit form atau aksi default tombol
                let isValid = true; // Flag untuk validasi

                $('#collapseTwo input[required], #collapseTwo select[required]').each(function() {
                    if ($(this).val() === '' || $(this).val() === null) {
                        console.log($(this).val())
                        console.log($(this))
                        isValid = false;
                        $(this).addClass('is-invalid'); // Tambahkan kelas untuk styling kesalahan
                        $(this).focus(); // Fokus pada elemen yang belum terisi
                        return false; // Hentikan loop setelah menemukan elemen kosong
                    } else {
                        $(this).removeClass('is-invalid'); // Hapus kelas jika sudah valid
                    }
                });

                if (isValid) {
                    // Jika valid, lakukan aksi selanjutnya
                    $(target).collapse('show'); // Tampilkan accordion berikutnya
                    $("#collapseTwo").collapse('hide'); // Sembunyikan accordion saat ini
                } else {
                    showToast("error", "Harap lengkapi semua input yang wajib diisi.");
                }

            })

            // Jalankan fungsi saat radio button diklik
            $("input[name='requirement_type']").on("change", function() {
                togglePurchaseRequirementType();
            });


            //handle radio button promo information
            $('input[name="promoType"]').on('change', function() {
                const selectedValue = $(this).val();
            });

            $('input[name="promo_type"]').on('change', function() {
                // Mendapatkan nilai yang dipilih
                const selectedValue = $(this).val();

                // Menampilkan feedback atau melakukan tindakan berdasarkan nilai yang dipilih
                if (selectedValue == "discount") {
                    $('#reward-discount').removeClass('d-none');
                    $('#reward-discount').empty();

                    $('#reward-free-item').addClass('d-none');
                    $('.specific_item_reward').remove();

                    let html = `
                    <div class="form-group">
                        <label>Discount Amount</label>
                        <div class="col-12 d-flex">
                            <input type="text" name="amount" id="amountInput" value="{{ $data->amount }}" class="form-control"
                                placeholder="Amount" aria-label="Amount" style="height: 36px !important; width: 80% !important;">
                            <div class="selectgroup ms-4">
                                <label class="selectgroup-item">
                                    <input type="radio" name="satuan" id="satuan-rupiah" value="rupiah" class="selectgroup-input"
                                        checked>
                                    <span class="selectgroup-button">Rp</span>
                                </label>
                                <label class="selectgroup-item">
                                    <input type="radio" name="satuan" id="satuan-percent" value="percent"
                                        class="selectgroup-input">
                                    <span class="selectgroup-button">%</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    `

                    $('#reward-discount').append(html);

                    satuanClicked();
                } else {
                    $('#reward-discount').empty();
                    $('#reward-discount').addClass('d-none');

                    $('#reward-free-item').removeClass('d-none');
                    $('.specific_item_reward').remove();
                }
            });

            $('#add_specific_item_reward').on('click', function() {
                $('#btnRewardNext').removeAttr('disabled');
                let tmpId = generateRandomID();

                var newItem = '';
                if (_totalRewardItem < 1) {
                    newItem = `
                    <div class="row specific_item_reward mt-3">
                        <div class="col-3">
                            <input type="number" name="qty_reward_item[]" class="form-control" placeholder="Qty" min="1" required>
                        </div>
                        <div class="col-1 d-flex align-items-center justify-content-center">
                            <strong class="text-muted">Of</strong>
                        </div>
                        <div class="col-7 mt-2">
                            <div class="form-group p-0">
                                <select name="item_reward[]" data-tmpid="${tmpId}" class="item_reward select2InsideModal form-select w-100" style="width: 100% !important;" required>
                                    <option selected disabled>Pilih Item</option>
                                    ${listProductBaseOnOutlet.map(function(item) {
                                        return `<option value="${item.name}">${item.name}</option>`;
                                    }).join('')}
                                </select>
                            </div>
                            <div class="form-group p-0 mt-3">
                                <select name="variant_item_reward[]" data-tmpid="${tmpId}" class="variant_item_reward select2InsideModal form-select w-100"
                                    style="width: 100% !important;" disabled>
                                    <option selected disabled>Pilih Variant</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-1 d-flex align-items-center justify-content-center">
                            <button type="button" class="btn btn-danger btn-sm remove_specific_item_reward">Remove</button>
                        </div>
                    </div>
                `;
                } else {
                    newItem = `
                    <div class="row row_condition_purchase_reward">
                        <div class="col-3 ">
                            <div class="form-group">
                                <select class="form-select form-control condition_purchase_reward" data-tmpid="${tmpId}" id="condition_purchase_reward" name="condition_purchase_reward[]">
                                    <option selected>AND</option>
                                    <option>OR</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-9 pt-3">
                            <hr class="pembatas">
                        </div>
                    </div>
                    <div class="row specific_item mt-3">
                        <div class="col-3">
                            <input type="number" name="qty_reward_item[]" class="form-control" placeholder="Qty" min="1" required>
                        </div>
                        <div class="col-1 d-flex align-items-center justify-content-center">
                            <strong class="text-muted">Of</strong>
                        </div>
                        <div class="col-7 mt-2">
                            <div class="form-group p-0">
                                <select name="item_reward[]" data-tmpid="${tmpId}" class="select2InsideModal item_reward form-select w-100" style="width: 100% !important;" required>
                                    <option selected disabled>Pilih Item</option>
                                    ${listProductBaseOnOutlet.map(function(item) {
                                        return `<option value="${item.name}">${item.name}</option>`;
                                    }).join('')}
                                </select>
                            </div>
                            <div class="form-group p-0 mt-3">
                                <select name="variant_item_reward[]" data-tmpid="${tmpId}" class="variant_item_reward select2InsideModal form-select w-100"
                                    style="width: 100% !important;" disabled>
                                    <option selected disabled>Pilih Variant</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-1 d-flex align-items-center justify-content-center">
                            <button type="button" class="btn btn-danger btn-sm remove_specific_reward">Remove</button>
                        </div>
                    </div>
                `;
                }

                _totalRewardItem++;

                $('#reward-free-item').append(newItem);
                initializeSelect2();
            })

            $('#modal_action').on('select2:select', '.item_reward', function(e) {
                let productValue = $(this).val();
                let tmpIdSelect = $(this).data('tmpid');
                let namaProduct = $(this).find(":selected").text();

                let variantSelect = $(`.variant_item_reward[data-tmpid="${tmpIdSelect}"]`);
                let baseUrl =
                    `{{ route('library/product/findVariantByProductName', ':name') }}`;
                let url = baseUrl.replace(':name', productValue);

                if (productValue) {
                    // Lakukan AJAX
                    $.ajax({
                        url: url,
                        type: 'GET',
                        data: {
                            idOutlet: outletTerpilih
                        },
                        success: function(response) {
                            variantSelect.empty();
                            if (outletTerpilih.length > 1) {
                                // Langkah 1: Buat objek untuk menghitung jumlah kemunculan nama
                                var nameCount = response.reduce((acc, item) => {
                                    acc[item.name] = (acc[item.name] || 0) + 1;
                                    return acc;
                                }, {});

                                // Langkah 2: Filter response untuk mendapatkan item yang memiliki nama muncul lebih dari 1 kali
                                var duplicates = response.filter(item => nameCount[item.name] >
                                    1);

                                // Langkah 3: Ambil salah satu dari setiap nama yang muncul lebih dari sekali
                                var uniqueDuplicates = duplicates.reduce((acc, item) => {
                                    if (!acc.some(dup => dup.name === item.name)) {
                                        acc.push(
                                            item
                                        ); // Ambil salah satu item dari setiap nama
                                    }
                                    return acc;
                                }, []);

                                variantSelect.append(
                                    '<option value="all" selected>All Variant</option>'
                                );
                                if (uniqueDuplicates.length != 0) {
                                    uniqueDuplicates.forEach(function(item, index) {
                                        variantSelect.append(
                                            `<option value="${item.name}" >${item.name}</option>`
                                        );
                                    })
                                }

                                variantSelect.prop('disabled', false);
                            } else {
                                if (response.length == 1 && response[0].name == namaProduct) {
                                    variantSelect.append(
                                        // `<option value="${response[0].name}" disabled selected>Tidak Punya Varian</option>`
                                        `<option value="${response[0].name}" selected>All Variant</option>`
                                    );

                                    variantSelect.prop('disabled', false);
                                    variantSelect.prop('required', false);
                                } else {
                                    variantSelect.append(
                                        '<option value="all" selected>All Variant</option>'
                                    );


                                    $.each(response, function(key, variant) {
                                        variantSelect.append('<option value="' + variant
                                            .name +
                                            '">' + variant.name + '</option>');
                                    });
                                    variantSelect.prop('disabled', false);
                                    variantSelect.prop('required', true);
                                }
                            }

                        },
                        error: function() {
                            console.error('Gagal mengambil data variant');
                        }
                    });
                } else {
                    // Reset select varian
                    variantSelect.empty();
                    variantSelect.append('<option disabled selected>Pilih Variant</option>');
                    variantSelect.prop('disabled', true);
                }
            });

            var startDate = moment().startOf('day');
            var endDate = moment().endOf('day');

            $('#schedule_promo').daterangepicker({
                startDate: startDate,
                endDate: endDate,
                minDate: moment(),
                ranges: {
                    // 'Today': [moment(), moment()],
                    // 'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    // 'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    // 'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    // 'This Month': [moment().startOf('month'), moment().endOf('month')],
                    // 'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1,
                    //     'month').endOf('month')]
                    '1 Week': [moment(), moment().add(1, 'weeks')],
                    '2 Weeks': [moment(), moment().add(2, 'weeks')],
                    '3 Weeks': [moment(), moment().add(3, 'weeks')],
                    '1 Month': [moment(), moment().add(1, 'months')],
                    '3 Months': [moment(), moment().add(3, 'months')],
                    '6 Months': [moment(), moment().add(6, 'months')],
                    '1 Year': [moment(), moment().add(1, 'years')]
                },
                "linkedCalendars": false,
                "autoUpdateInput": false,
                "showCustomRangeLabel": true,
                // "startDate": "12/30/2024",
                // "endDate": "01/05/2025",
                "drops": "auto",
                "buttonClasses": "btn btn-primary"
            }, function(start, end, label) {
                $('#schedule_promo').val(start.format('YYYY/MM/DD') + ' - ' + end.format('YYYY/MM/DD'));
                console.log('New date range selected: ' + start.format('YYYY-MM-DD') + ' to ' + end.format(
                    'YYYY-MM-DD') + ' (predefined range: ' + label + ')');

                startDate = start;
                endDate = end;
            });

            // Fungsi untuk mengubah tanggal
            $('#prevDate').on('click', function() {
                startDate.subtract(1, 'days');
                endDate.subtract(1, 'days');

                console.log(startDate);
                console.log(endDate);
                $('#schedule_promo').data('daterangepicker').setStartDate(startDate);
                $('#schedule_promo').data('daterangepicker').setEndDate(endDate);

                $('#schedule_promo').val(startDate.format('YYYY/MM/DD') + ' - ' + endDate.format(
                    'YYYY/MM/DD'));
            });

            $('#nextDate').on('click', function() {
                startDate.add(1, 'days');
                endDate.add(1, 'days');

                console.log(startDate)
                console.log(endDate)
                $('#schedule_promo').data('daterangepicker').setStartDate(startDate);
                $('#schedule_promo').data('daterangepicker').setEndDate(endDate);

                $('#schedule_promo').val(startDate.format('YYYY/MM/DD') + ' - ' + endDate.format(
                    'YYYY/MM/DD'));
            });

            $('.ranges li').addClass('btn btn-primary w-75 ms-3 mt-2');

            // Event untuk mengosongkan rentang saat daterangepicker dibuka
            $('#schedule_promo').on('show.daterangepicker', function(ev, picker) {
                picker.setStartDate(moment().startOf(
                    'day')); // Set start date ke hari ini atau tanggal lain
                picker.setEndDate(moment().startOf('day')); // Set end date ke hari ini atau tanggal lain
            });

            $('#promo_time_period').change(function() {
                if ($(this).is(':checked')) {
                    $('#row_promo_time_periode').removeClass('d-none'); // Menghapus kelas d-none
                    $('#schedule_promo').attr('required', true);
                } else {
                    $('#row_promo_time_periode').addClass('d-none'); // Menambahkan kelas d-none
                    $('#schedule_promo').removeAttr('required');
                }
            });

            // Ketika checkbox "Select All" dicentang atau tidak dicentang
            $('#check_all_day').on('change', function() {
                // Jika checkbox "Select All" dicentang
                if ($(this).is(':checked')) {
                    // Centang semua checkbox hari
                    $('.day_check').not(this).prop('checked', true);
                } else {
                    // Jika tidak dicentang, hilangkan centang dari semua checkbox hari
                    $('.day_check').not(this).prop('checked', false);
                }
            });

            // Ketika salah satu checkbox hari dicentang atau tidak dicentang
            $('.day_check').on('change', function() {
                // Jika ada checkbox hari yang tidak dicentang
                if ($('.day_check:checked').length === $('.day_check').length) {
                    // Centang checkbox "Select All"
                    $('#check_all_day').prop('checked', true);
                } else {
                    // Jika tidak, hilangkan centang dari checkbox "Select All"
                    $('#check_all_day').prop('checked', false);
                }
            });


            @if ($data->id)
                $('#btnPurchasRequirementNext').removeAttr('disabled');
                $('#btnRewardNext').removeAttr('disabled');
                let jsonProduct = '{!! json_encode($productRequirement) !!}';
                let listProduct = JSON.parse(jsonProduct);

                let salesType = '{!! $data->sales_type !!}';
                let dataSalesType = JSON.parse(salesType);

                if (dataSalesType.length > 0) {
                    $('#salesTypeSelect').empty();

                    let html = `<div class="form-group p-0">
                                <label for="sales_type_choose">Sales Type<span
                                        class="text-danger ">*</span></label>
                                <select name="sales_type_choose[]" id="sales_type_choose"
                                    class="select2MultipleInsideModal form-select w-100"
                                    style="width: 100% !important;"  multiple disabled>
                                    ${dataSalesType.map(function(item) {
                                        return `<option value="${item}" selected disabled>${item}</option>`;
                                    }).join('')}
                                </select>
                                <small id="sales_type_choose_feedback" class="d-none text-danger"><i>*Pilih
                                        Sales Type Terlebih
                                        Dahulu</i></small>
                            </div>`

                    $('#salesTypeSelect').append(html);

                    intializeSalesTypeSelect2();
                }



                @if ($data->purchase_requirement == 'any_item')

                    listProduct.forEach(function(item, index) {
                        let html = '';

                        item.forEach(function(product, indexProduct) {
                            if (index == 0 && indexProduct == 0) {
                                html = `
                                        <div class="row specific_item mt-3">
                                            <div class="col-3">
                                                <input type="number" name="qty_requirement_item[]" class="form-control" placeholder="Qty" min="1" value="${product.quantity}"  disabled>
                                            </div>
                                            <div class="col-1 d-flex align-items-center justify-content-center">
                                                <strong class="text-muted">Of</strong>
                                            </div>
                                            <div class="col-7 mt-2">
                                                <div class="form-group p-0">
                                                    <select name="item_requirement[]"  class="select2InsideModal item_requirement form-select w-100" style="width: 100% !important;"  disabled>
                                                        <option selected disabled>${product.product}</option>
                                                    </select>
                                                </div>
                                                <div class="form-group p-0 mt-3">
                                                    <select name="variant_item_requirement[]" class="variant_item_requirement select2InsideModal form-select w-100"
                                                        style="width: 100% !important;" disabled>
                                                        <option selected disabled>${product.variant}</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    `;

                            } else if (indexProduct > 0) {
                                html = `
                                    <div class="row row_condition_purchase_requirement">
                                        <div class="col-3 ">
                                            <div class="form-group">
                                                <select class="form-select form-control condition_purchase_requirement" id="condition_purchase_requirement" name="condition_purchase_requirement[]" disabled>
                                                    <option selected disabled>OR</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-9 pt-3">
                                            <hr class="pembatas">
                                        </div>
                                    </div>
                                    <div class="row specific_item mt-3">
                                        <div class="col-3">
                                            <input type="number" name="qty_requirement_item[]" class="form-control" placeholder="Qty" min="1" value="${product.quantity}"  disabled>
                                        </div>
                                        <div class="col-1 d-flex align-items-center justify-content-center">
                                            <strong class="text-muted">Of</strong>
                                        </div>
                                        <div class="col-7 mt-2">
                                            <div class="form-group p-0">
                                                <select name="item_requirement[]" class="select2InsideModal item_requirement form-select w-100" style="width: 100% !important;" disabled>
                                                    <option selected disabled>${product.product}</option>
                                                </select>
                                            </div>
                                            <div class="form-group p-0 mt-3">
                                                <select name="variant_item_requirement[]" class="variant_item_requirement select2InsideModal form-select w-100"
                                                    style="width: 100% !important;" disabled>
                                                    <option selected disabled>${product.variant}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            } else {
                                html = `
                                    <div class="row row_condition_purchase_requirement">
                                        <div class="col-3 ">
                                            <div class="form-group">
                                                <select class="form-select form-control condition_purchase_requirement" id="condition_purchase_requirement" name="condition_purchase_requirement[]" disabled>
                                                    <option disabled selected>AND</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-9 pt-3">
                                            <hr class="pembatas">
                                        </div>
                                    </div>
                                    <div class="row specific_item mt-3">
                                        <div class="col-3">
                                            <input type="number" name="qty_requirement_item[]" class="form-control" placeholder="Qty" min="1"  disabled value="${product.quantity}">
                                        </div>
                                        <div class="col-1 d-flex align-items-center justify-content-center">
                                            <strong class="text-muted">Of</strong>
                                        </div>
                                        <div class="col-7 mt-2">
                                            <div class="form-group p-0">
                                                <select name="item_requirement[]"  class="select2InsideModal item_requirement form-select w-100" style="width: 100% !important;"  disabled>
                                                    <option selected disabled>${product.product}</option>
                                                </select>
                                            </div>
                                            <div class="form-group p-0 mt-3">
                                                <select name="variant_item_requirement[]" class="variant_item_requirement select2InsideModal form-select w-100"
                                                    style="width: 100% !important;" disabled>
                                                    <option selected disabled>${product.variant}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            }

                            $('#specific_item_list').append(html);
                        });
                    });
                @else
                    $('#any_item_from_category').empty();

                    let html = `<div class="row" id="row_any_item_from_category">
                            <div class="col-3">
                                <input type="number" name="qty_requirement_category_item" class="form-control" min="1"
                                    placeholder="Qty" value="${listProduct.quantity}" disabled required>
                            </div>
                            <div class="col-1 d-flex align-items-center justify-content-center">
                                <strong class="text-muted">Of</strong>
                            </div>
                            <div class="col-8 mt-2">
                                <div class="form-group p-0">
                                    <select name="category_requirement" id="category_requirement" class="select2InsideModal form-select w-100"
                                        style="width: 100% !important;"  disabled>
                                        <option selected disabled>${listProduct.category}</option>
                                    </select>
                                </div>
                            </div>
                        </div>`;
                    $("#any_item_from_category").append(html);
                @endif

                @if ($data->type == 'discount')
                    let jsonReward = '{!! $data->reward !!}';
                    let listReward = JSON.parse(jsonReward)

                    // Menentukan apakah indexReward adalah "rupiah"  
                    let isRupiahChecked = (Object.keys(listReward[0])[0] === "rupiah") ? 'checked' : '';
                    let isPercentChecked = (Object.keys(listReward[0])[0] === "percent") ? 'checked' : '';

                    console.log(jsonReward)
                    console.log(listReward)
                    let htmlReward = `
                        <div class="form-group">
                            <label>Discount Amount</label>
                            <div class="col-12 d-flex">
                                <input type="text" name="amount" id="amountInput" value="${formatRupiah(listReward[0].rupiah.toString(), "Rp. ")}" class="form-control"
                                    placeholder="Amount" aria-label="Amount" style="height: 36px !important; width: 80% !important;" disabled>
                                <div class="selectgroup ms-4">
                                    <label class="selectgroup-item">
                                        <input type="radio" name="satuan" id="satuan-rupiah" value="rupiah" class="selectgroup-input" ${isRupiahChecked} disabled>
                                        <span class="selectgroup-button">Rp</span>
                                    </label>
                                    <label class="selectgroup-item">
                                        <input type="radio" name="satuan" id="satuan-percent" value="percent"
                                            class="selectgroup-input" ${isPercentChecked} disabled>
                                        <span class="selectgroup-button">%</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        `;

                    $('#reward-discount').append(htmlReward);
                @else
                    let jsonReward = '{!! json_encode($rewardFreeItem) !!}';
                    let listReward = JSON.parse(jsonReward);
                    $('#reward-free-item').removeClass('d-none');

                    listReward.forEach(function(item, index) {
                        let htmlRewardFreeItem = '';

                        console.log(item);
                        item.forEach(function(reward, indexReward) {
                            console.log(reward);
                            if (index == 0 && indexReward == 0) {
                                htmlRewardFreeItem = `
                                    <div class="row specific_item mt-3">
                                        <div class="col-3">
                                            <input type="number" name="qty_reward_item[]" class="form-control" placeholder="Qty" min="1" value="${reward.quantity}" disabled>
                                        </div>
                                        <div class="col-1 d-flex align-items-center justify-content-center">
                                            <strong class="text-muted">Of</strong>
                                        </div>
                                        <div class="col-7 mt-2">
                                            <div class="form-group p-0">
                                                <select name="item_reward[]" class="select2InsideModal item_reward form-select w-100" style="width: 100% !important;" disabled>
                                                    <option selected disabled>${reward.product}</option>
                                                </select>
                                            </div>
                                            <div class="form-group p-0 mt-3">
                                                <select name="variant_item_reward[]" class="variant_item_reward select2InsideModal form-select w-100"
                                                    style="width: 100% !important;" disabled>
                                                    <option selected disabled>${reward.variant}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                `;

                            } else if (indexReward > 0) {
                                htmlRewardFreeItem = `
                                    <div class="row row_condition_purchase_reward">
                                        <div class="col-3 ">
                                            <div class="form-group">
                                                <select class="form-select form-control condition_purchase_reward"  id="condition_purchase_reward" name="condition_purchase_reward[]" disabled>
                                                    <option selected disabled>OR</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-9 pt-3">
                                            <hr class="pembatas">
                                        </div>
                                    </div>
                                    <div class="row specific_item mt-3">
                                        <div class="col-3">
                                            <input type="number" name="qty_reward_item[]" class="form-control" placeholder="Qty" value="${reward.quantity}" min="1" disabled>
                                        </div>
                                        <div class="col-1 d-flex align-items-center justify-content-center">
                                            <strong class="text-muted">Of</strong>
                                        </div>
                                        <div class="col-7 mt-2">
                                            <div class="form-group p-0">
                                                <select name="item_reward[]" class="select2InsideModal item_reward form-select w-100" style="width: 100% !important;" disabled>
                                                    <option selected disabled>${reward.product}</option>
                                                </select>
                                            </div>
                                            <div class="form-group p-0 mt-3">
                                                <select name="variant_item_reward[]" class="variant_item_reward select2InsideModal form-select w-100"
                                                    style="width: 100% !important;" disabled>
                                                    <option selected disabled>${reward.variant}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                `;

                            } else {
                                htmlRewardFreeItem = `
                                    <div class="row row_condition_purchase_reward">
                                        <div class="col-3 ">
                                            <div class="form-group">
                                                <select class="form-select form-control condition_purchase_reward"  id="condition_purchase_reward" name="condition_purchase_reward[]" disabled>
                                                    <option disabled selected>AND</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-9 pt-3">
                                            <hr class="pembatas">
                                        </div>
                                    </div>
                                    <div class="row specific_item mt-3">
                                        <div class="col-3">
                                            <input type="number" name="qty_reward_item[]" class="form-control" placeholder="Qty" value="${reward.quantity}" min="1" disabled>
                                        </div>
                                        <div class="col-1 d-flex align-items-center justify-content-center">
                                            <strong class="text-muted">Of</strong>
                                        </div>
                                        <div class="col-7 mt-2">
                                            <div class="form-group p-0">
                                                <select name="item_reward[]" class="select2InsideModal item_reward form-select w-100" style="width: 100% !important;" disabled>
                                                    <option selected disabled>${reward.product}</option>
                                                </select>
                                            </div>
                                            <div class="form-group p-0 mt-3">
                                                <select name="variant_item_reward[]" class="variant_item_reward select2InsideModal form-select w-100"
                                                    style="width: 100% !important;" disabled>
                                                    <option selected disabled>${reward.variant}</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            }

                            $('#reward-free-item').append(htmlRewardFreeItem);
                        });
                    });
                @endif

                @if ($data->multiple == 1)
                    // Menandai checkbox  
                    $('#apply_multiple').prop('checked', true);
                @endif

                @if ($data->promo_date_periode_start != null)
                    let promoStartDate = '{!! $data->promo_date_periode_start !!}';
                    let promoEndDate = '{!! $data->promo_date_periode_end !!}';

                    let dataPromoStartDate = moment(promoStartDate);
                    let dataPromoEndDate = moment(promoEndDate);

                    startDate = dataPromoStartDate;
                    endDate = dataPromoEndDate;

                    $('#schedule_promo').val(dataPromoStartDate.format('YYYY/MM/DD') + ' - ' + dataPromoEndDate
                        .format('YYYY/MM/DD'));

                    let promoStartHour = '{!! $data->promo_time_periode_start !!}';
                    let promoEndHour = '{!! $data->promo_time_periode_end !!}';
                    // Memasukkan nilai ke dalam input jam
                    $('#start_hour').val(promoStartHour.substring(0, 5));
                    $('#end_hour').val(promoEndHour.substring(0, 5)); 

                    $('#promo_time_period').prop('checked', true);
                    $('#row_promo_time_periode').removeClass('d-none'); // Menghapus kelas d-none


                    // Data day_allowed yang diambil dari database
                    var dayAllowed = '{!! $data->day_allowed !!}';

                    // Parse string JSON menjadi array  
                    var days = JSON.parse(dayAllowed);

                    // Tandai checkbox yang sesuai  
                    $.each(days, function(index, day) {
                        // Tandai checkbox berdasarkan value  
                        $('input.day_check[value="' + day + '"]').prop('checked', true);
                    });

                    if (days.length == 7) {
                        $('#check_all_day').prop('checked', true);
                    }
                @endif
            @endif

        });
    </script>


</x-modal>
