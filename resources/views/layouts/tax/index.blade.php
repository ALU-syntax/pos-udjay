@extends('layouts.app')
@section('content')
    <div class="main-content">
        <div class="card text-center">
            <h5 class="card-header">Taxes</h5>
        </div>

        <div class="card mt-4">
            <div class="card-header d-flex justify-content-end" style="zoom: 0.8">
                @can('create library/tax')
                    <a href="{{ route('library/tax/create') }}" type="button" class="btn btn-primary btn-round ms-auto action"><i
                            class="fa fa-plus"></i>Tambah Tax</a>
                @endcan
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    {!! $dataTable->table() !!}
                </div>
            </div>
        </div>
    </div>


    @push('js')
        {!! $dataTable->scripts() !!}
        <script>
            var success = "{{ session('success') }}";
            const datatable = 'taxdatatables-table';

            $(".select2InsideModal").select2({
                dropdownParent: $("#modal_action")
            });
            
            handleAction(datatable);
            handleDelete(datatable);

            // $('#form').submit(function(e) {
            //     e.preventDefault();
            //     $.ajax({
            //         type: "POST",
            //         url: "{{ route('library/tax/store') }}",
            //         data: $(this).serialize(),
            //         dataType: "JSON",
            //         beforeSend: function() {
            //             showblockUI();
            //         },
            //         complete: function() {
            //             hideblockUI();
            //         },
            //         success: function(response) {
            //             if (response.status) {
            //                 toastr.success("Biaya layanan berhasil diperbaharui");
            //                 setTimeout(function() {
            //                     window.location.reload();
            //                 }, 1000);
            //             } else {
            //                 $.each(response.errors, function(key, value) {
            //                     $('[name="' + key + '"]').addClass('is-invalid');
            //                     if (key == 'ppn') {
            //                         $('[name="' + key + '"]').next().next().text(value);
            //                     } else {
            //                         $('[name="' + key + '"]').next().text(value);
            //                     }
            //                     if (value == "") {
            //                         $('[name="' + key + '"]').removeClass('is-invalid');
            //                         $('[name="' + key + '"]').addClass('is-valid');
            //                     }
            //                 });
            //             }
            //         },
            //         error: function(jqXHR, textStatus, errorThrown, exception) {
            //             var msg = '';
            //             if (jqXHR.status === 0) {
            //                 msg = 'Not connect.\n Verify Network.';
            //             } else if (jqXHR.status == 404) {
            //                 msg = 'Requested page not found. [404]';
            //             } else if (jqXHR.status == 500) {
            //                 msg = 'Internal Server Error [500].';
            //             } else if (exception === 'parsererror') {
            //                 msg = 'Requested JSON parse failed.';
            //             } else if (exception === 'timeout') {
            //                 msg = 'Time out error.';
            //             } else if (exception === 'abort') {
            //                 msg = 'Ajax request aborted.';
            //             } else {
            //                 msg = 'Uncaught Error.\n' + jqXHR.responseText;
            //             }
            //             alert(msg);
            //         }
            //     });
            // });
        </script>
    @endpush
@endsection
