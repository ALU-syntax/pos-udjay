<div class="ob-row-actions" role="group" aria-label="Aksi customer {{ $customer->name }}">
    <a href="{{ route('b2b/customers/show', $customer->id) }}" class="ob-icon-btn ob-icon-btn--sm" title="Detail customer" aria-label="Detail customer">
        <i class="fas fa-eye" aria-hidden="true"></i>
    </a>
    @can('update b2b/customers')
        <a href="{{ route('b2b/customers/edit', $customer->id) }}" class="ob-icon-btn ob-icon-btn--sm" title="Edit customer" aria-label="Edit customer">
            <i class="fas fa-pen" aria-hidden="true"></i>
        </a>
        <a href="{{ route('b2b/customers/toggle', $customer->id) }}" data-b2b-confirm data-table="b2b-customer-table"
            data-title="{{ $customer->is_active ? 'Nonaktifkan customer?' : 'Aktifkan customer?' }}"
            data-text="{{ $customer->is_active ? 'Customer nonaktif tidak bisa dipilih di Kasir B2B.' : 'Customer akan bisa dipilih lagi di Kasir B2B.' }}"
            class="ob-icon-btn ob-icon-btn--sm {{ $customer->is_active ? 'ob-icon-btn--danger' : 'ob-icon-btn--success' }}"
            title="{{ $customer->is_active ? 'Nonaktifkan' : 'Aktifkan' }}" aria-label="{{ $customer->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
            <i class="fas {{ $customer->is_active ? 'fa-eye-slash' : 'fa-check' }}" aria-hidden="true"></i>
        </a>
    @endcan
</div>
