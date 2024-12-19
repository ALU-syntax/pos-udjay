<style>
    .promo-box {
        border: 2px solid #ddd;
        border-radius: 8px;
        text-align: center;
        padding: 20px;
        cursor: pointer;
        transition: 0.3s;
    }

    .promo-box:hover {
        border-color: #007bff;
    }

    .promo-box.active {
        border-color: #007bff;
        box-shadow: 0 0 10px rgba(0, 123, 255, 0.5);
    }

    .promo-box .icon {
        font-size: 50px;
        color: #fff;
        background-color: #007bff;
        width: 80px;
        height: 80px;
        line-height: 80px;
        border-radius: 50%;
        margin: 0 auto 10px;
    }
</style>

<x-modal title="Tambah Discount" action="{{ $action }}" method="POST">
    @if ($data->id)
        @method('put')
    @endif
    <div class="col-sm-12">
        <div class="form-group">
            <label>Name <span class="text-danger">*</span></label>
            <input id="name" name="name" value="{{ $data->name }}" type="text" class="form-control"
                placeholder="nama diskon" required>
        </div>
    </div>
    <div class="col-sm-12">
        <div class="form-group">
            <label>Input Configuration</label><br>
            <div class="" onload="radioClicked()" onclick="radioClicked()">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="type_input" id="type_input" value="fixed"
                    @if ($data->id) @if ($data->type_input == 'fixed')checked @endif @else checked
                        @endif>
                    <label class="form-check-label" for="flexRadioDefault1"> Fixed Amount </label>
                    <small class="d-flex" style="color:gray;">Amount configured in Back Office and cannot be changed in
                        POS</small>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="type_input" id="type_input" value="custom"
                        @if ($data->type_input == 'custom') checked @endif>
                    <label class="form-check-label" for="flexRadioDefault2">Customizable Amount</label>
                    <small class="d-flex" style="color: gray;">Amount to be decide in POS</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-12" id="selectPromoTypeRow" @if ($data->type_input == 'custom') @else hidden @endif>
        <div class="form-group">
            <label>Select Discount Type</label><br>
            <div class="row ms-1">
                <div class="col-sm-6 ">
                    <label class="promo-box @if($data->satuan_discount_custom == 'rupiah') active @else @if(!$data->id) active @endif  @endif" for="deduction-rp">
                        <div class="icon">Rp</div>
                        <input type="radio" name="satuan_discount_custom" id="deduction-rp" value="rupiah"
                            class="form-check-input d-none" @if($data->satuan_discount_custom == 'rupiah') checked @else @if(!$data->id) checked @endif @endif>
                        <h6>Deduction Amount (Rp)</h6>
                        <small>e.g. Rp. 10.000 of Total Sales</small>
                    </label>
                </div>
                <div class="col-sm-6">
                    <label class="promo-box @if ($data->satuan_discount_custom == 'percent') active @endif" for="deduction-percent">
                        <div class="icon">%</div>
                        <input type="radio" name="satuan_discount_custom" id="deduction-percent" value="percent"
                            class="form-check-input d-none " @if ($data->satuan_discount_custom == 'percent') checked @endif>
                        <h6>Deduction Amount (%)</h6>
                        <small>e.g. 15% of Total Sales</small>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-12" id="discountAmount"
        @if (!$data->id) @else @if ($data->type_input == 'fixed')  @else hidden @endif @endif >
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
    </div>

    @if (!$data->id)
        <div class="col-md-12" @if ($data->id) hidden @endif>
            <div class="form-group">
                <label for="outlet_id">Outlet <span class="text-danger ">*</span></label>
                <select @if ($data->id) name="outlet_id" @else name="outlet_id[]" @endif
                    class="select2InsideModal form-select w-100" style="width: 100% !important;" required multiple
                    @if ($data->id) hidden @endif>
                    <option disabled>Pilih Category</option>
                    @foreach (json_decode($outlets) as $outlet)
                        <option value="{{ $outlet->id }}" @if ($data->outlet_id == $outlet->id) selected @endif>
                            {{ $outlet->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    @endif

    <script>
        @if ($data->id)

            @if ($data->type_input == 'fixed')
                var valueAmount = JSON.parse('{!! $data->amount !!}')
                var amountInput = document.getElementById("amountInput");
                console.log(valueAmount);

                amountInput.value = formatRupiah(valueAmount.toString(), "Rp. ");
            @endif
        @endif

        document.querySelectorAll('.promo-box').forEach(box => {
            box.addEventListener('click', function() {
                // Menghapus semua class 'active'
                document.querySelectorAll('.promo-box').forEach(b => b.classList.remove('active'));
                // Menambahkan class 'active' ke elemen yang diklik
                this.classList.add('active');
                // Memastikan radio button terkait ikut terpilih
                this.querySelector('input').checked = true;

            });
        });

        function radioClicked() {
            let requiredChoice = document.querySelector('input[name="type_input"]:checked').value;
            const selectPromoTypeRow = document.getElementById('selectPromoTypeRow');
            const discountAmountRow = document.getElementById('discountAmount');

            switch (requiredChoice) {
                case 'custom':
                    selectPromoTypeRow.removeAttribute("hidden");
                    discountAmountRow.setAttribute("hidden", true);
                    break;

                case 'fixed':
                    selectPromoTypeRow.setAttribute("hidden", true);
                    discountAmountRow.removeAttribute("hidden");
                    break;

                default:
            }
        };

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

                    // Handle keyup based on selected choice
                    amountInput.removeEventListener("keyup", handleKeyup);
                    amountInput.addEventListener("keyup", handleKeyup);
                });
            });

            amountInput.addEventListener("keyup", handleKeyup);
            function handleKeyup(e) {
                const satuanChoice = document.querySelector('input[name="satuan"]:checked').value;
                if (satuanChoice === "rupiah") {
                    console.log(this.value);
                    amountInput.type = "text";
                    this.value = formatRupiah(this.value, "Rp. ");
                } else if (satuanChoice === "percent") {
                    // Set input type to number and add min attribute
                    amountInput.type = "number";
                    amountInput.min = "1";
                    amountInput.removeEventListener("keyup", handleKeyup); // No formatting for percent
                }
            }

            // Define keyup handler
        }

        



        // Initialize
        satuanClicked();

        $(".select2InsideModal").select2({
            dropdownParent: $("#modal_action"), // Pastikan parent diatur untuk modal
            // Callback setelah dropdown dibuka
            closeOnSelect: false,
        }).on("select2:open", function() {
            const selectElement = $(this);
            const dropdown = $(".select2-container--open");

            // Hitung posisi elemen input
            const offset = selectElement.offset();
            const height = selectElement.outerHeight();

            // Atur posisi dropdown ke posisi fixed
            dropdown.css({
                position: "fixed",
                top: offset.top + height - $(window).scrollTop(), // Hitung posisi relatif terhadap layar
                left: offset.left,
                width: selectElement.outerWidth() - 50,
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
    </script>
</x-modal>
