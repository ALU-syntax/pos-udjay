@extends('layouts.app')
@section('content')
    <div class="main-content">
        <div class="card text-center">
            <h5 class="card-header">Sales Type</h5>
        </div>

        <div class="card mt-4">
            <div class="card-header d-flex justify-content-end" style="zoom: 0.8">
                @can('create library/salestype')
                    <a href="{{ route('library/salestype/create') }}" type="button" class="btn btn-primary btn-round ms-auto action"><i
                            class="fa fa-plus"></i>Tambah Sales Type</a>
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
            const datatable = 'salestype-table';

            $(".select2InsideModal").select2({
                dropdownParent: $("#modal_action")
            });
            
            handleAction(datatable);
            handleDelete(datatable);

        </script>
    @endpush
@endsection
