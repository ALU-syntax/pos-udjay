<button class="btn btn-sm btn-primary pilih-pelanggan-btn" data-id="{{ $data->id }}" data-nama="{{ $data->name }}" data-poin="{{$data->point}}">
    Pilih
</button>

<script>
    $('.pilih-pelanggan-btn').on('click', function() {
        let id = $(this).data('id');
        let name = $(this).data('nama');
        let point = $(this).data('poin');

        idPelanggan = id;
        pointPelanggan = parseInt(point);

        // Update button text with Font Awesome icon and name
        $('#pilih-pelanggan').html('<i class="fas fa-user"></i> ' + name);

        const modal = $('#itemModal');
        modal.modal('hide');
    });
</script>
