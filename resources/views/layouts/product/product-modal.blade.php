<x-modal title="Tambah Product" description="Tambah Product berdasarkan Category" action="{{ $action }}"
    method="POST">
    @if ($data->id)
        @method('put')
    @endif
    <div class="col-sm-12">
        <div class="form-group">
            <label>Name <span class="text-danger">*</span></label>
            <input id="name" name="name" value="{{ $data->name }}" type="text" class="form-control"
                placeholder="nama product.." required>
        </div>
    </div>
    <div class="col-12">
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
    <div class="col-sm-12">
        <div class="form-group">
            <label>Harga Jual<span class="text-danger">*</span></label>
            <input type="text" id="harga_jual" name="harga_jual" value="{{ $data->harga_jual }}" type="text"
                class="form-control" placeholder="Harga Jual.." required>
        </div>
    </div>
    <div class="col-sm-12">
        <div class="form-group">
            <label>Harga Modal<span class="text-danger">*</span></label>
            <input id="harga_modal" name="harga_modal" value="{{ $data->harga_modal }}" type="text"
                class="form-control" placeholder="Harga Modal.." required>
        </div>
    </div>
    <div class="col-sm-12">
        <div class="form-group">
            <label>Photo</label>
            <input id="photo" name="photo" value="{{ $data->photo }}" type="file" class="form-control"
                placeholder="Harga Modal..">
        </div>
    </div>
    <div class="col-sm-12">
        <div class="form-group">
            <label>Stok<span class="text-danger">*</span></label>
            <input id="stock" name="stock" value="{{ $data->stock }}" type="number" class="form-control"
                placeholder="Stok" required>
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

    <div class="col-md-12">
        <div class="form-group">
            <label for="status">Status <span class="text-danger">*</span></label>
            <select name="status" id="status" class="form-select" required>
                <option disabled selected>Pilih Status</option>
                <option value="1" @if ($data->status == 1) selected @endif>Aktif</option>
                <option value="0" @if ($data->status == 0) selected @endif>Tidak Aktif</option>
            </select>
        </div>
    </div>

    <script>
        var hargaJualInput = document.getElementById("harga_jual");
        var hargaModalInput = document.getElementById("harga_modal");

        $(".select2InsideModal").select2({
            dropdownParent: $("#modal_action")
        });

        @if ($data->id)
            var valueHargaJual = "{{ $data->harga_jual }}";
            var valueHargaModal = "{{ $data->harga_modal }}";

            var convertHargaJual = parseInt(valueHargaJual);
            var convertHargaModal = parseInt(valueHargaModal);

            hargaJualInput.value = formatRupiah(convertHargaJual.toString(), "Rp. ");
            hargaModalInput.value = formatRupiah(convertHargaModal.toString(), "Rp. ");
        @endif

        hargaJualInput.addEventListener("keyup", function(e) {
            this.value = formatRupiah(this.value, "Rp. ");
        });

        hargaModalInput.addEventListener("keyup", function(e) {
            this.value = formatRupiah(this.value, "Rp. ");
        });
    </script>
</x-modal>
