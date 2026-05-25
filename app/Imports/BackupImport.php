<?php

namespace App\Imports;

use App\Models\Category;
use App\Models\Outlets;
use App\Models\PettyCash;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\VariantProduct;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use SplFileObject;

class BackupImport
{
    protected string $tz = 'Asia/Jakarta';

    /** @var array<string, Outlets> */
    protected array $outletCache = [];

    /** @var array<string, PettyCash> */
    protected array $pettyCashCache = [];

    /** @var array<string, Product> */
    protected array $productCache = [];

    /** @var array<string, VariantProduct> */
    protected array $variantCache = [];

    /** @var array<string, int> */
    protected array $priceCache = [];

    protected ?Category $historyCategory = null;

    public function __construct(
        protected ?string $progressId = null,
        protected ?int $userId = null,
        protected ?int $fallbackOutletId = null,
    ) {
        DB::disableQueryLog();
    }

    public static function progressCacheKey(?int $userId, string $progressId): string
    {
        return 'backup-import:' . ($userId ?: 'guest') . ':' . $progressId;
    }

    public static function defaultProgress(): array
    {
        return [
            'status' => 'waiting',
            'message' => 'Menunggu file import.',
            'total' => 0,
            'processed' => 0,
            'percent' => 0,
            'summary' => [],
            'error' => null,
        ];
    }

    public function import(UploadedFile $file): array
    {
        return $this->importPath($file->getRealPath());
    }

    public function importPath(string $path): array
    {
        $rows = $this->readRows($path);
        $total = count($rows);

        $summary = [
            'rows_total' => $total,
            'rows_processed' => 0,
            'transactions_created' => 0,
            'transactions_updated' => 0,
            'items_created' => 0,
            'products_created' => 0,
            'variants_created' => 0,
            'skipped_rows' => 0,
        ];

        $this->putProgress('processing', 'Menganalisis harga produk history...', 0, $total, $summary);
        $this->primePriceCache($rows);

        $this->historyCategory = $this->historyCategory();

        foreach ($rows as $row) {
            DB::transaction(function () use ($row, &$summary) {
                $this->importRow($row, $summary);
            });

            $summary['rows_processed']++;
            $this->putProgress(
                'processing',
                'Mengimport transaksi history...',
                $summary['rows_processed'],
                $total,
                $summary
            );
        }

        $this->putProgress('finished', 'Import selesai.', $total, $total, $summary);

        return $summary;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function readRows(string $path): array
    {
        $delimiter = $this->detectDelimiter($path);
        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);
        $file->setCsvControl($delimiter);

        $headers = null;
        $rows = [];

        foreach ($file as $line) {
            if ($line === false || $line === [null]) {
                continue;
            }

            if ($headers === null) {
                $headers = array_map(fn ($header) => $this->normalizeHeader((string) $header), $line);
                $this->validateHeaders($headers);
                continue;
            }

            $normalized = $this->combineRow($headers, $line);
            if ($this->rowIsEmpty($normalized)) {
                continue;
            }

            $rows[] = $normalized;
        }

        return $rows;
    }

    protected function detectDelimiter(string $path): string
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            throw new \RuntimeException('File import tidak bisa dibaca.');
        }

        $firstLine = (string) fgets($handle);
        fclose($handle);

        return substr_count($firstLine, ';') >= substr_count($firstLine, ',') ? ';' : ',';
    }

    /**
     * @param array<int, string> $headers
     */
    protected function validateHeaders(array $headers): void
    {
        $required = ['outlet', 'date', 'time', 'receipt_number', 'items'];
        $missing = array_diff($required, $headers);

        if ($missing) {
            throw new \InvalidArgumentException('Header CSV tidak sesuai. Kolom wajib hilang: ' . implode(', ', $missing));
        }
    }

    /**
     * @param array<int, string> $headers
     * @param array<int, mixed> $line
     * @return array<string, mixed>
     */
    protected function combineRow(array $headers, array $line): array
    {
        $row = [];

        foreach ($headers as $index => $header) {
            if ($header === '') {
                continue;
            }

            $row[$header] = $this->cleanCell($line[$index] ?? null);
        }

        return $row;
    }

    /**
     * @param array<string, mixed> $row
     */
    protected function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    protected function primePriceCache(array $rows): void
    {
        foreach ($rows as $row) {
            $gross = $this->money($row['gross_sales'] ?? 0);
            if ($gross <= 0) {
                continue;
            }

            $items = $this->parseItems((string) ($row['items'] ?? ''));
            if (!$items) {
                continue;
            }

            $qty = array_sum(array_column($items, 'quantity'));
            if ($qty <= 0) {
                continue;
            }

            if (count($items) === 1) {
                $this->priceCache[$items[0]['display_name']] = (int) round($gross / $items[0]['quantity']);
            }
        }
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, int> $summary
     */
    protected function importRow(array $row, array &$summary): void
    {
        $outletName = trim((string) ($row['outlet'] ?? ''));
        $receiptNumber = trim((string) ($row['receipt_number'] ?? ''));
        $itemsText = trim((string) ($row['items'] ?? ''));

        if ($outletName === '' || $receiptNumber === '' || $itemsText === '') {
            $summary['skipped_rows']++;
            return;
        }

        $outlet = $this->outlet($outletName);
        $dateTime = $this->dateTime($row['date'] ?? null, $row['time'] ?? null);
        $items = $this->parseItems($itemsText);

        if (!$items) {
            $summary['skipped_rows']++;
            return;
        }

        $gross = $this->money($row['gross_sales'] ?? 0);
        $discount = $this->money($row['discounts'] ?? 0);
        $tax = $this->money($row['tax'] ?? 0);
        $totalCollected = $this->money($row['total_collected'] ?? $row['total_amount'] ?? 0);
        $totalAmount = $this->money($row['total_amount'] ?? $row['total_collected'] ?? 0);
        $eventType = trim((string) ($row['event_type'] ?? 'Payment'));
        $isRefund = Str::lower($eventType) === 'refund';

        $transaction = Transaction::where('outlet_id', $outlet->id)
            ->where('receipt_number', $receiptNumber)
            ->where('created_at', $dateTime->toDateTimeString())
            ->first();

        $payload = [
            'outlet_id' => $outlet->id,
            'user_id' => $this->userId ?: auth()->id(),
            'customer_id' => null,
            'total' => (int) round($totalAmount),
            'nominal_bayar' => (int) round($totalCollected),
            'change' => 0,
            'category_payment_id' => null,
            'tipe_pembayaran' => null,
            'nama_tipe_pembayaran' => $this->nullableString($row['payment_method'] ?? null) ?: 'MOKA',
            'total_pajak' => $tax > 0 ? json_encode([['id' => null, 'name' => 'MOKA Tax', 'total' => (int) round($tax)]]) : json_encode([]),
            'total_modifier' => (int) round($this->money($row['gratuity'] ?? 0)),
            'total_diskon' => max(0, (int) round($discount)),
            'diskon_all_item' => json_encode([]),
            'rounding_amount' => 0,
            'tanda_rounding' => null,
            'catatan' => $this->note($row, $eventType),
            'patty_cash_id' => $this->pettyCash($outlet, $dateTime)->id,
            'receipt_number' => $receiptNumber,
            'potongan_point' => 0,
            'created_at' => $dateTime,
            'updated_at' => now($this->tz),
        ];

        if ($transaction) {
            $transaction->update($payload);
            TransactionItem::withTrashed()->where('transaction_id', $transaction->id)->forceDelete();
            $summary['transactions_updated']++;
        } else {
            $transaction = Transaction::create($payload);
            $summary['transactions_created']++;
        }

        $unitPrices = $this->unitPrices($items, $gross);
        $position = 0;

        foreach ($items as $item) {
            [$product, $variant] = $this->productAndVariant($outlet, $item, $unitPrices[$position] ?? 0, $summary);

            for ($i = 0; $i < $item['quantity']; $i++) {
                TransactionItem::create([
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'discount_id' => json_encode([]),
                    'modifier_id' => json_encode([]),
                    'promo_id' => json_encode([]),
                    'sales_type_id' => null,
                    'transaction_id' => $transaction->id,
                    'catatan' => $isRefund ? 'MOKA Refund' : 'MOKA History',
                    'harga' => max(0, (int) ($unitPrices[$position] ?? 0)),
                    'reward_item' => false,
                    'created_at' => $dateTime,
                    'updated_at' => now($this->tz),
                ]);

                $summary['items_created']++;
                $position++;
            }
        }
    }

    protected function historyCategory(): Category
    {
        $category = Category::withTrashed()->where('name', 'History')->first();

        if ($category) {
            if (method_exists($category, 'trashed') && $category->trashed()) {
                $category->restore();
            }

            if (!$category->status) {
                $category->update(['status' => true]);
            }

            return $category;
        }

        return Category::create([
            'name' => 'History',
            'status' => true,
        ]);
    }

    protected function outlet(string $name): Outlets
    {
        $key = Str::lower(trim($name));

        if (isset($this->outletCache[$key])) {
            return $this->outletCache[$key];
        }

        $outlet = Outlets::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [$key])
            ->first();

        if (!$outlet) {
            $outlet = Outlets::all()->first(function (Outlets $outlet) use ($key) {
                return $this->normalizeOutletName($outlet->name) === $this->normalizeOutletName($key);
            });
        }

        if (!$outlet && $this->fallbackOutletId) {
            $outlet = Outlets::find($this->fallbackOutletId);
        }

        if (!$outlet) {
            throw new \RuntimeException("Outlet `{$name}` tidak ditemukan. Pastikan nama outlet di CSV sama dengan master outlet.");
        }

        return $this->outletCache[$key] = $outlet;
    }

    protected function normalizeOutletName(string $name): string
    {
        return preg_replace('/[^a-z0-9]/', '', Str::lower($name)) ?? '';
    }

    protected function pettyCash(Outlets $outlet, Carbon $dateTime): PettyCash
    {
        $key = $outlet->id . ':' . $dateTime->toDateString();

        if (isset($this->pettyCashCache[$key])) {
            return $this->pettyCashCache[$key];
        }

        $open = $dateTime->copy()->startOfDay();
        $close = $dateTime->copy()->endOfDay();

        $pettyCash = PettyCash::firstOrCreate(
            [
                'outlet_id' => (string) $outlet->id,
                'open' => $open,
            ],
            [
                'amount_awal' => '0',
                'amount_akhir' => '0',
                'user_id_started' => (string) ($this->userId ?: auth()->id()),
                'user_id_ended' => (string) ($this->userId ?: auth()->id()),
                'close' => $close,
            ]
        );

        if (!$pettyCash->close) {
            $pettyCash->update(['close' => $close]);
        }

        return $this->pettyCashCache[$key] = $pettyCash;
    }

    /**
     * @param array<string, mixed> $item
     * @param array<string, int> $summary
     * @return array{0: Product, 1: VariantProduct}
     */
    protected function productAndVariant(Outlets $outlet, array $item, int $price, array &$summary): array
    {
        $productKey = $outlet->id . ':' . $item['product_name'];

        if (!isset($this->productCache[$productKey])) {
            $product = Product::withTrashed()
                ->where('outlet_id', $outlet->id)
                ->where('category_id', $this->historyCategory?->id)
                ->where('name', $item['product_name'])
                ->first();

            if ($product) {
                if (method_exists($product, 'trashed') && $product->trashed()) {
                    $product->restore();
                }

                if (!$product->status || (float) $product->harga_modal !== 0.0) {
                    $product->update([
                        'status' => true,
                        'harga_modal' => 0,
                    ]);
                }
            } else {
                $product = Product::create([
                    'name' => $item['product_name'],
                    'category_id' => $this->historyCategory?->id,
                    'status' => true,
                    'harga_modal' => 0,
                    'outlet_id' => $outlet->id,
                    'description' => 'Produk otomatis dari import history MOKA.',
                    'exclude_tax' => true,
                ]);
                $summary['products_created']++;
            }

            $this->productCache[$productKey] = $product;
        }

        $product = $this->productCache[$productKey];
        $variantName = $item['variant_name'] ?: $item['product_name'];
        $variantKey = $product->id . ':' . $variantName;

        if (!isset($this->variantCache[$variantKey])) {
            $variant = VariantProduct::withTrashed()
                ->where('product_id', $product->id)
                ->where('name', $variantName)
                ->first();

            if ($variant) {
                if (method_exists($variant, 'trashed') && $variant->trashed()) {
                    $variant->restore();
                }

                if ((int) $variant->harga <= 0 && $price > 0) {
                    $variant->update(['harga' => $price]);
                }
            } else {
                $variant = VariantProduct::create([
                    'name' => $variantName,
                    'harga' => max(0, $price),
                    'stok' => 0,
                    'product_id' => $product->id,
                ]);
                $summary['variants_created']++;
            }

            $this->variantCache[$variantKey] = $variant;
        }

        return [$product, $this->variantCache[$variantKey]];
    }

    /**
     * @return array<int, array{display_name: string, product_name: string, variant_name: string, quantity: int}>
     */
    protected function parseItems(string $itemsText): array
    {
        $itemsText = trim($itemsText);
        if ($itemsText === '') {
            return [];
        }

        if (Str::startsWith(Str::lower($itemsText), 'full refund for:')) {
            $receipt = trim(Str::after($itemsText, ':'));

            return [[
                'display_name' => 'Refund ' . $receipt,
                'product_name' => 'Refund',
                'variant_name' => $receipt ?: 'MOKA',
                'quantity' => 1,
            ]];
        }

        $items = [];
        $parts = array_filter(array_map('trim', explode(',', $itemsText)));

        foreach ($parts as $part) {
            $quantity = 1;
            if (preg_match('/\s+x\s+(\d+)$/i', $part, $matches)) {
                $quantity = max(1, (int) $matches[1]);
                $part = trim(preg_replace('/\s+x\s+\d+$/i', '', $part) ?? $part);
            }

            [$productName, $variantName] = $this->splitProductVariant($part);

            $items[] = [
                'display_name' => $part,
                'product_name' => $productName,
                'variant_name' => $variantName,
                'quantity' => $quantity,
            ];
        }

        return $items;
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function splitProductVariant(string $name): array
    {
        $name = trim($name);

        if (preg_match('/^(.*?)\s*\(([^()]*)\)\s*$/', $name, $matches)) {
            $productName = trim($matches[1]);
            $variantName = trim($matches[2]);

            if ($productName !== '' && $variantName !== '') {
                return [$productName, $variantName];
            }
        }

        return [$name, $name];
    }

    /**
     * @param array<int, array{display_name: string, product_name: string, variant_name: string, quantity: int}> $items
     * @return array<int, int>
     */
    protected function unitPrices(array $items, float $gross): array
    {
        $gross = max(0, (int) round($gross));
        $units = [];
        $knownTotal = 0;
        $unknownIndexes = [];

        foreach ($items as $item) {
            for ($i = 0; $i < $item['quantity']; $i++) {
                $index = count($units);
                $price = $this->priceCache[$item['display_name']] ?? null;

                if ($price === null || $price < 0) {
                    $units[] = null;
                    $unknownIndexes[] = $index;
                } else {
                    $units[] = $price;
                    $knownTotal += $price;
                }
            }
        }

        if (!$units) {
            return [];
        }

        if ($knownTotal > $gross) {
            return $this->evenPrices(count($units), $gross);
        }

        if ($unknownIndexes) {
            $remaining = $gross - $knownTotal;
            $unknownPrices = $this->evenPrices(count($unknownIndexes), $remaining);

            foreach ($unknownIndexes as $offset => $unitIndex) {
                $units[$unitIndex] = $unknownPrices[$offset] ?? 0;
            }
        } elseif ($knownTotal !== $gross) {
            $lastIndex = count($units) - 1;
            $units[$lastIndex] = max(0, (int) $units[$lastIndex] + ($gross - $knownTotal));
        }

        return array_map(fn ($price) => max(0, (int) $price), $units);
    }

    /**
     * @return array<int, int>
     */
    protected function evenPrices(int $quantity, int $amount): array
    {
        if ($quantity <= 0) {
            return [];
        }

        $amount = max(0, $amount);
        $base = intdiv($amount, $quantity);
        $remainder = $amount % $quantity;
        $prices = array_fill(0, $quantity, $base);

        for ($i = 0; $i < $remainder; $i++) {
            $prices[$i]++;
        }

        return $prices;
    }

    protected function dateTime($date, $time): Carbon
    {
        $date = trim((string) $date);
        $time = trim((string) $time);

        if ($date === '') {
            throw new \InvalidArgumentException('Tanggal transaksi kosong.');
        }

        $date = str_replace(["\xEF\xBB\xBF", '.', '-'], ['', '/', '/'], $date);
        $time = str_replace('.', ':', $time ?: '00:00:00');
        $yearPart = explode('/', $date)[2] ?? '';
        $dateFormat = strlen($yearPart) === 2 ? 'd/m/y' : 'd/m/Y';
        $timeFormat = substr_count($time, ':') === 1 ? 'H:i' : 'H:i:s';

        return Carbon::createFromFormat($dateFormat . ' ' . $timeFormat, $date . ' ' . $time, $this->tz);
    }

    protected function money($value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $value = trim((string) $value);
        $value = preg_replace('/[^0-9,\.\-]/', '', $value) ?? '';

        if ($value === '' || $value === '-' || $value === '.') {
            return 0;
        }

        $negative = Str::startsWith($value, '-');
        $value = ltrim($value, '-');

        if (str_contains($value, ',') && str_contains($value, '.')) {
            if (strrpos($value, ',') > strrpos($value, '.')) {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }
        } elseif (substr_count($value, '.') > 1) {
            $parts = explode('.', $value);
            $thousandLike = collect(array_slice($parts, 1))->every(fn ($part) => strlen($part) === 3);
            $value = $thousandLike
                ? implode('', $parts)
                : $parts[0] . '.' . ($parts[1] ?? '0');
        } elseif (substr_count($value, ',') > 1) {
            $parts = explode(',', $value);
            $thousandLike = collect(array_slice($parts, 1))->every(fn ($part) => strlen($part) === 3);
            $value = $thousandLike
                ? implode('', $parts)
                : $parts[0] . '.' . ($parts[1] ?? '0');
        } elseif (str_contains($value, ',')) {
            $value = str_replace(',', '.', $value);
        }

        $number = is_numeric($value) ? (float) $value : 0;

        return $negative ? -$number : $number;
    }

    /**
     * @param array<string, mixed> $row
     */
    protected function note(array $row, string $eventType): string
    {
        $notes = ['MOKA History Import'];
        $notes[] = 'Event: ' . ($eventType ?: '-');

        foreach ([
            'outlet' => 'Source outlet',
            'other_note' => 'Other note',
            'collected_by' => 'Collected by',
            'served_by' => 'Served by',
            'customer' => 'Customer',
            'customer_phone' => 'Customer phone',
            'reason_of_refund' => 'Reason of refund',
        ] as $key => $label) {
            $value = $this->nullableString($row[$key] ?? null);
            if ($value) {
                $notes[] = $label . ': ' . $value;
            }
        }

        return implode("\n", $notes);
    }

    protected function nullableString($value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    protected function cleanCell($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = str_replace("\xEF\xBB\xBF", '', (string) $value);
        $value = preg_replace('/[\x00-\x1F\x7F\xC2\xA0]/u', '', $value) ?? $value;

        return trim($value);
    }

    protected function normalizeHeader(string $header): string
    {
        $header = $this->cleanCell($header) ?? '';
        $header = preg_replace('/\s*\([^)]*\)/', '', $header) ?? $header;

        return Str::snake(Str::lower($header));
    }

    /**
     * @param array<string, int> $summary
     */
    protected function putProgress(string $status, string $message, int $processed, int $total, array $summary = [], ?string $error = null): void
    {
        if (!$this->progressId) {
            return;
        }

        $percent = $total > 0 ? min(100, (int) floor(($processed / $total) * 100)) : 0;

        Cache::put(self::progressCacheKey($this->userId, $this->progressId), [
            'status' => $status,
            'message' => $message,
            'total' => $total,
            'processed' => $processed,
            'percent' => $percent,
            'summary' => $summary,
            'error' => $error,
        ], now()->addHour());
    }
}
