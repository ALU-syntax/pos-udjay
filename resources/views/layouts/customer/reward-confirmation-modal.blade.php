<x-modal title="Reward Confirmation" addStyle="modal-lg" action="{{ $action }}" method="POST" update="true">
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>Nama</th>
                    <th>Checkbox</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($data as $reward)
                    <tr>
                        <td>{{$reward['name']}}
                            <input type="text" name="reward_id[]" value="{{$reward['id']}}" hidden>
                        </td>
                        <td>
                            <input type="checkbox" class="form-check-input" id="accept" name="accept[]" @if($reward['accept']) checked @endif>
                        </td>
                    </tr>
                @endforeach
                <!-- Tambahkan baris lain sesuai kebutuhan -->
            </tbody>
        </table>
    </div>

    <div class="row">
        <div class="col-9">
            <input type="file" name="photo">
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
