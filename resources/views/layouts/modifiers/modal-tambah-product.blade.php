<x-modal addStyle="modal-lg" title="Tambah Product Modifiers" action="{{ $action }}" method="POST">
    @if ($data->id)
        @method('put')
    @endif
    {!! $dataTable->table(['class' => 'table table-bordered table-striped table-responsive'], true) !!}

    {!! $dataTable->scripts() !!}
    <script>
        $(document).ready(function() {

            // Jalankan DataTables secara otomatis
            $('#pilihproduct-table').on('preInit.dt', function() {
                $('#pilihproduct-table thead tr').addClass('bg-light');
            });

            // Checkbox "Select All"
            $('#checkAll').on('change', function() {
                $('.product-checkbox').prop('checked', this.checked);
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
                const totalCheckboxes = $('.product-checkbox').length; // Total checkbox di halaman saat ini
                const checkedCheckboxes = $('.product-checkbox:checked').length; // Checkbox yang dicentang
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


            // Variabel untuk menyimpan ID produk yang dicentang
            let checkedProducts = [];

            // ambil data product_id dari database jika ada
            let dataModifierGroup = @json($data);
            let dataProduct = JSON.parse(dataModifierGroup.product_id);
            
            checkedProducts.push(...dataProduct);

            // Event listener untuk checkbox
            $(document).on('change', '.product-checkbox', function() {
                const productId = $(this).val();

                if ($(this).is(':checked')) {
                    // Tambahkan ID ke checkedProducts jika dicentang
                    if (!checkedProducts.includes(productId)) {
                        checkedProducts.push(productId);
                    }
                } else {
                    // Hapus ID dari checkedProducts jika tidak dicentang
                    checkedProducts = checkedProducts.filter(id => id !== productId);
                }

                // console.log(productId);
            });

            // Kirim daftar checkedProducts saat DataTable melakukan request AJAX
            $('#pilihproduct-table').on('preXhr.dt', function(e, settings, data) {
                // console.log(data);
                data.checkedProducts = checkedProducts; // Tambahkan checkedProducts ke request
            });
        });
    </script>

</x-modal>
