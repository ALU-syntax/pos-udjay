<div class="ob-row-actions" role="group" aria-label="Aksi pengaturan outlet">
    @can('update order-table/outlet-settings')
        <a href="{{ route('order-table/outlet-settings/edit', $setting->id) }}" class="ob-icon-btn ob-icon-btn--sm action"
            title="Edit pengaturan" aria-label="Edit pengaturan"><i class="fas fa-pen" aria-hidden="true"></i></a>
        <a href="{{ route('order-table/outlet-settings/toggle', $setting->id) }}"
            class="ob-icon-btn ob-icon-btn--sm toggle-outlet-order {{ $setting->forced_close ? 'ob-icon-btn--success' : 'ob-icon-btn--danger' }}"
            data-paused="{{ $setting->forced_close ? 1 : 0 }}"
            title="{{ $setting->forced_close ? 'Buka order' : 'Pause order' }}"
            aria-label="{{ $setting->forced_close ? 'Buka order' : 'Pause order' }}">
            <i class="fas {{ $setting->forced_close ? 'fa-play' : 'fa-pause' }}" aria-hidden="true"></i>
        </a>
    @endcan
</div>
