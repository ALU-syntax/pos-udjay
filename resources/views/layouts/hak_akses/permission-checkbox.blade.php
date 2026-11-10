@php
    $verb = str($permission->name)->before(' ')->lower()->toString();
    $label = $verbLabels[$verb] ?? str($verb)->headline()->toString();
    $direct = isset($selectedSet[$permission->name]);
    $fromRole = !$direct && isset($inheritedSet[$permission->name]);
    $inputId = 'perm-'.$rowId.'-'.$permission->id;
@endphp

@if ($fromRole)
    {{-- Didapat dari role: hanya informasi, tidak ikut terkirim --}}
    <label class="ob-perm ob-perm--inherited" for="{{ $inputId }}" title="Didapat dari role {{ $roleName }}">
        <input id="{{ $inputId }}" type="checkbox" class="form-check-input" checked disabled>
        <span>{{ $label }}</span>
        <i class="fas fa-lock" aria-hidden="true"></i>
        <span class="visually-hidden">(dari role)</span>
    </label>
@else
    <label class="ob-perm" for="{{ $inputId }}" title="{{ $permission->name }}">
        <input id="{{ $inputId }}" type="checkbox" class="form-check-input perm-item" name="permissions[]"
            value="{{ $permission->name }}" data-group="{{ $groupId }}" data-row="{{ $rowId }}" @checked($direct)>
        <span>{{ $label }}</span>
    </label>
@endif
