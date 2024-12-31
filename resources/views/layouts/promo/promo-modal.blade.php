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
<x-modal title="Tambah Promo" addStyle="modal-xl" action="{{ $action }}" method="POST">
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
                                        <input type="radio" id="promo_type" name="promo_type" value="discount">
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
                                        <input type="radio" id="promo_type" name="promo_type" value="free-item">
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
                                        placeholder="Masukan nama promo" required>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12" @if ($data->id) hidden @endif>
                                <div class="form-group p-0">
                                    <label for="outlet_id">Outlet <span class="text-danger ">*</span></label>
                                    <select name="outlet_id[]" id="outlet_id"
                                        class="select2MultipleInsideModal form-select w-100"
                                        style="width: 100% !important;" required multiple>
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
                                                    @if ($data->id) @if ($data->required == 1)checked @endif
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
                                                    @if ($data->id) @if ($data->required == 0)checked @endif
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
                                    <div class="form-group p-0">
                                        <label for="sales_type_choose">Sales Type<span
                                                class="text-danger ">*</span></label>
                                        <select name="sales_type_choose[]" id="sales_type_choose"
                                            class="select2MultipleInsideModal form-select w-100"
                                            style="width: 100% !important;" required multiple>
                                            <option disabled>Pilih Sales Type</option>
                                            @foreach (json_decode($salesTypes) as $salesType)
                                                <option value="{{ $salesType->id }}"
                                                    @if ($data->id == $salesType->id) selected @endif>
                                                    {{ $salesType->name }}</option>
                                            @endforeach
                                        </select>
                                        <small id="sales_type_choose_feedback" class="d-none text-danger"><i>*Pilih
                                                Sales Type Terlebih
                                                Dahulu</i></small>
                                    </div>
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
                                                @if ($data->id) @if ($data->required == 1)checked @endif
                                            @else checked @endif>
                                            <label class="form-check-label"
                                                for="specific_item_requirement">Item</label>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="requirement_type"
                                                id="all_item_from_category_requirement"
                                                @if ($data->id) @if ($data->required == 0)checked @endif
                                                @endif
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

                        <div class="row mt-3">
                            <button type="button" class="btn btn-primary " id="add_specific_item">Add Item</button>
                        </div>
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
                    <p>Final task:</p>
                    <textarea id="task3" class="form-control" placeholder="Enter some text"></textarea>
                    <button type="button" class="btn btn-round btn-outline-secondary mt-3 next-btn"
                        id="backToCollapseOne" data-target="#collapseOne">Previous</button>
                    <button type="button" class="btn btn-round btn-primary mt-3 next-btn"
                        id="btnPurchasRequirementNext" data-session="promo-information" data-target="#collapseThree"
                        disabled>Next</button>
                </div>
            </div>
        </div>

        {{-- Promo Configuration --}}
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingThree">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour" disabled>
                    Promo Configuration
                </button>
            </h2>
            <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingThree"
                data-bs-parent="#accordionExample">
                <div class="accordion-body">
                    <p>Final task:</p>
                    <textarea id="task3" class="form-control" placeholder="Enter some text"></textarea>
                    <button class="btn btn-success mt-3 finish-btn">Finish</button>
                </div>
            </div>
        </div>
    </div>


    <script>
        var _totalSpesificItemPurchaseRequirement = 0;
        var listProductBaseOnOutlet = [];
        var outletTerpilih = [];

        function toggleSalesTypeSelect() {
            if ($('#specific_sales_type').is(':checked')) {
                $('#salesTypeSelect').removeClass('d-none'); // Tampilkan select
            } else {
                $('#salesTypeSelect').addClass('d-none'); // Sembunyikan select
            }
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
                                <input type="number" name="qty_requirement_item[]" class="form-control"
                                    placeholder="Qty" required>
                            </div>
                            <div class="col-1 d-flex align-items-center justify-content-center">
                                <strong class="text-muted">Of</strong>
                            </div>
                            <div class="col-8 mt-2">
                                <div class="form-group p-0">
                                    <select name="item_requirement[]" class="select2InsideModal form-select w-100"
                                        style="width: 100% !important;" required>
                                        <option selected disabled>Pilih Category</option>
                                        <!-- Anda bisa mengganti dengan opsi dinamis -->
                                        @foreach ($categorys as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group p-0 mt-3">
                                    <select name="item_requirement[]" class="select2InsideModal form-select w-100"
                                        style="width: 100% !important;" required>
                                        <option selected disabled>Pilih Category</option>
                                        <!-- Anda bisa mengganti dengan opsi dinamis -->
                                        @foreach ($categorys as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>`;
                $("#any_item_from_category").append(html);
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

            $(".select2MultipleInsideModal").select2({
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

            $("#btnPromoInformationNext").on('click', function() {
                const target = $(this).data('target');
                let isValid = 0; // Flag untuk mengecek apakah semua input sudah valid

                // Loop semua input dan select di dalam collapseOne
                $("#collapseOne input, #collapseOne select").each(function() {
                    const $this = $(this);

                    if ($this.is("select")) {
                        if ($this.attr('id') == 'sales_type_choose') {
                            if ($('input[name="sales_type"]:checked').val() ==
                                'specific_sales_type') {
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

            $('#backToCollapseOne').on('click', function() {
                const target = $(this).data('target');
                $(target).collapse('show');
            });

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
                                    style="width: 100% !important;" disabled>
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
                                    style="width: 100% !important;" disabled>
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
                            console.log(response);
                            listProductBaseOnOutlet = response;
                            // Lakukan sesuatu dengan data produk (response)
                        },
                        error: function(xhr, status, error) {
                            console.error("Terjadi kesalahan:", error);
                        }
                    });
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
                                if(uniqueDuplicates.length != 0){   
                                    uniqueDuplicates.forEach(function(item, index){
                                        variantSelect.append(
                                        `<option value="${item.name}" >${item.name}</option>`
                                    );
                                    })
                                }
                            }else{
                                if (response.length == 1 && response[0].name == namaProduct) {
                                    variantSelect.append(
                                        `<option value="${response[0].name}" disabled selected>Tidak Punya Varian</option>`
                                    );
    
                                    variantSelect.prop('disabled', true);
                                    variantSelect.prop('required', false);
                                } else {
                                    variantSelect.append(
                                        '<option value="all" selected>Pilih Variant</option>');
    
    
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

            $('#btnPurchasRequirementNext').on('click', function(e) {
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

            // Jalankan fungsi saat radio button diklik
            $("input[name='requirement_type']").on("change", function() {
                togglePurchaseRequirementType();
            });

            // Handle finish button
            $('.finish-btn').on('click', function() {
                const finalTask = $('#task3');

                if (finalTask.val().trim() === '') {
                    alert('Please complete the final task.');
                } else {
                    alert('All tasks completed successfully!');
                    $('#accordionModal').modal('hide');
                }
            });

            //handle radio button promo information
            $('input[name="promoType"]').on('change', function() {
                const selectedValue = $(this).val();
            });

        });
    </script>


</x-modal>
