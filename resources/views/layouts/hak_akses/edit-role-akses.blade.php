@extends('layouts.app')
@section('content')
    <div class="card mb-4">
        @if (session()->has('error'))
            <div class="alert alert-danger mt-2 mx-4" role="alert">
                {{ session('error') }}
            </div>
        @endif
        <div class="card-body">
            <form action="{{ route('employee/hak-akses/role/update', $data->id) }}" method="POST"
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
                                        <td class="text-nowrap fw-medium">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input permission-parent-check" type="checkbox"
                                                    id="permission-parent-{{ $mm->id }}"
                                                    data-parent-id="{{ $mm->id }}">
                                                <label class="form-check-label fw-medium"
                                                    for="permission-parent-{{ $mm->id }}">
                                                    {{ $mm->name }} <br> <small>(Parent)</small>
                                                </label>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex">
                                                @foreach ($mm->permissions as $permission)
                                                    <div class="form-check me-3 me-lg-5">
                                                        <input class="form-check-input checkbox-item" type="checkbox"
                                                            name="permissions[]" value="{{ $permission->name }}"
                                                            data-parent-id="{{ $mm->id }}"
                                                            data-row-id="menu-{{ $mm->id }}"
                                                            @checked($data->hasPermissionTo($permission->name))
                                                            id="permission-{{ $mm->id . '-' . $permission->id }}">
                                                        <label class="form-check-label"
                                                            for="permission-{{ $mm->id . '-' . $permission->id }}">
                                                            {{ explode(' ', $permission->name)[0] }}
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                    </tr>
                                    @foreach ($mm->subMenus as $sm)
                                        <tr>
                                            <td class="text-nowrap fw-medium ps-4">
                                                <div class="form-check mb-0">
                                                    <input class="form-check-input permission-row-check" type="checkbox"
                                                        id="permission-row-{{ $sm->id }}"
                                                        data-parent-id="{{ $mm->id }}"
                                                        data-row-id="submenu-{{ $sm->id }}">
                                                    <label class="form-check-label fw-medium"
                                                        for="permission-row-{{ $sm->id }}">
                                                        • {{ $sm->name }}
                                                    </label>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex">
                                                    @foreach ($sm->permissions as $permission)
                                                        <div class="form-check me-3 me-lg-5">
                                                            <input class="form-check-input checkbox-item" type="checkbox"
                                                                name="permissions[]" value="{{ $permission->name }}"
                                                                data-parent-id="{{ $mm->id }}"
                                                                data-row-id="submenu-{{ $sm->id }}"
                                                                @checked($data->hasPermissionTo($permission->name))
                                                                id="permission-{{ $sm->id . '-' . $permission->id }}">
                                                            <label class="form-check-label"
                                                                for="permission-{{ $sm->id . '-' . $permission->id }}">
                                                                {{ explode(' ', $permission->name)[0] }}
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
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
                const $selectAll = $('#selectAll');

                function permissionItemsBy(key, value) {
                    return $('.checkbox-item').filter(function() {
                        return String($(this).attr('data-' + key)) === String(value);
                    });
                }

                function setCheckState($checkbox, $items) {
                    const total = $items.length;
                    const checked = $items.filter(':checked').length;

                    $checkbox.prop('checked', total > 0 && checked === total);
                    $checkbox.prop('indeterminate', checked > 0 && checked < total);
                }

                function updateSelectAll() {
                    setCheckState($selectAll, $('.checkbox-item'));
                }

                function updateRowCheck(rowId) {
                    const $rowCheck = $('.permission-row-check').filter(function() {
                        return String($(this).attr('data-row-id')) === String(rowId);
                    });

                    setCheckState($rowCheck, permissionItemsBy('row-id', rowId));
                }

                function updateParentCheck(parentId) {
                    const $parentCheck = $('.permission-parent-check').filter(function() {
                        return String($(this).attr('data-parent-id')) === String(parentId);
                    });

                    setCheckState($parentCheck, permissionItemsBy('parent-id', parentId));
                }

                function refreshPermissionUtilities() {
                    $('.permission-row-check').each(function() {
                        updateRowCheck($(this).attr('data-row-id'));
                    });

                    $('.permission-parent-check').each(function() {
                        updateParentCheck($(this).attr('data-parent-id'));
                    });

                    updateSelectAll();
                }

                $('.checkbox-item').change(function() {
                    updateRowCheck($(this).attr('data-row-id'));
                    updateParentCheck($(this).attr('data-parent-id'));
                    updateSelectAll();
                });

                $('.permission-row-check').change(function() {
                    permissionItemsBy('row-id', $(this).attr('data-row-id')).prop('checked', this.checked);
                    refreshPermissionUtilities();
                });

                $('.permission-parent-check').change(function() {
                    permissionItemsBy('parent-id', $(this).attr('data-parent-id')).prop('checked', this.checked);
                    refreshPermissionUtilities();
                });

                $selectAll.change(function() {
                    $('.checkbox-item').prop('checked', this.checked);
                    refreshPermissionUtilities();
                });

                $('form').on('reset', function() {
                    setTimeout(refreshPermissionUtilities, 0);
                });

                refreshPermissionUtilities();
            });
        </script>
    @endpush
@endsection
