@extends('layouts.app')
@section('content')
    <div class="main-content">
        <div class="card text-center">
            <h5 class="card-header">Level Membership</h5>
        </div>

        <div class="card mt-4">
            <div class="card-header d-flex justify-content-end" >

                @can('create membership/level-membership')
                    <a href="{{ route('membership/level-membership/create') }}" type="button" class="btn btn-lg btn-primary btn-round ms-auto action"><i
                            class="fa fa-plus"></i> Tambah Level Membership</a>
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
            const datatable = 'levelmembership-table';

            handleAction(datatable);
            handleDelete(datatable);

        </script>
    @endpush
@endsection
