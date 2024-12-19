@extends('layouts.app')
@section('content')
    <div class="main-content">
        <div class="card text-center">
            <h5 class="card-header">Discount</h5>
        </div>
        @if (session()->has('success'))
            <div class="alert alert-success mt-2" role="alert">
                {{ session('success') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="alert alert-danger mt-2" role="alert">
                {{ session('error') }}
            </div>
        @endif
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

                @can('create library/discount')
                    <a href="{{ route('library/discount/create') }}" type="button"
                        class="btn btn-primary btn-round ms-auto action"><i class="fa fa-plus"></i>Tambah Discount</a>
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
            const datatable = 'discountdatatables-table';

            $(".select2InsideModal").select2({
                dropdownParent: $("#modal_action")
            });

            handleAction(datatable);
            handleDelete(datatable);

            $(document).ready(function() {
                // Event listener untuk dropdown filter
                $('#filter-outlet').on('change', function() {
                    console.log($('#filter-outlet').val());
                    var table = $('#' + datatable).DataTable();

                    // Refresh tabel
                    table.ajax.url("{{ route('library/discount') }}?outlet=" + $('#filter-outlet').val())
                    .load();
                });
            });
            if (success) {
                Swal.fire({
                    title: 'Success!',
                    // text: 'Data User Berhasil Disimpan',
                    text: success,
                    icon: 'success',
                    type: 'success',
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    },
                    buttonsStyling: false
                });
            }
        </script>
    @endpush
@endsection
