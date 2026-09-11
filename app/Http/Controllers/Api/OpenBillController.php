<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\ItemOpenBill;
use App\Models\OpenBill;
use App\Models\Product;
use App\Models\VariantProduct;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OpenBillController extends Controller
{
    /**
     * Ambil daftar bill yang masih terbuka untuk outlet user.
     *
     * - Outlet diambil otomatis dari token user yang login
     * - Hanya bill yang belum dibayar (deleted_at null) dan tidak dihapus permanen
     * - Mendukung pencarian berdasarkan nama bill via query param ?q=
     * - Setiap bill menyertakan jumlah item dan total (sum result_total)
     * - Diurutkan dari yang terbaru
     *
     * GET /api/v1/open-bills?q=
     */
    public function index(Request $request): JsonResponse
    {
        $outletIds = $request->user()->outletIds();

        if (empty($outletIds)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User tidak memiliki outlet yang terdaftar.',
            ], 422);
        }

        $outletId = $outletIds[0];
        $search   = trim((string) $request->query('q', ''));

        $openBills = OpenBill::with(['customer', 'user'])
            ->where('outlet_id', $outletId)
            ->whereNull('deleted_at')
            ->whereNull('delete_permanen')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->withCount('item')
            ->withSum('item', 'result_total')
            ->latest('created_at')
            ->get()
            ->map(fn ($bill) => [
                'id'               => $bill->id,
                'name'             => $bill->name,
                'queue_order'      => $bill->queue_order,
                'customer_id'      => $bill->customer?->id,
                'customer_name'    => $bill->customer?->name,
                'user'             => [
                    'id'   => $bill->user?->id,
                    'name' => $bill->user?->name,
                ],
                'item_count'       => $bill->item_count,
                'total'            => (int) $bill->item_sum_result_total,
                'created_at'       => $bill->created_at,
                'created_at_human' => Carbon::parse($bill->created_at)->diffForHumans(),
            ]);

        return response()->json([
            'status' => 'success',
            'data'   => $openBills,
        ]);
    }

    /**
     * Simpan Open Bill baru.
     *
     * Mendukung format JSON terstruktur (items array) maupun format form-data legacy (array paralel).
     *
     * POST /api/v1/open-bills
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $outletIds = $user->outletIds();

        if (empty($outletIds)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User tidak memiliki outlet yang terdaftar.',
            ], 422);
        }

        $outletId = $outletIds[0];

        $validated = $request->validate([
            'name'                   => ['required', 'string', 'max:255'],
            'customer_id'            => ['nullable', 'integer'],

            // Format JSON terstruktur
            'items'                  => ['nullable', 'array', 'min:1'],
            'items.*.product_id'     => ['nullable'],
            'items.*.variant_id'     => ['nullable'],
            'items.*.nama_product'   => ['nullable', 'string'],
            'items.*.nama_variant'   => ['nullable', 'string'],
            'items.*.harga'          => ['nullable', 'numeric'],
            'items.*.quantity'       => ['nullable', 'numeric', 'min:1'],
            'items.*.result_total'   => ['nullable', 'numeric'],
            'items.*.catatan'        => ['nullable', 'string'],
            'items.*.sales_type'     => ['nullable', 'string'],
            'items.*.tmp_id'         => ['nullable', 'string'],
            'items.*.exclude_tax'    => ['nullable'],
            'items.*.diskon'         => ['nullable'],
            'items.*.modifier'       => ['nullable'],
            'items.*.pilihan'        => ['nullable'],
            'items.*.promo'          => ['nullable'],

            // Format Form-Data Legacy (seperti web.php endpoint /kasir/open-bill)
            'idProduct'              => ['nullable', 'array'],
            'idVariant'              => ['nullable', 'array'],
            'namaProduct'            => ['nullable', 'array'],
            'namaVariant'            => ['nullable', 'array'],
            'harga'                  => ['nullable', 'array'],
            'quantity'               => ['nullable', 'array'],
            'resultTotal'            => ['nullable', 'array'],
            'catatan'                => ['nullable', 'array'],
            'salesType'              => ['nullable', 'array'],
            'tmpId'                  => ['nullable', 'array'],
            'exclude_tax'            => ['nullable'],
            'diskon'                 => ['nullable', 'array'],
            'modifier'               => ['nullable', 'array'],
            'pilihan'                => ['nullable', 'array'],
            'promo'                  => ['nullable', 'array'],
        ]);

        $customerId = $validated['customer_id'] ?? null;
        if ($customerId) {
            $customerExists = Customer::where('id', $customerId)->exists();
            if (!$customerExists) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Customer tidak ditemukan.',
                ], 422);
            }
        }

        try {
            $openBill = DB::transaction(function () use ($validated, $user, $outletId, $customerId) {
                $openBill = OpenBill::create([
                    'name'        => $validated['name'],
                    'user_id'     => $user->id,
                    'outlet_id'   => $outletId,
                    'queue_order' => 1,
                    'customer_id' => $customerId,
                ]);

                $dataItemOpenBill = [];

                // 1. Jika dikirim dalam format items (JSON terstruktur)
                if (!empty($validated['items']) && is_array($validated['items'])) {
                    foreach ($validated['items'] as $item) {
                        $qty = isset($item['quantity']) ? (int) $item['quantity'] : 1;
                        $harga = isset($item['harga']) ? (int) $item['harga'] : 0;
                        $resultTotal = isset($item['result_total']) ? (int) $item['result_total'] : ($harga * $qty);

                        // Ambil nama produk / variant jika tidak dikirim
                        $productId = $item['product_id'] ?? null;
                        $variantId = $item['variant_id'] ?? null;
                        $namaProduct = $item['nama_product'] ?? '';
                        $namaVariant = $item['nama_variant'] ?? '';

                        if ((empty($namaProduct) || empty($namaVariant)) && ($variantId || $productId)) {
                            if ($variantId) {
                                $variant = VariantProduct::with('product')->find($variantId);
                                if ($variant) {
                                    $namaVariant = $namaVariant ?: $variant->nama_variant;
                                    $namaProduct = $namaProduct ?: ($variant->product?->nama_product ?? '');
                                    $productId = $productId ?: $variant->product_id;
                                }
                            } elseif ($productId) {
                                $product = Product::find($productId);
                                if ($product) {
                                    $namaProduct = $namaProduct ?: $product->nama_product;
                                }
                            }
                        }

                        $excludeTax = filter_var($item['exclude_tax'] ?? false, FILTER_VALIDATE_BOOLEAN);

                        $diskon = is_string($item['diskon'] ?? null) ? json_decode($item['diskon'], true) : ($item['diskon'] ?? []);
                        $modifier = is_string($item['modifier'] ?? null) ? json_decode($item['modifier'], true) : ($item['modifier'] ?? []);
                        $pilihan = is_string($item['pilihan'] ?? null) ? json_decode($item['pilihan'], true) : ($item['pilihan'] ?? []);
                        $promo = is_string($item['promo'] ?? null) ? json_decode($item['promo'], true) : ($item['promo'] ?? []);

                        $dataItemOpenBill[] = [
                            'open_bill_id' => $openBill->id,
                            'catatan'      => $item['catatan'] ?? null,
                            'diskon'       => json_encode($diskon ?: []),
                            'harga'        => $harga,
                            'product_id'   => (string) ($productId ?? ''),
                            'variant_id'   => (string) ($variantId ?? ''),
                            'modifier'     => json_encode($modifier ?: []),
                            'nama_product' => (string) $namaProduct,
                            'nama_variant' => (string) $namaVariant,
                            'pilihan'      => json_encode($pilihan ?: []),
                            'promo'        => json_encode($promo ?: []),
                            'quantity'     => (string) $qty,
                            'result_total' => $resultTotal,
                            'sales_type'   => $item['sales_type'] ?? null,
                            'tmp_id'       => (string) ($item['tmp_id'] ?? ('tmp_' . uniqid())),
                            'queue_order'  => 1,
                            'exclude_tax'  => $excludeTax,
                            'created_at'   => Carbon::now(),
                            'updated_at'   => Carbon::now(),
                        ];
                    }
                } elseif (!empty($validated['tmpId']) && is_array($validated['tmpId'])) {
                    // 2. Format Legacy (paralel arrays)
                    $totalItems = count($validated['tmpId']);
                    $excludeTaxes = is_array($validated['exclude_tax'] ?? null) ? $validated['exclude_tax'] : [];

                    for ($x = 0; $x < $totalItems; $x++) {
                        $checkExcludeTax = filter_var($excludeTaxes[$x] ?? false, FILTER_VALIDATE_BOOLEAN);
                        $diskonVal = $validated['diskon'][$x] ?? [];
                        $modifierVal = $validated['modifier'][$x] ?? [];
                        $pilihanVal = $validated['pilihan'][$x] ?? [];
                        $promoVal = $validated['promo'][$x] ?? [];

                        $dataItemOpenBill[] = [
                            'open_bill_id' => $openBill->id,
                            'catatan'      => $validated['catatan'][$x] ?? null,
                            'diskon'       => is_string($diskonVal) ? $diskonVal : json_encode($diskonVal ?: []),
                            'harga'        => (int) ($validated['harga'][$x] ?? 0),
                            'product_id'   => (string) ($validated['idProduct'][$x] ?? ''),
                            'variant_id'   => (string) ($validated['idVariant'][$x] ?? ''),
                            'modifier'     => is_string($modifierVal) ? $modifierVal : json_encode($modifierVal ?: []),
                            'nama_product' => (string) ($validated['namaProduct'][$x] ?? ''),
                            'nama_variant' => (string) ($validated['namaVariant'][$x] ?? ''),
                            'pilihan'      => is_string($pilihanVal) ? $pilihanVal : json_encode($pilihanVal ?: []),
                            'promo'        => is_string($promoVal) ? $promoVal : json_encode($promoVal ?: []),
                            'quantity'     => (string) ($validated['quantity'][$x] ?? 1),
                            'result_total' => (int) ($validated['resultTotal'][$x] ?? 0),
                            'sales_type'   => $validated['salesType'][$x] ?? null,
                            'tmp_id'       => (string) ($validated['tmpId'][$x] ?? ('tmp_' . uniqid())),
                            'queue_order'  => 1,
                            'exclude_tax'  => $checkExcludeTax,
                            'created_at'   => Carbon::now(),
                            'updated_at'   => Carbon::now(),
                        ];
                    }
                }

                if (!empty($dataItemOpenBill)) {
                    ItemOpenBill::insert($dataItemOpenBill);
                }

                return $openBill;
            });

            // Muat relasi lengkap untuk response
            $openBill->load(['customer', 'user', 'item']);

            $items = $openBill->item->map(fn ($item) => [
                'id'           => $item->id,
                'open_bill_id' => $item->open_bill_id,
                'tmp_id'       => $item->tmp_id,
                'product_id'   => $item->product_id,
                'variant_id'   => $item->variant_id,
                'nama_product' => $item->nama_product,
                'nama_variant' => $item->nama_variant,
                'harga'        => (int) $item->harga,
                'quantity'     => (int) $item->quantity,
                'qty_terbayar' => (int) $item->qty_terbayar,
                'result_total' => (int) $item->result_total,
                'catatan'      => $item->catatan,
                'exclude_tax'  => (bool) $item->exclude_tax,
                'sales_type'   => $item->sales_type === 'null' || $item->sales_type === null ? null : $item->sales_type,
                'diskon'       => json_decode($item->diskon, true),
                'modifier'     => json_decode($item->modifier, true),
                'pilihan'      => json_decode($item->pilihan, true),
                'promo'        => json_decode($item->promo, true),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Open bill berhasil disimpan.',
                'data'    => [
                    'id'               => $openBill->id,
                    'name'             => $openBill->name,
                    'queue_order'      => $openBill->queue_order,
                    'customer'         => $openBill->customer
                        ? [
                            'id'   => $openBill->customer->id,
                            'name' => $openBill->customer->name,
                        ]
                        : null,
                    'user'             => [
                        'id'   => $openBill->user?->id,
                        'name' => $openBill->user?->name,
                    ],
                    'total'            => (int) $openBill->item->sum('result_total'),
                    'created_at'       => $openBill->created_at,
                    'created_at_human' => Carbon::parse($openBill->created_at)->diffForHumans(),
                    'items'            => $items,
                ],
            ], 201);
        } catch (\Throwable $e) {
            Log::error('OpenBillController@store Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menyimpan open bill: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ambil detail satu open bill beserta item-nya untuk outlet user.
     *
     * - Bill harus still open (belum dibayar) dan milik outlet user
     * - Field JSON (diskon, modifier, pilihan, promo) dikembalikan sebagai array
     * - Format item menyesuaikan struktur yang dikirim Android saat bayar
     *
     * GET /api/v1/open-bills/{id}
     */
    public function show(Request $request, $id): JsonResponse
    {
        $outletIds = $request->user()->outletIds();

        if (empty($outletIds)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User tidak memiliki outlet yang terdaftar.',
            ], 422);
        }

        $outletId = $outletIds[0];

        $openBill = OpenBill::with(['customer', 'user', 'item'])
            ->where('id', $id)
            ->where('outlet_id', $outletId)
            ->whereNull('deleted_at')
            ->whereNull('delete_permanen')
            ->first();

        if (!$openBill) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Open bill tidak ditemukan.',
            ], 404);
        }

        $items = $openBill->item->map(fn ($item) => [
            'id'           => $item->id,
            'open_bill_id' => $item->open_bill_id,
            'tmp_id'       => $item->tmp_id,
            'product_id'   => $item->product_id,
            'variant_id'   => $item->variant_id,
            'nama_product' => $item->nama_product,
            'nama_variant' => $item->nama_variant,
            'harga'        => (int) $item->harga,
            'quantity'     => (int) $item->quantity,
            'qty_terbayar' => (int) $item->qty_terbayar,
            'result_total' => (int) $item->result_total,
            'catatan'      => $item->catatan,
            'exclude_tax'  => (bool) $item->exclude_tax,
            'sales_type'   => $item->sales_type === 'null' || $item->sales_type === null ? null : $item->sales_type,
            'diskon'       => json_decode($item->diskon, true),
            'modifier'     => json_decode($item->modifier, true),
            'pilihan'      => json_decode($item->pilihan, true),
            'promo'        => json_decode($item->promo, true),
        ]);

            return response()->json([
                'status' => 'success',
                'data'   => [
                    'id'               => $openBill->id,
                    'name'             => $openBill->name,
                    'queue_order'      => $openBill->queue_order,
                    'customer'         => $openBill->customer
                        ? [
                            'id'   => $openBill->customer->id,
                            'name' => $openBill->customer->name,
                        ]
                        : null,
                    'user'             => [
                        'id'   => $openBill->user?->id,
                        'name' => $openBill->user?->name,
                    ],
                    'total'            => (int) $openBill->item->sum('result_total'),
                    'created_at'       => $openBill->created_at,
                    'created_at_human' => Carbon::parse($openBill->created_at)->diffForHumans(),
                    'items'            => $items,
                ],
            ]);
        }

    /**
     * Update open bill yang sudah ada (menambahkan item baru & increment queue_order).
     *
     * PUT /api/v1/open-bills/{id}
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $outletIds = $user->outletIds();

        if (empty($outletIds)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User tidak memiliki outlet yang terdaftar.',
            ], 422);
        }

        $outletId = $outletIds[0];

        $openBill = OpenBill::where('id', $id)
            ->where('outlet_id', $outletId)
            ->whereNull('deleted_at')
            ->whereNull('delete_permanen')
            ->first();

        if (!$openBill) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Open bill tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'name'                   => ['nullable', 'string', 'max:255'],
            'customer_id'            => ['nullable', 'integer'],

            // Format JSON terstruktur
            'items'                  => ['nullable', 'array', 'min:1'],
            'items.*.product_id'     => ['nullable'],
            'items.*.variant_id'     => ['nullable'],
            'items.*.nama_product'   => ['nullable', 'string'],
            'items.*.nama_variant'   => ['nullable', 'string'],
            'items.*.harga'          => ['nullable', 'numeric'],
            'items.*.quantity'       => ['nullable', 'numeric', 'min:1'],
            'items.*.result_total'   => ['nullable', 'numeric'],
            'items.*.catatan'        => ['nullable', 'string'],
            'items.*.sales_type'     => ['nullable', 'string'],
            'items.*.tmp_id'         => ['nullable', 'string'],
            'items.*.exclude_tax'    => ['nullable'],
            'items.*.diskon'         => ['nullable'],
            'items.*.modifier'       => ['nullable'],
            'items.*.pilihan'        => ['nullable'],
            'items.*.promo'          => ['nullable'],

            // Format Legacy
            'idProduct'              => ['nullable', 'array'],
            'idVariant'              => ['nullable', 'array'],
            'namaProduct'            => ['nullable', 'array'],
            'namaVariant'            => ['nullable', 'array'],
            'harga'                  => ['nullable', 'array'],
            'quantity'               => ['nullable', 'array'],
            'resultTotal'            => ['nullable', 'array'],
            'catatan'                => ['nullable', 'array'],
            'salesType'              => ['nullable', 'array'],
            'tmpId'                  => ['nullable', 'array'],
            'exclude_tax'            => ['nullable'],
            'diskon'                 => ['nullable', 'array'],
            'modifier'               => ['nullable', 'array'],
            'pilihan'                => ['nullable', 'array'],
            'promo'                  => ['nullable', 'array'],
        ]);

        if (!empty($validated['customer_id'])) {
            $customerExists = Customer::where('id', $validated['customer_id'])->exists();
            if (!$customerExists) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Customer tidak ditemukan.',
                ], 422);
            }
            $openBill->customer_id = $validated['customer_id'];
        }

        if (!empty($validated['name'])) {
            $openBill->name = $validated['name'];
        }

        try {
            DB::transaction(function () use ($validated, $openBill) {
                $openBill->queue_order += 1;
                $openBill->save();

                $newQueueOrder = $openBill->queue_order;
                $dataItemOpenBill = [];

                if (!empty($validated['items']) && is_array($validated['items'])) {
                    foreach ($validated['items'] as $item) {
                        $qty = isset($item['quantity']) ? (int) $item['quantity'] : 1;
                        $harga = isset($item['harga']) ? (int) $item['harga'] : 0;
                        $resultTotal = isset($item['result_total']) ? (int) $item['result_total'] : ($harga * $qty);

                        $productId = $item['product_id'] ?? null;
                        $variantId = $item['variant_id'] ?? null;
                        $namaProduct = $item['nama_product'] ?? '';
                        $namaVariant = $item['nama_variant'] ?? '';

                        if ((empty($namaProduct) || empty($namaVariant)) && ($variantId || $productId)) {
                            if ($variantId) {
                                $variant = VariantProduct::with('product')->find($variantId);
                                if ($variant) {
                                    $namaVariant = $namaVariant ?: $variant->nama_variant;
                                    $namaProduct = $namaProduct ?: ($variant->product?->nama_product ?? '');
                                    $productId = $productId ?: $variant->product_id;
                                }
                            } elseif ($productId) {
                                $product = Product::find($productId);
                                if ($product) {
                                    $namaProduct = $namaProduct ?: $product->nama_product;
                                }
                            }
                        }

                        $excludeTax = filter_var($item['exclude_tax'] ?? false, FILTER_VALIDATE_BOOLEAN);

                        $diskon = is_string($item['diskon'] ?? null) ? json_decode($item['diskon'], true) : ($item['diskon'] ?? []);
                        $modifier = is_string($item['modifier'] ?? null) ? json_decode($item['modifier'], true) : ($item['modifier'] ?? []);
                        $pilihan = is_string($item['pilihan'] ?? null) ? json_decode($item['pilihan'], true) : ($item['pilihan'] ?? []);
                        $promo = is_string($item['promo'] ?? null) ? json_decode($item['promo'], true) : ($item['promo'] ?? []);

                        $dataItemOpenBill[] = [
                            'open_bill_id' => $openBill->id,
                            'catatan'      => $item['catatan'] ?? null,
                            'diskon'       => json_encode($diskon ?: []),
                            'harga'        => $harga,
                            'product_id'   => (string) ($productId ?? ''),
                            'variant_id'   => (string) ($variantId ?? ''),
                            'modifier'     => json_encode($modifier ?: []),
                            'nama_product' => (string) $namaProduct,
                            'nama_variant' => (string) $namaVariant,
                            'pilihan'      => json_encode($pilihan ?: []),
                            'promo'        => json_encode($promo ?: []),
                            'quantity'     => (string) $qty,
                            'result_total' => $resultTotal,
                            'sales_type'   => $item['sales_type'] ?? null,
                            'tmp_id'       => (string) ($item['tmp_id'] ?? ('tmp_' . uniqid())),
                            'queue_order'  => $newQueueOrder,
                            'exclude_tax'  => $excludeTax,
                            'created_at'   => Carbon::now(),
                            'updated_at'   => Carbon::now(),
                        ];
                    }
                } elseif (!empty($validated['tmpId']) && is_array($validated['tmpId'])) {
                    $totalItems = count($validated['tmpId']);
                    $excludeTaxes = is_array($validated['exclude_tax'] ?? null) ? $validated['exclude_tax'] : [];

                    for ($x = 0; $x < $totalItems; $x++) {
                        $checkExcludeTax = filter_var($excludeTaxes[$x] ?? false, FILTER_VALIDATE_BOOLEAN);
                        $diskonVal = $validated['diskon'][$x] ?? [];
                        $modifierVal = $validated['modifier'][$x] ?? [];
                        $pilihanVal = $validated['pilihan'][$x] ?? [];
                        $promoVal = $validated['promo'][$x] ?? [];

                        $dataItemOpenBill[] = [
                            'open_bill_id' => $openBill->id,
                            'catatan'      => $validated['catatan'][$x] ?? null,
                            'diskon'       => is_string($diskonVal) ? $diskonVal : json_encode($diskonVal ?: []),
                            'harga'        => (int) ($validated['harga'][$x] ?? 0),
                            'product_id'   => (string) ($validated['idProduct'][$x] ?? ''),
                            'variant_id'   => (string) ($validated['idVariant'][$x] ?? ''),
                            'modifier'     => is_string($modifierVal) ? $modifierVal : json_encode($modifierVal ?: []),
                            'nama_product' => (string) ($validated['namaProduct'][$x] ?? ''),
                            'nama_variant' => (string) ($validated['namaVariant'][$x] ?? ''),
                            'pilihan'      => is_string($pilihanVal) ? $pilihanVal : json_encode($pilihanVal ?: []),
                            'promo'        => is_string($promoVal) ? $promoVal : json_encode($promoVal ?: []),
                            'quantity'     => (string) ($validated['quantity'][$x] ?? 1),
                            'result_total' => (int) ($validated['resultTotal'][$x] ?? 0),
                            'sales_type'   => $validated['salesType'][$x] ?? null,
                            'tmp_id'       => (string) ($validated['tmpId'][$x] ?? ('tmp_' . uniqid())),
                            'queue_order'  => $newQueueOrder,
                            'exclude_tax'  => $checkExcludeTax,
                            'created_at'   => Carbon::now(),
                            'updated_at'   => Carbon::now(),
                        ];
                    }
                }

                if (!empty($dataItemOpenBill)) {
                    ItemOpenBill::insert($dataItemOpenBill);
                }
            });

            $openBill->load(['customer', 'user', 'item']);

            $items = $openBill->item->map(fn ($item) => [
                'id'           => $item->id,
                'open_bill_id' => $item->open_bill_id,
                'tmp_id'       => $item->tmp_id,
                'product_id'   => $item->product_id,
                'variant_id'   => $item->variant_id,
                'nama_product' => $item->nama_product,
                'nama_variant' => $item->nama_variant,
                'harga'        => (int) $item->harga,
                'quantity'     => (int) $item->quantity,
                'qty_terbayar' => (int) $item->qty_terbayar,
                'result_total' => (int) $item->result_total,
                'catatan'      => $item->catatan,
                'exclude_tax'  => (bool) $item->exclude_tax,
                'sales_type'   => $item->sales_type === 'null' || $item->sales_type === null ? null : $item->sales_type,
                'diskon'       => json_decode($item->diskon, true),
                'modifier'     => json_decode($item->modifier, true),
                'pilihan'      => json_decode($item->pilihan, true),
                'promo'        => json_decode($item->promo, true),
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => 'Open bill berhasil diperbarui.',
                'data'    => [
                    'id'               => $openBill->id,
                    'name'             => $openBill->name,
                    'queue_order'      => $openBill->queue_order,
                    'customer'         => $openBill->customer
                        ? [
                            'id'   => $openBill->customer->id,
                            'name' => $openBill->customer->name,
                        ]
                        : null,
                    'user'             => [
                        'id'   => $openBill->user?->id,
                        'name' => $openBill->user?->name,
                    ],
                    'total'            => (int) $openBill->item->sum('result_total'),
                    'created_at'       => $openBill->created_at,
                    'created_at_human' => Carbon::parse($openBill->created_at)->diffForHumans(),
                    'items'            => $items,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('OpenBillController@update Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memperbarui open bill: ' . $e->getMessage(),
            ], 500);
        }
    }
}
