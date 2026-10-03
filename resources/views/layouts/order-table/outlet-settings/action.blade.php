<div class="order-table-actions" role="group" aria-label="Aksi pengaturan outlet">
    @can('update order-table/outlet-settings')
        <a href="{{ route('order-table/outlet-settings/edit', $setting->id) }}"
            class="btn btn-sm btn-outline-primary action" title="Edit pengaturan" aria-label="Edit pengaturan">
            <i class="fa fa-pen"></i>
        </a>
        <a href="{{ route('order-table/outlet-settings/toggle', $setting->id) }}"
            class="btn btn-sm {{ $setting->forced_close ? 'btn-outline-success' : 'btn-outline-warning' }} toggle-outlet-order"
            data-paused="{{ $setting->forced_close ? 1 : 0 }}"
            title="{{ $setting->forced_close ? 'Buka order' : 'Pause order' }}"
            aria-label="{{ $setting->forced_close ? 'Buka order' : 'Pause order' }}">
            <i class="fa {{ $setting->forced_close ? 'fa-play' : 'fa-pause' }}"></i>
        </a>
    @endcan
</div>
