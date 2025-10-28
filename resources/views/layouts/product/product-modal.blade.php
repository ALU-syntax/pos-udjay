<x-modal title="{{ $update ? 'Update Product' : 'Tambah Product' }}" addStyle="modal-xl" update="{{ $update }}"
    description="{{ $update ? 'Update Product berdasarkan Category' : 'Add Product berdasarkan Category' }}"
    action="{{ $action }}" method="POST">

    @if ($data->id)
        @method('put')
    @endif
    <div class="col-4">
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
    <div class="col-4">
        <div class="form-group">
            <label>Name <span class="text-danger">*</span></label>
            <input id="name" name="name" value="{{ old('name', $data->name) }}" type="text" class="form-control"
                placeholder="nama product.." required>
        </div>
    </div>
    <div class="col-4">
        <div class="form-check mt-5">
            <input class="form-check-input" type="checkbox" name="exclude_tax" value="1" id="exclude_tax" @if($data->exclude_tax) checked @endif>
            <label class="form-check-label" for="exclude_tax">
                Tidak Dikenakan Pajak
            </label>
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

    <div class="col-md-12 mb-3">
        <div class="form-group">
            <label for="description" class="form-label">Deskripsi</label>
            <textarea class="form-control" name="description" id="description"
                placeholder="Masukkan deskripsi produk, kosongkan bila tidak ada">{{ $data->description }}</textarea>
        </div>
    </div>

    <div class="col-12 mt-3 px-4" id="container-list-variant">
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
            $(".list-varian").last().after(newVarian);
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
            var valueNamaProduct = "{{ $data->name }}";
            // var listVariants = '{!! $data->variants !!}';
            // var jsonListVariants = JSON.parse(listVariants);
            var jsonListVariants = @json($data->variants);

            $('#tambah-varian').nextAll().remove();
            jsonListVariants.forEach(function(item, index) {
                console.log(index)
                if (jsonListVariants.length > 1) {
                    if (index > 0) {
                        $('.list-varian').last().after(`
                                <div class="input-group col-12 mt-1 list-varian">
                                    <input name="id_variant[]" type="hidden" value="${item.id}" class="form-control">
                                    <input name="nama_varian[]" type="text" value="${item.name}" class="form-control" placeholder="Nama Varian" required>
                                    <input id="harga-varian" name="harga_jual[]" value="Rp. ${formatRupiah(item.harga.toString())}" placeholder="Harga Jual" type="text" class="form-control harga_jual" required>
                                    <input id="stok-varian" name="stock[]" type="number" value="${item.stok}" class="form-control" placeholder="Stok" required>
                                    <button type="button" class="btn btn-danger btn-sm remove-varian">×</button>
                                </div>
                            `);
                    } else {
                        $('#tambah-varian').after(`
                                <div class="input-group col-12 mt-1 list-varian">
                                    <input name="id_variant[]" type="hidden" value="${item.id}" class="form-control">
                                    <input name="nama_varian[]" id="nama-varian" type="text" value="${item.name}" class="form-control" placeholder="Nama Varian" required>
                                    <input id="harga-varian" name="harga_jual[]" value="Rp. ${formatRupiah(item.harga.toString())}" placeholder="Harga Jual" type="text" class="form-control harga_jual" required>
                                    <input id="stok-varian" name="stock[]" type="number" value="${item.stok}" class="form-control" placeholder="Stok" required>
                                    <button type="button" class="btn btn-danger btn-sm remove-varian">×</button>
                                </div>
                            `);
                    }
                } else if (jsonListVariants.length == 1 && item.name == valueNamaProduct) {
                    $('#tambah-varian').after(`
                            <div class="input-group col-12 mt-1 list-varian">
                                <input name="id_variant[]" type="hidden" value="${item.id}" class="form-control">
                                <input id="harga-varian" name="harga_jual[]" value="Rp. ${formatRupiah(item.harga.toString())}" placeholder="Harga Jual" type="text" class="form-control harga_jual" required>
                                <input id="stok-varian" name="stock[]" type="number" value="${item.stok}" class="form-control" placeholder="Stok" required>
                            </div>
                        `);
                } else {
                    $('#tambah-varian').after(`
                                <div class="input-group col-12 mt-1 list-varian">
                                    <input name="id_variant[]" type="hidden" value="${item.id}" class="form-control">
                                    <input name="nama_varian[]" id="nama-varian" type="text" value="${item.name}" class="form-control" placeholder="Nama Varian" required>
                                    <input id="harga-varian" name="harga_jual[]" value="Rp. ${formatRupiah(item.harga.toString())}" placeholder="Harga Jual" type="text" class="form-control harga_jual" required>
                                    <input id="stok-varian" name="stock[]" type="number" value="${item.stok}" class="form-control" placeholder="Stok" required>
                                    <button type="button" class="btn btn-danger btn-sm remove-varian">×</button>
                                </div>
                            `);
                }

                listVarianLength = index;
            });

            $(document).off().on('click', '.remove-varian', function() {
                $(this).closest('.list-varian').remove(); // Hapus row terpilih
                if (listVarianLength == 1) {
                    $('.remove-varian').remove();
                }
                console.log("masok pak mekcay")
                listVarianLength--;
            });

            $('#tambah-varian').off().on('click', function() {
                const newVarian = `
        <div class="input-group col-12 mt-1 list-varian">
            <input  name="nama_varian[]" type="text" class="form-control" placeholder="Nama Varian" required>
            <input id="harga-varian" name="harga_jual[]" placeholder="Harga Jual" type="text" class="form-control harga_jual" required>
            <input id="stok-varian" name="stock[]" type="number" class="form-control" placeholder="Stok" required>
            <button type="button" class="btn btn-danger btn-sm remove-varian">×</button>
        </div>`;

                if (listVarianLength == 0) {
                    $('.list-varian').first().append(`
                    <button type="button" class="btn btn-danger btn-sm remove-varian">×</button>
                `);
                }
                listVarianLength++;

                // Menambahkan elemen baru ke dalam container
                $(".list-varian").last().after(newVarian);
            });

            var convertHargaModal = parseInt(valueHargaModal);

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
