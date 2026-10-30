<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\ProductImageVariants;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Buat versi kecil (thumb/medium, JPEG + WebP) untuk foto produk yang sudah ada.
 * Foto asli tidak diubah; aman dijalankan ulang. Untuk foto besar jalankan dengan
 * `php -d memory_limit=512M artisan products:generate-image-variants`.
 */
class GenerateProductImageVariants extends Command
{
    protected $signature = 'products:generate-image-variants
        {--dry-run : Tampilkan foto yang akan diproses tanpa membuat file}
        {--force : Buat ulang walau versi kecil sudah ada}';

    protected $description = 'Buat versi kecil foto produk untuk menu order table';

    public function handle(ProductImageVariants $variants): int
    {
        $query = Product::query()->whereNotNull('photo')->where('photo', '!=', '');
        if (! $this->option('force')) {
            $query->where(fn ($q) => $q->whereNull('photo_thumb')->orWhereNull('photo_medium'));
        }

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->info('Semua foto produk sudah punya versi kecil.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->table(['ID', 'Produk', 'Foto', 'File ada'], (clone $query)->orderBy('id')->get()
                ->map(fn (Product $product) => [
                    $product->id, $product->name, $product->photo,
                    Storage::disk('local')->exists($product->photo) ? 'ya' : 'TIDAK',
                ]));
            $this->info("{$total} foto akan diproses. Jalankan tanpa --dry-run untuk membuat versi kecil.");

            return self::SUCCESS;
        }

        $failed = [];
        $done = 0;
        $bar = $this->output->createProgressBar($total);
        $query->chunkById(25, function ($products) use ($variants, &$failed, &$done, $bar) {
            foreach ($products as $product) {
                $variants->generate($product) ? $done++ : $failed[] = [$product->id, $product->name, $product->photo];
                $bar->advance();
            }
        });
        $bar->finish();
        $this->newLine(2);

        $this->info("Berhasil: {$done} foto.");
        if ($failed) {
            $this->warn('Gagal (tetap memakai foto asli; detail di storage/logs):');
            $this->table(['ID', 'Produk', 'Foto'], $failed);
        }

        return self::SUCCESS;
    }
}
