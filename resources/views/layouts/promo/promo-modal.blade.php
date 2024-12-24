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
                            <div class="row mt-3">
                                <!-- Discount per Item -->
                                <div class="col-md-6">
                                    <label class="radio-card">
                                        <input type="radio" name="promo_type" value="discount">
                                        <div class="radio-label">
                                            <img src="{{ asset('img/icon-discount-item.png') }}" alt="Discount Icon">
                                            <h5>Discount per Item</h5>
                                            <p>Customers get a <strong>discount (by % or amount)</strong> automatically
                                                when
                                                they buy the specified item and quantity.</p>
                                            {{-- <a href="#">Learn More</a> --}}
                                        </div>
                                    </label>
                                </div>
                                <!-- Free Item -->
                                <div class="col-md-6">
                                    <label class="radio-card">
                                        <input type="radio" name="promo_type" value="free-item">
                                        <div class="radio-label">
                                            <img src="{{ asset('img/icon-free-item.png') }}" alt="Free Item Icon">
                                            <h5>Free Item</h5>
                                            <p>Customers get a <strong>free item automatically</strong> when they buy
                                                the
                                                specified item and quantity.</p>
                                            {{-- <a href="#">Learn More</a> --}}
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="form-grup">
                                    <label for="promo-name">Nama Promo</label>
                                    <input type="text" id="promo-name" name="name" class="form-control"
                                        placeholder="Masukan nama promo" required>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12" @if ($data->id) hidden @endif>
                                <div class="form-group p-0">
                                    <label for="outlet_id">Outlet <span class="text-danger ">*</span></label>
                                    <select
                                        @if ($data->id) name="outlet_id" @else name="outlet_id[]" @endif
                                        class="select2InsideModal form-select w-100" style="width: 100% !important;"
                                        required multiple @if ($data->id) hidden @endif>
                                        <option disabled>Pilih Category</option>
                                        @foreach (json_decode($outlets) as $outlet)
                                            <option value="{{ $outlet->id }}"
                                                @if ($data->outlet_id == $outlet->id) selected @endif>
                                                {{ $outlet->name }}</option>
                                        @endforeach
                                    </select>
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
                                                    id="all_sales_type" value="true"
                                                    @if ($data->id) @if ($data->required == 1)checked @endif
                                                @else checked @endif>
                                                <label class="form-check-label" for="requiredYes">All Sales Type</label>
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
                                                value="false">
                                                <label class="form-check-label" for="requiredNo">
                                                    Specific Sales Type
                                                </label> <br>
                                                <small style="color: gray">Choose the selected sales type for this
                                                    promo.</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group p-0">
                                                <label for="outlet_id">Assign Sales Type<span
                                                        class="text-danger ">*</span></label>
                                                <select
                                                    @if ($data->id) name="outlet_id" @else name="outlet_id[]" @endif
                                                    class="select2InsideModal form-select w-100"
                                                    style="width: 100% !important;" required multiple
                                                    @if ($data->id) hidden @endif>
                                                    <option disabled>Pilih Sales Type</option>
                                                    @foreach (json_decode($outlets) as $outlet)
                                                        <option value="{{ $outlet->id }}"
                                                            @if ($data->outlet_id == $outlet->id) selected @endif>
                                                            {{ $outlet->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    {{-- <div class="d-flex" onload="radioClicked()" onclick="radioClicked()">
                                        
                                        
                                    </div> --}}
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

        <!-- Accordion Level 2 -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingTwo">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo" disabled>
                    Level 2
                </button>
            </h2>
            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo"
                data-bs-parent="#accordionExample">
                <div class="accordion-body">
                    {{-- <p>Complete this task to proceed:</p>
                    <input type="number" id="task2" class="form-control" placeholder="Enter a number"> --}}
                    <button type="button" class="btn btn-round btn-outline-secondary mt-3 next-btn"
                        data-target="#collapseOne">Back</button>
                    <button type="button" class="btn btn-round btn-primary mt-3 next-btn"
                        data-target="#collapseThree">Next</button>
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
        $(document).ready(function() {
            $(".select2InsideModal").select2({
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
                    console.log(this);

                    const dropdown = $(".select2-container");

                    // Hapus style yang diterapkan saat dropdown ditutup
                    dropdown.css({
                        position: "",
                        top: "",
                        left: "",
                    });

                    console.log(dropdown);

                });

            // Disable proceeding to the next accordion unless the current task is fulfilled
            $("#btnPromoInformationNext").on('click', function() {

            })
            $('.next-btn').on('click', function() {
                const target = $(this).data('target');
                const currentTask = $(this).closest('.accordion-body').find('input, textarea');
                console.log(target);
                console.log(currentTask)
                console.log(currentTask.val())
                // if (!$('input[name="promoType"]:checked').length) {
                //     alert('Please select a promo type before proceeding.');
                //     return false;
                // }

                if (currentTask.val().trim() === '') {
                    if (currentTask.type)

                        alert('Please complete the task before proceeding.');
                } else {
                    // Open the next accordion
                    $(target).collapse('show');
                }
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
                console.log('Selected Promo Type:', selectedValue);
            });

        });
    </script>


</x-modal>
