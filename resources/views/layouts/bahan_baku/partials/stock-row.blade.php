{{-- Satu baris Stok per Lokasi di detail bahan baku; $qty dari detail-modal --}}
@php
    $inventory = $balance->inventory;
    $inventoryActive = $inventory && ! $inventory->trashed() && $inventory->is_active;
@endphp
<tr class="{{ $inventoryActive ? '' : 'wh-row-inactive' }}">
    <td>
        <div class="wh-cell-stack">
            <span class="wh-strong">{{ $inventory?->name ?? 'Lokasi #' . $balance->inventory_id }}</span>
            @if (! $inventory || $inventory->trashed())
                <span class="ob-cell-meta">Lokasi dihapus</span>
            @elseif (! $inventory->is_active)
                <span class="ob-cell-meta">Lokasi nonaktif</span>
            @elseif ($inventory->code)
                <span class="ob-cell-meta">{{ $inventory->code }}</span>
            @endif
        </div>
    </td>
    <td class="text-end wh-num wh-strong">{{ $qty($balance->qty_available) }}</td>
    <td class="text-end wh-num">{{ (float) $balance->qty_reserved > 0 ? $qty($balance->qty_reserved) : '-' }}</td>
</tr>
