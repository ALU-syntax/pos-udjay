@php
    /** @var \App\Models\Product $product */
    /** @var \Illuminate\Support\Collection $modifierGroups */
@endphp

<div class="modal-dialog modal-xl modal-dialog-centered" role="document">
  <div class="modal-content">
    <div class="modal-header border-0 pb-0">
      <div>
        <h5 class="modal-title mb-1">
          <span class="fw-semibold">Detail Modifier Item Sales </span>
        </h5>
        <div class="text-muted small"><strong>{{ $startDate ?? '-' }}</strong> s/d <strong>{{ $endDate ?? '-' }}</strong></div>
        <div class="text-muted small">Produk: <strong>{{ $nameProductVariant }}</strong></div>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    <div class="modal-body">
      @if(($modifierGroups ?? collect())->isEmpty())
        <div class="alert alert-info d-flex align-items-center" role="alert">
          <i class="bi bi-info-circle me-2"></i>
          Tidak ada modifier group untuk produk ini.
        </div>
      @else
        <!-- Tabs: Groups -->
        <ul class="nav nav-tabs flex-nowrap overflow-auto mb-3" role="tablist">
          @foreach($modifierGroups as $gIndex => $group)
            @php $tabId = 'group-'.$group->id; @endphp
            <li class="nav-item" role="presentation">
              <button class="nav-link @if($loop->first) active @endif" id="{{ $tabId }}-tab" data-bs-toggle="tab" data-bs-target="#{{ $tabId }}" type="button" role="tab" aria-controls="{{ $tabId }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                {{ $group->name }}
              </button>
            </li>
          @endforeach
        </ul>

        <div class="tab-content">
          @foreach($modifierGroups as $group)
            @php $tabId = 'group-'.$group->id; @endphp
            <div class="tab-pane fade @if($loop->first) show active @endif" id="{{ $tabId }}" role="tabpanel" aria-labelledby="{{ $tabId }}-tab">

              @if(($group->modifier ?? collect())->isEmpty())
                <div class="alert alert-light border">Belum ada modifier pada grup ini.</div>
              @else
                <!-- Modifier cards grid -->
                <div class="row g-3">
                  @foreach($group->modifier as $mod)
                    @php
                      $tx = collect($mod->transaction_items ?? []);
                      $totalSold = (int) $tx->sum('total_count');
                    @endphp
                    <div class="col-12 col-md-6 col-lg-4">
                      <div class="card h-100 shadow-sm border-0 rounded-3">
                        <div class="card-body">
                          <div class="d-flex justify-content-between align-items-start mb-1">
                            <h6 class="card-title mb-0">{{ $mod->name }}</h6>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Terjual: {{ number_format($totalSold) }}</span>
                          </div>
                          <div class="text-muted small mb-2">Harga tambah: <strong>{{ number_format($mod->harga ?? 0) }}</strong> &middot; Stok: <strong>{{ $mod->stok ?? '-' }}</strong></div>

                          @if($tx->isEmpty())
                            <div class="text-muted small fst-italic">Belum ada transaksi untuk modifier ini.</div>
                          @else
                            <!-- Collapsible detail table per modifier -->
                            @php $collapseId = 'tx-'.$group->id.'-'.$mod->id; @endphp
                            <div class="mt-2">
                              <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="false" aria-controls="{{ $collapseId }}">
                                Lihat rincian transaksi
                              </button>
                              <div class="collapse mt-2" id="{{ $collapseId }}">
                                <div class="table-responsive">
                                  <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                      <tr>
                                        <th class="text-nowrap">Waktu</th>
                                        <th class="text-nowrap">Transaksi Item Id</th>
                                        <th class="text-nowrap text-end">Qty</th>
                                        {{-- <th class="text-nowrap">Catatan</th> --}}
                                      </tr>
                                    </thead>
                                    <tbody>
                                      @foreach($tx->sortByDesc('created_at') as $row)
                                        <tr>
                                          <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($row['created_at'])->timezone(config('app.timezone'))->format('d M Y, H:i') }}</td>
                                          <td>#{{ $row['transaction_id'] }}</td>
                                          <td class="text-end">{{ $row['total_count'] }}</td>
                                          {{-- <td class="text-truncate" style="max-width: 220px;" title="{{ $row['catatan'] }}">{{ $row['catatan'] ?: '-' }}</td> --}}
                                        </tr>
                                      @endforeach
                                    </tbody>
                                  </table>
                                </div>
                              </div>
                            </div>
                          @endif
                        </div>
                      </div>
                    </div>
                  @endforeach
                </div>
              @endif
            </div>
          @endforeach
        </div>
      @endif
    </div>

    <div class="modal-footer border-0 pt-0">
      <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
    </div>
  </div>
</div>

<!-- Optional: lightweight styling to elevate the look -->
<style>
  .bg-primary-subtle{ background: rgba(13,110,253,.08); }
  .border-primary-subtle{ border-color: rgba(13,110,253,.25) !important; }
</style>
