<style>
    /* .select2-container--open{
        position: fixed !important;
    } */
    .select2-container--open {
        position: fixed !important;
        /* Pastikan dropdown tetap di dalam konteks modal */
    }
</style>
<x-modal addStyle="modal-lg" title="Tambah Pilihan" action="{{ $action }}" method="POST">
    @if ($data->id)
        @method('put')
    @endif
    <div class="col-sm-12">
        <div class="form-group">
            <label>Pilihan Group <span class="text-danger">*</span></label>
            <input id="name" name="name" value="{{ $data->name }}" type="text" class="form-control"
                placeholder="Name" required>
        </div>
    </div>
    <hr class="ms-4 me-4" style="width: 95%;">
    <div class="col-sm-12 px-4 table-responsive">
        <table class="table table-bordered ">
            <thead class="thead-light">
                <tr>
                    <th>Option Name</th>
                    <th>Price</th>
                    {{-- <th>Stok</th> --}}
                    <th></th>
                </tr>
            </thead>
            <tbody id="tableBody">
                @if ($data->id)
                    @foreach ($data->pilihans as $pilihan)
                        <tr>
                            <td style="padding: 5px !important"><input type="text" class="form-control"
                                    name="option_name[]" placeholder="Name" value="{{ $pilihan->name }}">
                            </td>
                            <td style="padding: 5px !important"><input type="text"
                                    class="form-control harga-pilihan" id="harga-pilihan" name="price[]"
                                    placeholder="Rp" value="{{ $pilihan->harga }}">
                            </td>
                            <td style="padding: 5px !important"><input type="number" class="form-control"
                                    name="stok[]" placeholder="Stok" value="{{ $pilihan->stok }}"></td>
                            <td>
                                <input type="text" hidden value="{{ $pilihan->id }}" name="id_pilihan[]">
                                <button type="button" class="btn btn-danger btn-sm removeRow">Remove</button>
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td style="padding: 5px !important"><input type="text" class="form-control"
                                name="option_name[]" placeholder="Name">
                        </td>
                        <td style="padding: 5px !important"><input type="text" class="form-control"
                                id="harga-pilihan" name="price[]" placeholder="Rp"></td>
                        <td style="padding: 5px !important"><input type="number" class="form-control" name="stok[]"
                                placeholder="Stok"></td>
                        <td>
                            {{-- <button type="button" class="btn btn-danger btn-sm removeRow">Remove</button> --}}
                        </td>
                    </tr>
                @endif

            </tbody>
        </table>
        <button id="addRow" onclick="loadHargaPilihanInput()" type="button" class="btn btn-primary btn-sm">Add
            Row</button>
    </div>
    <hr class="ms-4 me-4 mt-4" style="width: 95%;">

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

    <script>
        @if ($data->id)
            var valueHargaPilihan = JSON.parse('{!! $data->pilihans !!}')

            var hargaPilihanInputs = document.querySelectorAll(".harga-pilihan");
            hargaPilihanInputs.forEach(function(item, index) {
                item.value = formatRupiah(valueHargaPililhan[index].harga.toString(), "Rp. ");
            });
        @endif

        // Remove row
        $(document).on('click', '.removeRow', function() {
            $(this).closest('tr').remove();
        });

        var firstHargaPilihanInput = document.getElementById('harga-pilihan');

        firstHargaPilihanInput.addEventListener("keyup", function(e) {
            this.value = formatRupiah(this.value, "Rp. ");
        })

        function addHargaPilihanEventListeners() {
            const hargaPilihanInputs = document.querySelectorAll(".harga-pilihan");

            hargaPilihanInputs.forEach(input => {
                input.removeEventListener("keyup", handleKeyup); // Hapus listener lama (jika ada)
                input.addEventListener("keyup", handleKeyup);
            });
        }

        function handleKeyup(e) {
            this.value = formatRupiah(this.value, "Rp. ");
        }

        function loadHargaPilihanInput() {
            const tableBody = document.getElementById("tableBody");

            // Buat elemen <tr> baru
            const newRow = document.createElement("tr");
            newRow.innerHTML = `
        <td style="padding: 5px !important">
            <input type="text" class="form-control" name="option_name[]" placeholder="Name">
        </td>
        <td style="padding: 5px !important">
            <input type="text" class="form-control harga-pilihan" name="price[]" placeholder="Rp">
        </td>
        <td style="padding: 5px !important">
            <input type="number" class="form-control" name="stok[]" placeholder="Stok">
        </td>
        <td>
            <button type="button" class="btn btn-danger btn-sm removeRow">Remove</button>
        </td>
    `;

            // Tambahkan baris baru ke tabel
            tableBody.appendChild(newRow);

            // Tambahkan event listener untuk input baru
            addHargaPilihanEventListeners();
        }


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
            addHargaPilihanEventListeners();

            // Tombol "Add Row"
            document.getElementById("addRow").addEventListener("click", loadHargaPilihanInput);
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
