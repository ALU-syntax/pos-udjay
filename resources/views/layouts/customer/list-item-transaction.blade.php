<style>
    tr.shown {
        background-color: #f9f9f9 !important;
    }
</style>

<div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
        <div class="modal-header border-0">
            <h5 class="modal-title">
                <span class="fw-mediumbold">
                    History Exp Use</span>

            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            {!! $dataTable->table(['class' => 'table table-bordered table-striped table-responsive'], true) !!}

            {!! $dataTable->scripts() !!}

        </div>
        <div class="modal-footer border-0">
            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
</div>

<script>
    $('#modal_action').on('shown.bs.modal', function () {

        let table = window.LaravelDataTables['detailitemtransaction-table'];

        // Unbind event lama untuk menghindari dobel listener
        $('#detailitemtransaction-table tbody').off('click').on('click', '.expand', function () {
            let tr = $(this);
            let row = table.row(tr);

            if (row.child.isShown()) {
                // Jika subrow terbuka → tutup
                row.child.hide();
                tr.removeClass('shown');
            } else {
                // Tampilkan subrow baru
                row.child(format(row.data())).show();
                tr.addClass('shown');
            }
        });
    });

    // Fungsi format: menampilkan detail di subrow
    function format(d) {
        let rows = "";

        const modifierList = d.modifier_id ? d.modifier_id : [];
        let decoded = modifierList.replaceAll("&quot;", '"');
        let data = JSON.parse(decoded);

        data.forEach(item => {
            rows += `
            <tr>
                <th>${item.nama}</th>
                <td>${d.qty ?? '-'}</td>
                <td>${item.harga}</td>
                <td>${item.harga}</td>
            </tr>
        `;
        });
        return `
        <div class="p-3 bg-light">
            <table class="table table-sm table-bordered mb-0">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Qty</th>
                        <th>Harga</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    ${rows}
                </tbody>
            </table>
        </div>
    `;
    }


</script>