<div class="order-table-actions" role="group" aria-label="Aksi banner {{ $banner->title }}">
    @can('update order-table/banners')
        <a href="{{ route('order-table/banners/edit', $banner->id) }}" class="btn btn-sm btn-outline-primary action"
            title="Edit banner" aria-label="Edit banner"><i class="fa fa-pen"></i></a>
        <a href="{{ route('order-table/banners/toggle', $banner->id) }}"
            class="btn btn-sm {{ $banner->status ? 'btn-outline-danger' : 'btn-outline-success' }} toggle-banner"
            title="{{ $banner->status ? 'Nonaktifkan banner' : 'Aktifkan banner' }}"
            aria-label="{{ $banner->status ? 'Nonaktifkan banner' : 'Aktifkan banner' }}">
            <i class="fa {{ $banner->status ? 'fa-toggle-off' : 'fa-toggle-on' }}"></i>
        </a>
    @endcan
</div>
