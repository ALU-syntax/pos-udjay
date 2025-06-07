<div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
        <div class="modal-header border-0">
            <h5 class="modal-title">
                <span class="fw-mediumbold" id="category-title">{{ $categoryName }}</span>

            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            @if ($products->count() == 0)
                <div class="alert alert-info">
                    Tidak ada produk yang terjual pada kategori ini.
                </div>
            @else
                <div class="row p-2">
                    <div class="col-5"><strong>Nama Produk</strong></div>
                    <div class="col-5"><strong>Quantity</strong></div>
                </div>

                <div class="row">
                    <div class="accordion accordion-secondary ">
                        @foreach ($products as $product)
                            @foreach ($product->variants as $variant)
                                <div class="card m-2">
                                    <div class="card-header collapsed" id="heading{{ $variant->id }}"
                                        data-bs-toggle="collapse" data-bs-target="#collapse{{ $variant->id }}"
                                        aria-expanded="false" aria-controls="collapse{{ $variant->id }}"
                                        role="button">

                                        <div class="row w-100 m-0">
                                            <div class="col-5">
                                                <div class="span-title">
                                                    {{ $variant->name == $product->name ? $product->name . ' (' . $product->outlet->name . ')' : $product->name . ' - ' . $variant->name . ' (' . $product->outlet->name . ')' }}
                                                </div>
                                            </div>
                                            <div class="col-5 ">
                                                <div class="span-title">
                                                    {{ $variant->itemTransaction->sum('total_count') }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="span-mode"></div>
                                    </div>

                                    <div id="collapse{{ $variant->id }}" class="collapse"
                                        aria-labelledby="heading{{ $variant->id }}" data-parent="#accordion"
                                        style="">
                                        <div class="card-body">
                                            <table class="table table-sm table-bordered">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th scope="col">Tanggal</th>
                                                        <th scope="col">Jumlah</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($variant->itemTransaction as $transaction)
                                                        <tr>
                                                            <td>{{ $transaction->created_at }}</td>
                                                            <td>{{ $transaction->total_count }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
        <div class="modal-footer border-0">
            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
</div>
