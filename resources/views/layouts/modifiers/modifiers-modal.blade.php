<style>
    /* Dropdown select2 tetap di dalam konteks modal */
    .select2-container--open {
        position: fixed !important;
    }
</style>
{{-- ob-page memberi token & gaya report ke modal (modal dirender di luar .main-content) --}}
<x-modal addStyle="modal-lg ob-page ob-modal" title="{{ $data->id ? 'Edit Modifier' : 'Tambah Modifier' }}" action="{{ $action }}" method="POST" update="{{ $update }}">
    @if ($data->id)
        @method('put')
    @endif

    <div class="col-12">
        <p class="ob-modal__desc">
            {{ $data->id ? 'Perubahan berlaku untuk grup modifier di outlet ini.' : 'Grup modifier dibuat terpisah untuk setiap outlet yang dipilih.' }}
            Pasang ke produk lewat tombol <i class="fas fa-link" aria-hidden="true"></i> di daftar modifier.
        </p>
    </div>

    {{-- Grup --}}
    <div class="col-12 ob-form-heading ob-form-heading--first">
        <h3 class="ob-section-title"><i class="fas fa-sliders-h" aria-hidden="true"></i> Grup Modifier</h3>
    </div>
    <div class="col-md-8 mb-3">
        <label for="name" class="form-label">Nama Grup <span class="text-danger">*</span></label>
        <input id="name" name="name" value="{{ $data->name }}" type="text" class="form-control"
            placeholder="Contoh: Sugar Level" required>
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">Pilihan</label>
        <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" name="is_required" value="1" id="is_required" @if($data->is_required) checked @endif>
            <label class="form-check-label" for="is_required">Wajib dipilih minimal satu</label>
        </div>
    </div>
    <div class="col-12 mb-3">
        <label for="show_in_order_table" class="form-label">Tampil di Order Table <span class="text-danger">*</span></label>
        <select name="show_in_order_table" id="show_in_order_table" class="form-select" required>
            <option value="1" @if ($data->show_in_order_table ?? true) selected @endif>Iya, tampilkan di menu pelanggan</option>
            <option value="0" @if (!($data->show_in_order_table ?? true)) selected @endif>Tidak, khusus kasir</option>
        </select>
        <small class="text-muted">Opsi tertentu bisa disembunyikan lewat kolom "Order Table" di tabel opsi. Kasir tetap melihat semuanya.</small>
        <span id="order-table-required-warning" class="ob-field-warning d-none">
            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i> Grup wajib ini tidak akan diminta di Order Table. Pesanan pelanggan bisa masuk tanpa pilihan ini.
        </span>
    </div>

    {{-- Opsi --}}
    <div class="col-12 ob-form-heading">
        <h3 class="ob-section-title"><i class="fas fa-list-ul" aria-hidden="true"></i> Opsi</h3>
    </div>
    <div class="col-12 mb-3">
        <div class="table-responsive">
            <table class="table ob-options-table mb-2">
                <thead>
                    <tr>
                        <th>Nama Opsi</th>
                        <th style="width: 22%">Harga</th>
                        <th style="width: 18%">Stok</th>
                        <th class="text-center" style="width: 14%" title="Tampil di Order Table">Order Table</th>
                        <th style="width: 56px"></th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    @if ($data->id)
                        @foreach ($data->modifier as $modifier)
                            <tr>
                            <td><input type="text" class="form-control" name="option_name[]" placeholder="Contoh: Less Sugar" value="{{ $modifier->name }}"></td>
                            <td><input type="text" class="form-control harga-modifier" id="harga-modifier" name="price[]" placeholder="Rp" value="{{ $modifier->harga }}"></td>
                            <td><input type="number" class="form-control" name="stok[]" placeholder="Tanpa batas" value="{{ $modifier->stok }}"></td>
                            <td class="text-center">
                                @include('layouts.modifiers.option-order-table-cell', ['checked' => $modifier->show_in_order_table ?? true])
                            </td>
                            <td class="text-center">
                                <input type="text" hidden value="{{ $modifier->id }}" name="id_modifier[]">
                                <button type="button" class="ob-icon-btn ob-icon-btn--sm ob-icon-btn--danger removeRow" title="Hapus opsi" aria-label="Hapus opsi"><i class="fas fa-trash-alt" aria-hidden="true"></i></button>
                            </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td><input type="text" class="form-control" name="option_name[]" placeholder="Contoh: Less Sugar"></td>
                            <td><input type="text" class="form-control" id="harga-modifier" name="price[]" placeholder="Rp"></td>
                            <td><input type="number" class="form-control" name="stok[]" placeholder="Tanpa batas"></td>
                            <td class="text-center">
                                @include('layouts.modifiers.option-order-table-cell', ['checked' => true])
                            </td>
                            <td></td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        <button id="addRow" onclick="loadHargaModifierInput()" type="button" class="btn ob-btn ob-btn-outline ob-btn-sm">
            <i class="fas fa-plus" aria-hidden="true"></i> Tambah Opsi
        </button>
        <small class="text-muted">Harga 0 berarti gratis. Kosongkan stok bila tidak dibatasi.</small>
    </div>

    {{-- Outlet (hanya saat tambah) --}}
    <div class="col-12 ob-form-heading" @if ($data->id) hidden @endif>
        <h3 class="ob-section-title"><i class="fas fa-store" aria-hidden="true"></i> Outlet</h3>
    </div>
    <div class="col-12 mb-3" @if ($data->id) hidden @endif>
        <label for="outlet_id" class="form-label">Outlet <span class="text-danger">*</span></label>
        <select id="outlet_id" @if ($data->id) name="outlet_id" @else name="outlet_id[]" @endif
            class="select2InsideModal form-select w-100" style="width: 100% !important;" required multiple
            @if ($data->id) hidden @endif>
            <option disabled>Pilih outlet</option>
            @foreach (json_decode($outlets) as $outlet)
                <option value="{{ $outlet->id }}" @if ($data->outlet_id == $outlet->id) selected @endif>
                    {{ $outlet->name }}</option>
            @endforeach
        </select>
    </div>

    <script>
        @if ($data->id)
            var valueHargaModifier = @json($data->modifier);

            var hargaModifierInputs = document.querySelectorAll(".harga-modifier");
            hargaModifierInputs.forEach(function(item, index) {
                item.value = formatRupiah(valueHargaModifier[index].harga.toString(), "Rp. ");
            });
        @endif

        // Remove row
        $(document).on('click', '.removeRow', function() {
            $(this).closest('tr').remove();
            updateOrderTableRequiredWarning();
        });

        var firstHargaModifierInput = document.getElementById('harga-modifier');

        firstHargaModifierInput.addEventListener("keyup", function(e) {
            this.value = formatRupiah(this.value, "Rp. ");
        })

        function addHargaModifierEventListeners() {
            const hargaModifierInputs = document.querySelectorAll(".harga-modifier");

            hargaModifierInputs.forEach(input => {
                input.removeEventListener("keyup", handleKeyup); // Hapus listener lama (jika ada)
                input.addEventListener("keyup", handleKeyup);
            });
        }

        function handleKeyup(e) {
            this.value = formatRupiah(this.value, "Rp. ");
        }

        function loadHargaModifierInput() {
            const tableBody = document.getElementById("tableBody");

            // Buat elemen <tr> baru
            const newRow = document.createElement("tr");
            newRow.innerHTML = `
        <td><input type="text" class="form-control" name="option_name[]" placeholder="Contoh: Less Sugar"></td>
        <td><input type="text" class="form-control harga-modifier" name="price[]" placeholder="Rp"></td>
        <td><input type="number" class="form-control" name="stok[]" placeholder="Tanpa batas"></td>
        <td class="text-center">
            @include('layouts.modifiers.option-order-table-cell', ['checked' => true])
        </td>
        <td class="text-center">
            <button type="button" class="ob-icon-btn ob-icon-btn--sm ob-icon-btn--danger removeRow" title="Hapus opsi" aria-label="Hapus opsi"><i class="fas fa-trash-alt" aria-hidden="true"></i></button>
        </td>
    `;

            // Tambahkan baris baru ke tabel
            tableBody.appendChild(newRow);

            // Tambahkan event listener untuk input baru
            addHargaModifierEventListeners();
            updateOrderTableRequiredWarning();
        }

        // Checkbox tidak terkirim saat tidak dicentang, jadi nilai 0/1 dikirim lewat input hidden
        // agar option_show_in_order_table[] tetap sejajar dengan option_name[].
        $(document).off('change.optionOrderTable').on('change.optionOrderTable', '.option-order-table-check', function() {
            $(this).siblings('.option-order-table-value').val(this.checked ? 1 : 0);
            updateOrderTableRequiredWarning();
        });

        // Grup wajib yang disembunyikan (atau semua opsinya disembunyikan) tidak diminta di Order Table.
        function updateOrderTableRequiredWarning() {
            const required = $('#is_required').is(':checked');
            const groupHidden = $('#show_in_order_table').val() === '0';
            const visibleOptions = $('#tableBody .option-order-table-check:checked').length;
            $('#order-table-required-warning').toggleClass('d-none', !(required && (groupHidden || visibleOptions === 0)));
        }

        $('#is_required, #show_in_order_table').on('change', updateOrderTableRequiredWarning);
        updateOrderTableRequiredWarning();


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

        document.addEventListener("DOMContentLoaded", function() {
            // Tambahkan event listener ke semua input harga awal
            addHargaModifierEventListeners();

            // Tombol "Add Row"
            document.getElementById("addRow").addEventListener("click", loadHargaModifierInput);
        });

        function radioClicked() {
            let requiredChoice = document.querySelector('input[name="required"]:checked').value;
            const minForm = document.getElementById('min-input');
            const minInput = document.getElementById('min')


            switch (requiredChoice) {
                case 'true':
                    minForm.removeAttribute("hidden");
                    minForm.setAttribute("required", true);
                    break;

                case 'false':
                    minForm.setAttribute("hidden", true);
                    minForm.removeAttribute("required");
                    minInput.value = '';
                    break;

                default:
            }
        };
    </script>
</x-modal>
