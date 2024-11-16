@extends('layouts.app')
@section('content')
    <div class="card mb-4">
        @if (session()->has('error'))
            <div class="alert alert-danger mt-2 mx-4" role="alert">
                {{ session('error') }}
            </div>
        @endif
        <div class="card-body">
            <form action="{{ route('konfigurasi/hak-akses/role/update', $data->id) }}" method="POST"
                class="row g-3 fv-plugins-bootstrap5 fv-plugins-framework">
                @csrf
                {{-- <div class="col-12 mb-4 fv-plugins-icon-container">
                <label class="form-label" for="modalRoleName">Role Name</label>
                <input type="text" class="form-control search"
                    placeholder="Enter a role name" tabindex="-1">
                <div class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback">
                </div>
            </div> --}}
                <div class="col-12">
                    <h4>Role {{ $data->name }} Permissions</h4>
                    <!-- Permission table -->
                    <div class="table-responsive">
                        <table class="table table-flush-spacing">
                            <tbody id="role_permissions">
                                <tr>
                                    <td class="text-nowrap fw-medium">Administrator Access <i
                                            class="bx bx-info-circle bx-xs" data-bs-toggle="tooltip" data-bs-placement="top"
                                            aria-label="Allows a full access to the system"
                                            data-bs-original-title="Allows a full access to the system"></i>
                                    </td>
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="selectAll">
                                            <label class="form-check-label" for="selectAll">
                                                Select All
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                                @foreach ($menus as $mm)
                                    <tr>
                                        <td class="text-nowrap fw-medium">{{ $mm->name }} <br> <small>(Parent)</small>
                                        </td>
                                        <td>
                                            <div class="d-flex">
                                                @foreach ($mm->permissions as $permission)
                                                    <div class="form-check me-3 me-lg-5">
                                                        <input class="form-check-input checkbox-item" type="checkbox"
                                                            name="permissions[]" value="{{ $permission->name }}"
                                                            @checked($data->hasPermissionTo($permission->name)) type="checkbox"
                                                            id="permission-{{ $mm->id . '-' . $permission->id }}">
                                                        <label class="form-check-label"
                                                            for="{{ $mm->id . '-' . $permission->id }}">
                                                            {{ explode(' ', $permission->name)[0] }}
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                    </tr>
                                    @foreach ($mm->subMenus as $sm)
                                        <tr>
                                            {{-- <td>&nbsp; &nbsp; &nbsp; &nbsp; <x-form.checkbox
                            id="parent{{ $mm->id . $sm->id }}" label="{{ $sm->name }}"
                            class="parent" /></td>
                    <td> --}}
                                            <td class="text-nowrap fw-medium ps-4">- {{ $sm->name }}</td>
                                            <td>
                                                <div class="d-flex">
                                                    @foreach ($sm->permissions as $permission)
                                                        <div class="form-check me-3 me-lg-5">
                                                            <input class="form-check-input checkbox-item" type="checkbox"
                                                                name="permissions[]" value="{{ $permission->name }}"
                                                                @checked($data->hasPermissionTo($permission->name)) type="checkbox"
                                                                id="permission-{{ $sm->id . '-' . $permission->id }}">
                                                            <label class="form-check-label"
                                                                for="{{ $sm->id . '-' . $permission->id }}">
                                                                {{ explode(' ', $permission->name)[0] }}
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </td>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <!-- Permission table -->
                </div>
                <div class="col-12 text-center">
                    <button type="submit" class="btn btn-primary me-sm-3 me-1">Submit</button>
                    <button type="reset" class="btn btn-label-secondary" data-bs-dismiss="modal"
                        aria-label="Close">Reset</button>
                </div>
            </form>
        </div>
    </div>

    @push('js')
        <script>
            $(document).ready(function() {
                // Ketika checkbox "check all" diubah statusnya
                $('#selectAll').change(function() {
                    // Jika checkbox "check all" di-check
                    if (this.checked) {
                        // Set semua checkbox dengan kelas .checkbox-item menjadi checked
                        $('.checkbox-item').each(function() {
                            this.checked = true;
                        });
                    } else { // Jika checkbox "check all" tidak di-check
                        // Set semua checkbox dengan kelas .checkbox-item menjadi unchecked
                        $('.checkbox-item').each(function() {
                            this.checked = false;
                        });
                    }
                });

                // Ketika salah satu checkbox dengan kelas .checkbox-item diubah statusnya
                $('.checkbox-item').change(function() {
                    // Cek apakah semua checkbox dengan kelas .checkbox-item telah di-check
                    if ($('.checkbox-item:checked').length == $('.checkbox-item').length) {
                        // Jika semua checkbox telah di-check, check checkbox "check all"
                        $('#selectAll').prop('checked', true);
                    } else {
                        // Jika tidak semua checkbox di-check, uncheck checkbox "check all"
                        $('#selectAll').prop('checked', false);
                    }
                });
            });
        </script>
    @endpush
@endsection
