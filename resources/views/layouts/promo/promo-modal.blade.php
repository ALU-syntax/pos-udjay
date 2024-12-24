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
<x-modal title="Tambah Promo" addStyle="modal-lg" action="{{ $action }}" method="POST">
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

                    <button type="button" class="btn btn-round btn-outline-secondary mt-3 next-btn"
                        id="backToCollapseOne" data-target="#collapseOne">Previous</button>
                    <button type="button" class="btn btn-round btn-primary mt-3 next-btn"
                        id="btnPurchasRequirementNext" data-target="#collapseThree" disabled>Next</button>
                </div>
            </div>
        </div>

        <!-- Accordion Level 3 -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingThree">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree" disabled>
                    Level 3
                </button>
            </h2>
            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree"
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

        function toggleSalesTypeSelect() {
            if ($('#specific_sales_type').is(':checked')) {
                $('#salesTypeSelect').removeClass('d-none'); // Tampilkan select
            } else {
                $('#salesTypeSelect').addClass('d-none'); // Sembunyikan select
            }
        }

        function tooglePurchaseRequirementType() {

        }

        $(document).ready(function() {
            // Pengecekan awal saat halaman dimuat
            toggleSalesTypeSelect();

            // Event listener ketika radio button berubah
            $('input[name="sales_type"]').on('change', function() {
                toggleSalesTypeSelect();
            });

            $(".select2InsideModal").select2({
                dropdownParent: $("#modal_action")
            });

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
                // Template HTML untuk elemen baru
                var newItem = '';
                if (_totalSpesificItemPurchaseRequirement < 1) {
                    newItem = `
                        <div class="row specific_item mt-3">
                            <div class="col-3">
                                <input type="number" name="qty_requirement_item[]" class="form-control" placeholder="Qty" required>
                            </div>
                            <div class="col-1 d-flex align-items-center justify-content-center">
                                <strong class="text-muted">Of</strong>
                            </div>
                            <div class="col-7 mt-2">
                                <div class="form-group p-0">
                                    <select name="item_requirement[]" class="select2InsideModal form-select w-100" style="width: 100% !important;" required>
                                        <option disabled>Pilih Item</option>
                                        <!-- Anda bisa mengganti dengan opsi dinamis -->
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                                        @endforeach
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
                                    <select class="form-select form-control" id="condition_purchase_requirement" name="condition_purchase_requirement[]">
                                        <option selected>AND</option>
                                        <option>OR</option>
                                    </select>
                                    
                                    </div>
                            </div>
                            <div class="col-9 pt-3">
                                <hr>
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
                                    <select name="item_requirement[]" class="select2InsideModal form-select w-100" style="width: 100% !important;" required>
                                        <option disabled>Pilih Item</option>
                                        <!-- Anda bisa mengganti dengan opsi dinamis -->
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                                        @endforeach
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

                // Tambahkan elemen baru ke dalam container
                $('#specific_item_list').append(newItem);

                // Reinitialize Select2 jika Anda menggunakan plugin Select2
                $(".select2InsideModal").select2({
                    dropdownParent: $("#modal_action")
                });
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
                }else{
                    if(nextRow.length){
                        nextRow.remove();
                    }
                }

                // Hapus elemen `specific_item`
                specificItem.remove();

            });

            $('#btnPurchasRequirementNext').on('click', function() {
                
            })

            // Disable proceeding to the next accordion unless the current task is fulfilled
            // $('.next-btn').on('click', function() {
            //     const target = $(this).data('target');
            //     const currentTask = $(this).closest('.accordion-body').find('input, textarea');
            //     console.log(target);
            //     console.log(currentTask)
            //     console.log(currentTask.val())
            //     // if (!$('input[name="promoType"]:checked').length) {
            //     //     alert('Please select a promo type before proceeding.');
            //     //     return false;
            //     // }

            //     if (currentTask.val().trim() === '') {
            //         if (currentTask.type)

            //             alert('Please complete the task before proceeding.');
            //     } else {
            //         // Open the next accordion
            //         $(target).collapse('show');
            //     }
            // });

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
