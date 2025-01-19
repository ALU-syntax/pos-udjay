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
        <label for="pegawai" class="form-label">Memberikan ke :</label>
        <select class="form-select" name="pegawai" id="pegawai">
            <option disabled selected>Pilih Pegawai</option>
            
        </select>
        <div class="invalid-feedback"></div>
    </div>
    <div class="mb-3">
        <label for="jumlah" class="form-label">Memberikan sejumlah</label>
        <input type="text" class="form-control harga" id="jumlah" name="jumlah" placeholder="Masukkan jumlah piutang">
        <div class="invalid-feedback"></div>
    </div>
    <div class="mb-3">
        <label for="foto" class="form-label">Upload Foto</label>
        <input type="file" class="form-control" id="foto" name="foto" accept="image/*">
        <div class="invalid-feedback"></div>
        <p class="text-muted mt-1" style="font-size: x-small;">Silakan upload foto yang berkaitan dengan piutang ini.</p>
        <div id="preview"></div>
    </div>
    <div class="mb-3">
        <label for="foto" class="form-label">Catatan</label>
        <input type="text" class="form-control" name="catatan" id="catatan" placeholder="Cth: Piutang pembelian pulsa"></input>
        <div class="invalid-feedback"></div>
    </div>
    <div class="mb-3">
        <label for="tgl" class="form-label">Tanggal</label>
        <input type="date" class="form-control" id="tgl" name="tgl">
        <div class="invalid-feedback"></div>
    </div>
    <div class="mb-3">
        <label for="jt" class="form-label">Jatuh Tempo</label>
        <input type="date" class="form-control" id="jt" name="jt">
        <div class="invalid-feedback"></div>
    </div>
    <div class="mb-3">
        <label class="form-label">Status</label>
        <div class="row">
            <div class="col-4">
                <div class="form-check">
                    <input class="form-check-input belum" type="radio" name="status" value="0">
                    <label class="form-check-label">
                        Belum lunas
                    </label>
                </div>
            </div>
            <div class="col-3">
                <div class="form-check">
                    <input class="form-check-input lunas" type="radio" name="status" value="1">
                    <label class="form-check-label">
                        Lunas
                    </label>
                </div>
            </div>
        </div>
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
