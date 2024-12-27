<x-modal title="Tambah Customer" action={{$action}}
    method="POST">
    @if ($data->id)
        @method('put')
    @endif
    <div class="col-sm-12">
        <div class="form-group">
            <label>Name <span class="text-danger">*</span></label>
            <input id="name" name="name" value="{{ $data->name }}" type="text" class="form-control"
                placeholder="nama customer.." required>
        </div>
    </div>
    <div class="col-sm-12">
        <div class="form-group">
            <label>Umur<span class="text-danger">*</span></label>
            <input type="text" id="umur" name="umur" value="{{ $data->umur }}" type="number"
                class="form-control" placeholder="Umur" required>
        </div>
    </div>
    <div class="col-sm-12">
        <div class="form-group">
            <label>Nomor Telfon<span class="text-danger">*</span></label>
            <input id="telfon" name="telfon" value="{{ $data->telfon }}" type="text"
                class="form-control" placeholder="Nomor telfon" required>
        </div>
    </div>
    
    <script>
        document.getElementById('telfon').addEventListener('keyup', function (event) {
            const input = event.target;
            const errorMessage = document.getElementById('error-message');
    
            // Hanya izinkan angka
            if (!/^\d*$/.test(input.value)) {
                input.value = input.value.replace(/[^0-9]/g, ''); // Hapus karakter non-angka
                errorMessage.style.display = 'inline'; // Tampilkan pesan error
            } else {
                errorMessage.style.display = 'none'; // Sembunyikan pesan error
            }
        });
    </script>
</x-modal>

