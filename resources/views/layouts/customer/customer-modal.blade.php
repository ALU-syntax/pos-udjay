<x-modal title="Tambah Customer" action="{{ $action }}" method="POST">
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
            <input type="number" id="umur" name="umur" value="{{ $data->umur }}" type="number"
                class="form-control" placeholder="Umur" required>
        </div>
    </div>
    <div class="col-sm-12">
        <div class="form-group">
            <label>Nomor Telfon<span class="text-danger">*</span></label>
            <input id="telfon" name="telfon" value="{{ $data->telfon }}" type="text" class="form-control"
                placeholder="Nomor telfon" required>
        </div>
    </div>
    <div class="col-sm-12">
        <div class="form-group">
            <label>Email<span class="text-danger">*</span></label>
            <input id="email" name="email" value="{{ $data->email }}" type="email" class="form-control"
                placeholder="Email" required>
        </div>
    </div>
    <div class="col-sm-12">
        <div class="form-group">
            <label>Tanggal Lahir<span class="text-danger">*</span></label>
            <input id="tanggal_lahir" name="tanggal_lahir" value="{{ $data->tanggal_lahir }}" type="date"
                class="form-control" placeholder="Tanggal lahir" required>
        </div>
    </div>
    <div class="col-sm-12">
        <div class="form-group">
            <label>Domisili<span class="text-danger">*</span></label>
            <input id="domisili" name="domisili" value="{{ $data->domisili }}" type="text" class="form-control"
                placeholder="Domisili" required>
        </div>
    </div>
    <div class="col-sm-12">
        <div class="form-group">
            <label>Gender<span class="text-danger">*</span></label>
            <select id="gender" name="gender" class="form-select w-100" style="color:black;"
                data-style="btn-default" required>
                <option selected disabled class="text-gray">Pilih Gender</option>
                <option value="laki-laki" @if ($data->gender == 'laki-laki') selected @endif>Laki-Laki</option>
                <option value="perempuan" @if ($data->gender == 'perempuan') selected @endif>Perempuan</option>
            </select>
        </div>
    </div>
    <div class="col-md-12">
        <div class="form-group">
            <label for="community_id">Community</label>
            <select name="community_id" class="select2InsideModal form-select w-100" style="width: 100% !important;">
                <option disabled selected>Jika Umum tidak usah dipilih</option>
                @foreach ($communities as $community)
                    <option value="{{ $community->id }}" @if ($data->community_id == $community->id) selected @endif>
                        {{ $community->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-sm-12">
        <div class="form-group">
            <label>Referral</label>
            <select id="referral_id" name="referral_id" class="form-select w-100 select2InsideModal" style="width: 100% !important;"
                data-style="btn-default" required>
                <option selected disabled class="text-gray">Pilih Refferal</option>
                @foreach ($customer as $dataCustomer)
                    <option value="{{$dataCustomer->id}}" @if ($data->referral_id == $dataCustomer->id) selected @endif>{{$dataCustomer->name}} - {{$dataCustomer->telfon}}</option>
                @endforeach
            </select>
        </div>
    </div>

    <script>
        document.getElementById('telfon').addEventListener('keyup', function(event) {
            const input = event.target;

            // Hanya izinkan angka
            if (!/^\d*$/.test(input.value)) {
                input.value = input.value.replace(/[^0-9]/g, ''); // Hapus karakter non-angka
            }
        });

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
