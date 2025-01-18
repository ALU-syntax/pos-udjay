<x-modal title="Tambah Pengeluaran" action="{{ $action }}" method="POST">
    <style>
        .img-preview {
        max-width: 100%;
        max-height: 100px;
    }
    </style>
    @if ($data->id)
        @method('put')
    @endif

    <div class="mb-3">
        <label for="kategori_pemasukan_id" class="form-label">Kategori <span class="text-danger">*</span></label>
        <select class="form-select" name="kategori_pemasukan_id" id="kategori_pemasukan_id" required>
            <option disabled selected class="text-muted">Pilih kategori pemasukan</option>
            @foreach($kategoriPemasukans as $kategoriPemasukan)
                <option value="{{$kategoriPemasukan->id}}" @if ($data->kategori_pemasukan_id == $kategoriPemasukan->id) selected @endif>{{$kategoriPemasukan->name}}</option>
            @endforeach
        </select>
        <div class="invalid-feedback"></div>
    </div>

    <div class="mb-3">
        <label for="outlet_id" class="form-label">Outlet <span class="text-danger">*</span></label>
        <select class="form-select" name="outlet_id" id="outlet_id" required>
            <option disabled selected class="text-muted">Pilih Outlet</option>
            @foreach($outlets as $outlet)
                <option value="{{$outlet->id}}" @if ($data->outlet_id == $outlet->id) selected @endif>{{$outlet->name}}</option>
            @endforeach
        </select>
        <div class="invalid-feedback"></div>
    </div>
    
    <div class="mb-3">
        <label for="jumlah" class="form-label">Total Pemasukan <span class="text-danger">*</span></label>
        <input type="text" class="form-control harga" value="{{$data->jumlah}}" id="jumlah" name="jumlah" required
            placeholder="Masukkan jumlah pengeluaran">
        <div class="invalid-feedback"></div>
    </div>
    <hr>
    <p class="text-center">Informasi Opsional</p>
    <div class="mb-3">
        <label for="photo" class="form-label">Upload Foto</label>
        <input type="file" value="{{$data->photo}}" class="form-control" id="photo" name="photo" accept="image/*">
        <div class="invalid-feedback"></div>
        <p class="text-muted mt-1" style="font-size: x-small;">Silakan upload Foto yang berkaitan dengan transaksi ini.
        </p>
        <div id="preview" class="mt-3"></div>
    </div>
    <div class="mb-3">
        <label for="catatan" class="form-label">Catatan</label>
        <textarea class="form-control" name="catatan" id="catatan" placeholder="Masukkan catatan transaksi">{{$data->catatan}}</textarea>
    </div>
    <div class="mb-3">
        <label for="customer_id" class="form-label">Pelanggan</label>
        <select class="form-select" name="customer_id" id="customer_id">
            <option disabled selected class="text-muted">Pilih Pelanggan</option>
            @foreach($customers as $customer)
                <option value="{{$customer->id}}" @if ($data->customer_id == $customer->id) selected @endif>{{$customer->name}}</option>
            @endforeach
        </select>
        <div class="invalid-feedback"></div>
    </div>
    <div class="mb-3">
        <label for="tanggal" class="form-label">Tanggal</label>
        <input type="date" value="{{$data->tanggal}}" class="form-control" id="tanggal" name="tanggal">
        <div class="invalid-feedback"></div>
    </div>

    <script>
        var jumlahInput = document.getElementById("jumlah");

        $('#preview').empty();

        $(document).on('input', '#jumlah', function() {
            this.value = formatRupiah(this.value, "Rp. ");
        });

        @if($data->id)
            var valueJumlah = "{{ $data->jumlah }}";
            var valuePhoto = @json($data->photo);
            console.log(valuePhoto);
            jumlahInput.value = formatRupiah(valueJumlah.toString(), "Rp. ");

            $('#preview').html(`<img src="{{ asset('') }}${valuePhoto}" alt="Preview Gambar" class="img-preview rounded">`);  
        @endif
    </script>
</x-modal>
