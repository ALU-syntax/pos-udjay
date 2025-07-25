@extends('layouts.app')
@section('content')
    <div class="main-content">
        <div class="card text-center">
            <h5 class="card-header">Note Receipt Scheduling</h5>
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
                {{-- <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasMenu"
                    aria-controls="offcanvasMenu">
                    Open Menu
                </button> --}}
                @can('create library/note-receipt-scheduling')
                    <a href="{{ route('library/note-receipt-scheduling/create') }}" type="button"
                        class="btn btn-primary btn-round ms-auto action"><i class="fa fa-plus"></i>Tambah Nota Struk Scheduling</a>
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
            const datatable = 'notereceiptscheduling-table';

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
                    table.ajax.url("{{ route('library/note-receipt-scheduling') }}?outlet=" + $('#filter-outlet').val()).load();
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
