<style>
    .scrollable-row {
        overflow-x: auto;
        /* Enable horizontal scrolling */
        white-space: nowrap;
        /* Prevent content from wrapping to the next line */
    }
</style>
<div class="modal-dialog  modal-xl" id="pilihCustomer">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="btn btn-outline-secondary" id="btn-batal-tambah-customer"
                data-bs-dismiss="modal">Batal</button>
            <h5 class="modal-title mx-auto text-center" id="productModalLabel">

            </h5>
        </div>
        <div class="modal-body w-100">
            <div class="scrollable-row">
                <div class="row">
                    <div class="col-4 readonly">
                        <div class="form-group readonly">
                            <label>Name <span class="text-danger">*</span></label>
                            <input id="name" name="name" type="text" class="form-control" placeholder="Nama"
                                required>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label>Umur <span class="text-danger">*</span></label>
                            <input type="text" id="umur" name="umur" type="number" class="form-control"
                                placeholder="Umur otomatis generate ketika input tanggal lahir" readonly required>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label>Nomor Telfon <span class="text-danger">*</span></label>
                            <input id="telfon" name="telfon" type="text" class="form-control"
                                placeholder="Nomor telfon" required>
                        </div>
                    </div>
                    <div class="col-4 mt-3">
                        <div class="form-group">
                            <label>Email<span class="text-danger">*</span></label>
                            <input id="email" name="email" type="email" class="form-control" placeholder="Email"
                                required>
                        </div>
                    </div>
                    <div class="col-4 mt-3">
                        <div class="form-group">
                            <label>Tanggal Lahir<span class="text-danger">*</span></label>
                            <input id="tanggal_lahir" name="tanggal_lahir" type="date" class="form-control"
                                placeholder="Tanggal lahir" required>
                        </div>
                    </div>
                    <div class="col-4 mt-3">
                        <div class="form-group">
                            <label>Domisili<span class="text-danger">*</span></label>
                            <input id="domisili" name="domisili" type="text" class="form-control"
                                placeholder="Domisili" required>
                        </div>
                    </div>
                    <div class="col-4 mt-3">
                        <div class="form-group">
                            <label>Gender<span class="text-danger">*</span></label>
                            <select id="gender" name="gender" class="form-select w-100" style="color:black;"
                                data-style="btn-default" required>
                                <option selected disabled class="text-gray">Pilih Gender</option>
                                <option value="laki-laki">Laki-Laki</option>
                                <option value="perempuan">Perempuan</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-4 mt-3">
                        <div class="form-group">
                            <label for="community_id">Community</label>
                            <select name="community_id" id="community_id" class="select2InsideModal form-select w-100"
                                style="width: 100% !important;">
                                <option disabled selected>Jika Umum tidak usah dipilih</option>
                                @foreach ($communities as $community)
                                    <option value="{{ $community->id }}">
                                        {{ $community->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-4 mt-3">
                        <div class="form-group">
                            <label>Referral</label>
                            <select id="referral_id" name="referral_id" class="form-select w-100 select2InsideModal"
                                style="width: 100% !important;" data-style="btn-default">
                                <option selected disabled class="text-gray">Pilih Refferal</option>
                                @foreach ($customer as $dataCustomer)
                                    <option value="{{ $dataCustomer->id }}">{{ $dataCustomer->name }} -
                                        {{ $dataCustomer->telfon }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                </div>
            </div>

            <div class="col-12">
                <button class="btn btn-primary btn-lg w-100 my-3" id="btn-tambah-customer">Tambah Customer</button>
            </div>

        </div>
    </div>
</div>


<script>
    $('#btn-batal-tambah-customer').on('click', function() {
        const modal = $('#itemModal');
        modal.modal('hide');
    });

    function calculateAge() {
        const birthDate = new Date($('#tanggal_lahir').val());
        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDifference = today.getMonth() - birthDate.getMonth();

        if (monthDifference < 0 || (monthDifference === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }

        $('#umur').val(age);
    }


    document.getElementById('telfon').addEventListener('keyup', function(event) {
        const input = event.target;

        // Hanya izinkan angka
        if (!/^\d*$/.test(input.value)) {
            input.value = input.value.replace(/[^0-9]/g, ''); // Hapus karakter non-angka
        }
    });

    $(".select2InsideModal").select2({
        dropdownParent: $("#itemModal"), // Pastikan parent diatur untuk modal
        // Callback setelah dropdown dibuka
        closeOnSelect: true,
    }).on("select2:open", function() {
        const selectElement = $(this);
        const dropdown = $(".select2-container--open");
        const container = $(".select2-container--below");

        // Hitung posisi elemen input
        const offset = selectElement.offset();
        const height = selectElement.outerHeight();

        container.css({
            display: "block"
        })
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

    $('#tanggal_lahir').on('change', function() {
        calculateAge();
    });

    $('#btn-tambah-customer').on('click', function() {
        let dataForm = new FormData();
        let namaCustomer = $('#name').val();
        let nomorTelfonCustomer = $('#telfon').val();
        let umurCustomer = $('#umur').val();
        let emailCustomer = $('#email').val();
        let tanggalLahirCustomer = $('#tanggal_lahir').val();
        let domisiliCustomer = $('#domisili').val();
        let genderCustomer = $('#gender').val();
        let communityCustomer = $('#community_id').val();
        let referralCustomer = $('#referral_id').val();

        dataForm.append('name', namaCustomer);
        dataForm.append('umur', umurCustomer);
        dataForm.append('telfon', nomorTelfonCustomer);
        dataForm.append('email', emailCustomer);
        dataForm.append('tanggal_lahir', tanggalLahirCustomer);
        dataForm.append('domisili', domisiliCustomer);
        dataForm.append('gender', genderCustomer);

        if (communityCustomer != null) {
            dataForm.append('community_id', communityCustomer);
        }

        if (referralCustomer != null) {
            dataForm.append('referral_id', referralCustomer);
        }

        console.log(dataForm);

        $.ajax({
            url: "{{ route('membership/customer/store') }}",
            method: "POST",
            data: dataForm,
            contentType: false,
            processData: false,
            beforeSend: function() {
                showLoader();
            },
            success: (res) => {

                if (res.status) {
                    console.log(res);
                    showToast(res.status, res.message);

                    const modal = $('#itemModal');
                    modal.modal('hide');
                }

            },
            complete: function() {
                showLoader(false);
            },
            error: function(err) {
                const errors = err.responseJSON?.errors

                showToast('error', err.responseJSON?.message)
            }
        })
    })
</script>
