<x-modal title="Tambah Product" addStyle="modal-xl" description="Tambah Product berdasarkan Category"
    action="{{ $action }}" method="POST">
    @if ($data->id)
        @method('put')
    @endif
    <div class="col-6">
        <div class="form-group">
            <label for="category_id">Category <span class="text-danger ">*</span></label>
            <select name="category_id" id="category_id" class="select2InsideModal form-select w-100"
                style="width: 100% !important;" required>
                <option selected disabled>Pilih Category</option>
                @foreach ($categorys as $category)
                    <option value="{{ $category->id }}" @if ($data->category_id == $category->id) selected @endif>
                        {{ $category->name }}</option>
                @endforeach
            </select>
            {{-- <small id="category_id_feedback" class="d-none text-danger"><i>*Pilih Category Terlebih
                    Dahulu</i></small> --}}
        </div>
    </div>
    <div class="col-6">
        <div class="form-group">
            <label>Name <span class="text-danger">*</span></label>
            <input id="name" name="name" value="{{ $data->name }}" type="text" class="form-control"
                placeholder="nama product.." required>
        </div>
    </div>

    <div class="col-4">
        <div class="form-group">
            <label for="status">Status <span class="text-danger">*</span></label>
            <select name="status" id="status" class="form-select" required>
                <option disabled selected>Pilih Status</option>
                <option value="1" @if ($data->status == 1) selected @endif>Aktif</option>
                <option value="0" @if ($data->status == 0) selected @endif>Tidak Aktif</option>
            </select>
        </div>
    </div>
    <div class="col-4">
        <div class="form-group">
            <label>Harga Modal<span class="text-danger">*</span></label>
            <input id="harga_modal" name="harga_modal" value="{{ $data->harga_modal }}" type="text"
                class="form-control" placeholder="Harga Modal.." required>
        </div>
    </div>
    <div class="col-4">
        <div class="form-group">
            <label>Photo</label>
            <input id="photo" name="photo" value="{{ $data->photo }}" type="file" class="form-control"
                placeholder="Harga Modal..">
        </div>
    </div>

    <div class="col-12 mt-3 px-4">
        <button type="button" id="tambah-varian" class="btn btn-primary w-100"> Tambah Varian</button>
        <div class="input-group col-12 list-varian">
            <input id="harga-varian" name="harga_jual[]" placeholder="Harga Jual" type="text"
                class="form-control harga_jual" required>
            <input id="stok-varian" name="stock[]" type="number" class="form-control" placeholder="Stok" required>
        </div>
    </div>

    <div class="col-12" @if ($data->id) hidden @endif>
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
        var hargaModalInput = document.getElementById("harga_modal");

        $(".select2InsideModal").select2({
            dropdownParent: $("#modal_action")
        });

        $('#tambah-varian').on('click', function() {
            const newVarian = `
        <div class="input-group col-12 mt-1 list-varian">
            <input  name="nama_varian[]" type="text" class="form-control" placeholder="Nama Varian" required>
            <input id="harga-varian" name="harga_jual[]" placeholder="Harga Jual" type="text" class="form-control harga_jual" required>
            <input id="stok-varian" name="stock[]" type="number" class="form-control" placeholder="Stok" required>
            <button type="button" class="btn btn-danger btn-sm remove-varian">×</button>
        </div>`;

            if (listVarianLength < 1) {
                $('.list-varian').first().prepend(`
                    <input id="nama-varian" name="nama_varian[]" type="text" class="form-control" placeholder="Nama Varian" required>
                `);
            }
            listVarianLength++;

            // Menambahkan elemen baru ke dalam container
            $(".list-varian").first().after(newVarian);
        });

        $(document).off().on('click', '.remove-varian', function() {
            $(this).closest('.list-varian').remove(); // Hapus row terpilih
            if (listVarianLength == 1) {
                $('.list-varian #nama-varian').remove();
            }
            console.log("masok pak eko")
            listVarianLength--;
        });

        @if ($data->id)
            var valueHargaJual = "{{ $data->harga_jual }}";
            var valueHargaModal = "{{ $data->harga_modal }}";

            var convertHargaJual = parseInt(valueHargaJual);
            var convertHargaModal = parseInt(valueHargaModal);

            hargaJualInput.value = formatRupiah(convertHargaJual.toString(), "Rp. ");
            hargaModalInput.value = formatRupiah(convertHargaModal.toString(), "Rp. ");
        @endif

        $(document).on('input', '.harga_jual', function() {
            this.value = formatRupiah(this.value, "Rp. ");
        });

        hargaModalInput.addEventListener("keyup", function(e) {
            this.value = formatRupiah(this.value, "Rp. ");
        });
    </script>
</x-modal>
