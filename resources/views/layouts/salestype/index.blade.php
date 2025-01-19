@extends('layouts.app')
@section('content')
    <div class="main-content">
        <div class="card text-center">
            <h5 class="card-header">Sales Type</h5>
        </div>

        <div class="card mt-4">
            <div class="card-header d-flex justify-content-end">
                <div class="col-4">
                    <select id="filter-outlet" class="form-control select2">
                        <option value="all" selected>-- Semua Outlet --</option>
                        @foreach ($outlets as $outlet)
                            <option value="{{ $outlet->id }}">{{ $outlet->name }}</option>
                        @endforeach
                    </select>
                </div>

                @can('create library/salestype')
                    <a href="{{ route('library/salestype/create') }}" type="button"
                        class="btn btn-primary btn-round ms-auto action"><i class="fa fa-plus"></i> Tambah Sales Type</a>
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
            $("#filter-outlet").select2();
            var success = "{{ session('success') }}";
            const datatable = 'salestype-table';

            $(".select2InsideModal").select2({
                dropdownParent: $("#modal_action")
            });

            handleAction(datatable);
            handleDelete(datatable);

            function changeStatus(id) {
                var checkbox = document.getElementById('set_active' + id);
                var isActive = checkbox.checked ? 1 : 0;

                let baseUrl = `{{route('library/salestype/update-status', ':id')}}`;
                let url = baseUrl.replace(':id', id); // Ganti ':id' dengan nilai dataId

                $.ajax({
                    url: url, // Ganti dengan route yang sesuai  
                    type: 'PUT',
                    data: {
                        status: isActive,
                        _token: '{{ csrf_token() }}' // Pastikan untuk menyertakan token CSRF  
                    },
                    success: function(response) {
                        console.log('Status updated successfully:', response);
                        if(response.success){
                            showToast("success", "Status Data Berhasil diupdate")
                            if(response.data.status == "1"){
                                $(`#label_status_${id}`).text('Aktif')
                            }else{
                                $(`#label_status_${id}`).text('Tidak Aktif')
                            }
                        }
                    },
                    error: function(xhr) {
                        console.error('Error updating status:', xhr);
                    }
                });
            }

            $(document).ready(function() {
                // Event listener untuk dropdown filter
                $('#filter-outlet').on('change', function() {
                    console.log($('#filter-outlet').val());
                    var table = $('#' + datatable).DataTable();

                    // Refresh tabel
                    table.ajax.url("{{ route('library/salestype') }}?outlet=" + $('#filter-outlet').val())
                    .load();
                });
            });
        </script>
    @endpush
@endsection
