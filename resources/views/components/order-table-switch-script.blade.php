{{-- Penanganan switch "Order Table" (components/order-table-switch). $datatable: id tabel DataTables. --}}
<script>
    $(document).off('change.orderTableSwitch').on('change.orderTableSwitch', '.order-table-switch', function () {
        const input = this;
        const show = input.checked;
        input.disabled = true;

        $.ajax({
            url: input.dataset.url,
            method: 'POST',
            data: { _method: 'PATCH', show: show ? 1 : 0 },
            success: function (response) {
                showToast(response.status || 'success', response.message || 'Status Order Table diperbarui');
                window.LaravelDataTables['{{ $datatable }}'].ajax.reload(null, false);
            },
            error: function (xhr) {
                // Kembalikan posisi switch agar tampilan sesuai data tersimpan.
                input.checked = !show;
                showToast('error', xhr.responseJSON?.message || 'Gagal memperbarui status Order Table');
            },
            complete: function () {
                input.disabled = false;
            }
        });
    });
</script>
