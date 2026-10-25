<div class="ob-row-actions" role="group" aria-label="Aksi banner {{ $banner->title }}">
    @can('update order-table/banners')
        <a href="{{ route('order-table/banners/edit', $banner->id) }}" class="ob-icon-btn ob-icon-btn--sm action"
            title="Edit banner" aria-label="Edit banner"><i class="fas fa-pen" aria-hidden="true"></i></a>
        <a href="{{ route('order-table/banners/toggle', $banner->id) }}"
            class="ob-icon-btn ob-icon-btn--sm toggle-banner {{ $banner->status ? 'ob-icon-btn--danger' : 'ob-icon-btn--success' }}"
            title="{{ $banner->status ? 'Nonaktifkan banner' : 'Aktifkan banner' }}"
            aria-label="{{ $banner->status ? 'Nonaktifkan banner' : 'Aktifkan banner' }}">
            <i class="fas {{ $banner->status ? 'fa-eye-slash' : 'fa-eye' }}" aria-hidden="true"></i>
        </a>
    @endcan
</div>
