<x-modal addStyle="modal-lg" title="Set Requirement Product" action="{{ $action }}" method="POST">
    @if ($data->id)
        @method('put')
    @endif
    <input type="text" name="input-requirement-product" value="true" hidden>
    {!! $dataTable->table(['class' => 'table table-bordered table-striped table-responsive'], true) !!}

    {!! $dataTable->scripts() !!}
    <script src="https://cdn.datatables.net/2.2.1/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/fixedcolumns/5.0.4/js/dataTables.fixedColumns.js"></script>
    <script src="https://cdn.datatables.net/fixedcolumns/5.0.4/js/fixedColumns.dataTables.js"></script>

    <script>
        $(document).ready(function() {

            console.log(tmpDataProductRequirement);
            tmpDataProductRequirement = []; //kosongkan array terlebih dahulu

            // ambil data product_id dari database jika ada
            let dataModifierGroup = @json($data);
            let dataProduct = JSON.parse(dataModifierGroup.product_id);
            let allIdProduct = @json($dataProduct);

            if (dataProduct) tmpDataProductRequirement.push(...dataProduct);

            // Jalankan DataTables secara otomatis
            $('#notereceiptschedulingrequirementproduct-table').on('preInit.dt', function() {
                $('#notereceiptschedulingrequirementproduct-table thead tr').addClass('bg-light');
            });

            // Checkbox "Select All"
            $('#checkAll').on('change', function() {
                $('.product-checkbox').prop('checked', this.checked);

                if (this.checked) {
                    tmpDataProductRequirement = [];
                    tmpDataProductRequirement = allIdProduct;
                } else {
                    tmpDataProductRequirement = [];
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
                const checkedCheckboxes = tmpDataProductRequirement.length; // Checkbox yang dicentang
                console.log(totalCheckboxes);
                console.log(checkedCheckboxes);
                $('#checkAll').prop('checked', totalCheckboxes > 0 && totalCheckboxes ===
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
                $('#checkAll').on('change', function() {
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
                    console.log("masuk ke hapus")
                    tmpDataProductRequirement = tmpDataProductRequirement.filter(id => id != productId);
                }

                console.log(tmpDataProductRequirement);
            });

            // Kirim daftar tmpDataProductRequirement saat DataTable melakukan request AJAX
            $('#notereceiptschedulingrequirementproduct-table').on('preXhr.dt', function(e, settings, data) {
                // console.log(data);
                data.checkedProducts =
                    tmpDataProductRequirement; // Tambahkan tmpDataProductRequirement ke request
            });
        });

        // var tableTest = $('#notereceiptschedulingrequirementproduct-table').DataTable();
        // // Misal untuk kolom index 1 (kolom yang akan difilter)
        // var columnIdx = 1;

        // // Ambil kolom yang diinginkan dari instance DataTable
        // var column = tableTest.column(columnIdx);

        // // Ambil selector footer <th> dari kolom tersebut, misal:
        // var footerCell = $(column.footer());

        // // Buat dropdown select dan tambahkan ke footer
        // var select = $('<select><option value="">All</option></select>')
        //     .appendTo(footerCell.empty())
        //     .on('change', function() {
        //         var val = $.fn.dataTable.util.escapeRegex($(this).val());

        //         // Cari sesuai pilihan dropdown, regex exact match
        //         column.search(val ? '^' + val + '$' : '', true, false).draw();
        //     });

        // // Isi opsi dropdown dengan data unik kolom
        // column.data().unique().sort().each(function(d) {
        //     select.append('<option value="' + d + '">' + d + '</option>');
        //     console.log("masuk kah")
        // });
    </script>

</x-modal>
