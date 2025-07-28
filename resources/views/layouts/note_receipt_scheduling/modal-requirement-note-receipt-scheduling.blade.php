<x-modal addStyle="modal-lg" title="Set Requirement Product" action="{{ $action }}" method="POST">
    @if ($data->id)
        @method('put')
    @endif
    <input type="text" name="input-requirement-product" value="true" hidden>
    {!! $dataTable->table(['class' => 'table table-bordered table-striped table-responsive'], true) !!}

    {!! $dataTable->scripts() !!}

    <script>
        $(document).ready(function() {
            tmpDataProductRequirement = []; //kosongkan array terlebih dahulu

            // ambil data product_id dari database jika ada
            let dataNoteBilling = @json($data);
            let dataProduct = JSON.parse(dataNoteBilling.product_id);
            let allIdProduct = @json($dataProduct);

            if (dataProduct) tmpDataProductRequirement.push(...dataProduct);

            // Jalankan DataTables secara otomatis
            $('#notereceiptschedulingrequirementproduct-table').on('preInit.dt', function() {
                $('#notereceiptschedulingrequirementproduct-table thead tr').addClass('bg-light');
            });

            // Checkbox "Select All"
            $('#checkAllRequirement').on('change', function() {
                $('.product-checkbox').prop('checked', this.checked);

                if (this.checked) {
                    tmpDataProductRequirement = [];
                    tmpDataProductRequirement = allIdProduct;
                } else {
                    tmpDataProductRequirement = [];
                }

            });


            $(document).on('change', '.product-checkbox', function() {
                const totalCheckboxes = $('.product-checkbox').length; // Total checkbox yang ada
                const checkedCheckboxes = $('.product-checkbox:checked').length; // Checkbox yang tercentang

                // Jika semua checkbox tercentang, centang #checkAll, jika tidak, hilangkan centang
                $('#checkAllRequirement').prop('checked', totalCheckboxes === checkedCheckboxes);
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
                const checkedCheckboxes = tmpDataProductRequirement.length; // Checkbox yang dicentang
                $('#checkAllRequirement').prop('checked', totalCheckboxes > 0 && totalCheckboxes ===
                    checkedCheckboxes); // Update status
            }

            // Re-attach event listener dan update status #checkAll setelah DataTables menggambar ulang
            $('#notereceiptschedulingrequirementproduct-table').on('draw.dt', function() {
                // Pastikan #checkAll tidak tercentang jika tidak ada checkbox yang tercentang
                updateCheckAll();

                // Event listener untuk checkbox individual
                $('.product-checkbox').on('change', function() {
                    updateCheckAll();
                });

                // Event listener untuk #checkAll
                $('#checkAllRequirement').on('change', function() {
                    $('.product-checkbox').prop('checked', this.checked);
                });
            });

            // Event listener untuk checkbox
            $(document).on('change', '.product-checkbox', function() {
                const productId = $(this).val();

                if ($(this).is(':checked')) {
                    // Tambahkan ID ke tmpDataProductRequirement jika dicentang
                    if (!tmpDataProductRequirement.includes(productId)) {
                        tmpDataProductRequirement.push(productId);
                    }
                } else {
                    // Hapus ID dari tmpDataProductRequirement jika tidak dicentang
                    tmpDataProductRequirement = tmpDataProductRequirement.filter(id => id != productId);
                }

                console.log(tmpDataProductRequirement);
            });

            // Kirim daftar tmpDataProductRequirement saat DataTable melakukan request AJAX
            $('#notereceiptschedulingrequirementproduct-table').on('preXhr.dt', function(e, settings, data) {
                console.log("masuk ajax")
                data.checkedProducts =
                    tmpDataProductRequirement; // Tambahkan tmpDataProductRequirement ke request
            });

            var table = window.LaravelDataTables["notereceiptschedulingrequirementproduct-table"];

            // Gunakan initComplete dari laravel-datatables event
            table.on('init.dt', function() {
                var columnIdx = 2;
                var column = table.column(columnIdx);
                var footerCell = $(column.footer());

                console.log('Footer kolom 2:', footerCell); // cek elemen footer

                if (footerCell.length === 0) {
                    console.error('Footer kolom 2 tidak tersedia!');
                    return;
                }

                if (footerCell.find('select').length === 0) {
                    var select = $('<select><option value="">All</option></select>')
                        .appendTo(footerCell.empty())
                        .on('change', function() {
                            var val = $.fn.dataTable.util.escapeRegex($(this).val());
                            column.search(val ? '^' + val + '$' : '', true, false).draw();
                        });

                    column.data().unique().sort().each(function(d) {
                        if (d) select.append('<option value="' + d + '">' + d + '</option>');
                    });
                    console.log('Dropdown dipasang untuk kolom 2');
                }

            });


        });
    </script>

</x-modal>
