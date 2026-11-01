{{-- ob-page memberi token & gaya report ke modal (modal dirender di luar .main-content) --}}
<x-modal addStyle="modal-lg ob-page ob-modal" title="Atur Produk: {{ $data->name }}" action="{{ $action }}" method="POST" update="1">
    @if ($data->id)
        @method('put')
    @endif
    <div class="col-12">
        <p class="ob-modal__desc">Centang produk yang memakai grup modifier ini. Hanya produk di outlet yang sama yang ditampilkan.</p>
    </div>
    <input type="text" name="input-modifier-product" value="true" hidden>
    <div class="col-12 table-responsive">
        {!! $dataTable->table(['class' => 'table w-100'], true) !!}
    </div>

    {!! $dataTable->scripts() !!}
    <script>
        $(document).ready(function() {

            console.log(tmpDataProductModifier);
            tmpDataProductModifier = []; //kosongkan array terlebih dahulu

            // ambil data product_id dari database jika ada
            let dataModifierGroup = @json($data);
            let dataProduct = JSON.parse(dataModifierGroup.product_id);
            let allIdProduct = @json($dataProduct);

            if (dataProduct) tmpDataProductModifier.push(...dataProduct);

            // Jalankan DataTables secara otomatis
            $('#pilihproduct-table').on('preInit.dt', function() {
                $('#pilihproduct-table thead tr').addClass('bg-light');
            });

            // Checkbox "Select All"
            $('#checkAll').on('change', function() {
                $('.product-checkbox').prop('checked', this.checked);

                if(this.checked){
                    tmpDataProductModifier = [];
                    tmpDataProductModifier = allIdProduct;
                }else{
                    tmpDataProductModifier = [];
                }

            });

            // $(document).on('change', '#checkAll', function() {
            //     $('.product-checkbox').prop('checked', this.checked);
            // });

            $(document).on('change', '.product-checkbox', function() {
                const totalCheckboxes = $('.product-checkbox').length; // Total checkbox yang ada
                const checkedCheckboxes = $('.product-checkbox:checked').length; // Checkbox yang tercentang

                // Jika semua checkbox tercentang, centang #checkAll, jika tidak, hilangkan centang
                $('#checkAll').prop('checked', totalCheckboxes === checkedCheckboxes);
            });

            // Simpan Pilihan
            $('#saveSelection').on('click', function() {
                let selectedProducts = [];
                $('.product-checkbox:checked').each(function() {
                    selectedProducts.push($(this).val());
                });

                console.log('Produk yang dipilih:', selectedProducts);
                $('#productModal').modal('hide');
            });

            // Fungsi untuk memperbarui status #checkAll
            function updateCheckAll() {
                const totalCheckboxes = allIdProduct.length; // Total checkbox di halaman saat ini
                const checkedCheckboxes = tmpDataProductModifier.length; // Checkbox yang dicentang
                console.log(totalCheckboxes);
                console.log(checkedCheckboxes);
                $('#checkAll').prop('checked', totalCheckboxes > 0 && totalCheckboxes ===
                    checkedCheckboxes); // Update status
            }

            // Re-attach event listener dan update status #checkAll setelah DataTables menggambar ulang
            $('#pilihproduct-table').on('draw.dt', function() {
                // Pastikan #checkAll tidak tercentang jika tidak ada checkbox yang tercentang
                updateCheckAll();

                // Event listener untuk checkbox individual
                $('.product-checkbox').on('change', function() {
                    updateCheckAll();
                });

                // Event listener untuk #checkAll
                $('#checkAll').on('change', function() {
                    $('.product-checkbox').prop('checked', this.checked);
                });
            });




            // Event listener untuk checkbox
            $(document).on('change', '.product-checkbox', function() {
                const productId = $(this).val();

                if ($(this).is(':checked')) {
                    // Tambahkan ID ke tmpDataProductModifier jika dicentang
                    if (!tmpDataProductModifier.includes(productId)) {
                        tmpDataProductModifier.push(productId);
                    }
                } else {
                    // Hapus ID dari tmpDataProductModifier jika tidak dicentang
                    console.log("masuk ke hapus")
                    tmpDataProductModifier = tmpDataProductModifier.filter(id => id != productId);
                }

                console.log(tmpDataProductModifier);
            });

            // Kirim daftar tmpDataProductModifier saat DataTable melakukan request AJAX
            $('#pilihproduct-table').on('preXhr.dt', function(e, settings, data) {
                // console.log(data);
                data.checkedProducts =
                tmpDataProductModifier; // Tambahkan tmpDataProductModifier ke request
            });
        });
    </script>

</x-modal>
