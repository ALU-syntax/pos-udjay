<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

/**
 * Versi kecil foto produk untuk menu order table. Foto asli (3–8 MB dari HP admin) tetap disimpan;
 * HP pelanggan memakai thumb (kartu menu) dan medium (detail), masing-masing JPEG + WebP.
 */
class ProductImageVariants
{
    /**
     * Sisi persegi (px). Menu, keranjang, dan detail order table menampilkan foto dalam kotak persegi
     * (object-cover), jadi dipotong persegi di tengah: bagian yang dibuang sama dengan yang disembunyikan
     * browser. Medium 1280 = lebar layar HP (±430 px) × kepadatan 3; thumb 320 untuk kartu 92 px.
     * Diproses dari besar ke kecil agar foto asli cukup dibaca sekali.
     */
    public const SIZES = ['medium' => 1280, 'thumb' => 320];

    public const DIRECTORY = 'product/variants';

    private const QUALITY = 75;

    /** GD menyimpan seluruh piksel di memori PHP; terukur ±8,5 byte/piksel (foto 75 MP ≈ 640 MB). */
    private const GD_BYTES_PER_PIXEL = 9;

    /** Simpan foto baru produk, buat versinya, lalu hapus foto lama beserta versinya. */
    public function attach(Product $product, UploadedFile $file): void
    {
        // Nilai dari database, bukan atribut yang mungkin sudah diubah fill() di controller.
        $old = [$product->getOriginal('photo'), $product->getOriginal('photo_thumb'), $product->getOriginal('photo_medium')];

        $product->forceFill([
            'photo' => $file->store('product'),
            'photo_thumb' => null,
            'photo_medium' => null,
            'photo_webp' => false,
        ])->save();
        $this->generate($product);

        $this->deleteFiles($product, ...$old);
    }

    /**
     * Buat thumb & medium dari foto asli. Gagal (file rusak, SVG, memori kurang) tidak dilempar:
     * dicatat ke log dan produk tetap memakai foto asli.
     */
    public function generate(Product $product): bool
    {
        $disk = Storage::disk('local');
        if (! $product->photo || ! $disk->exists($product->photo)) {
            return false;
        }

        // Disk local membuat folder 0700; web server harus bisa membaca versi kecil.
        File::ensureDirectoryExists($disk->path(self::DIRECTORY), 0755);
        @chmod($disk->path(self::DIRECTORY), 0755);

        // Kehabisan memori adalah fatal error yang tidak bisa ditangkap dan akan memutus request upload
        // di tengah jalan; lebih baik tanpa versi kecil (foto asli tetap dipakai).
        if (! $this->hasMemoryFor($disk->path($product->photo))) {
            Log::warning('Versi kecil foto produk dilewati: memori PHP tidak cukup untuk GD', [
                'product_id' => $product->id, 'photo' => $product->photo, 'memory_limit' => ini_get('memory_limit'),
            ]);

            return false;
        }

        $name = pathinfo($product->photo, PATHINFO_FILENAME);
        $written = [];
        $paths = [];
        $webp = function_exists('imagewebp') || extension_loaded('imagick');
        try {
            $manager = $this->manager();
            $image = $this->read($manager, $disk->path($product->photo))->orientate();
            foreach (self::SIZES as $variant => $max) {
                // Foto yang lebih kecil dari target tetap dipotong persegi, tapi tidak diperbesar.
                $image->fit($max, $max, function ($constraint) {
                    $constraint->upsize();
                }, 'center');
                // Ukuran ikut di nama file: bila ukuran diubah, URL berubah sehingga cache browser lama tidak terpakai.
                $base = self::DIRECTORY."/{$name}-{$variant}-{$max}";

                // JPEG tidak punya transparansi: PNG transparan diberi latar putih, bukan hitam.
                $jpeg = $manager->canvas($image->width(), $image->height(), '#ffffff')->insert($image);
                $jpeg->save($disk->path("{$base}.jpg"), self::QUALITY, 'jpg');
                $jpeg->destroy();
                $written[] = "{$base}.jpg";

                if ($webp) {
                    try {
                        $image->save($disk->path("{$base}.webp"), self::QUALITY, 'webp');
                        $written[] = "{$base}.webp";
                    } catch (\Throwable $e) {
                        $webp = false;
                    }
                }
                $paths[$variant] = "{$base}.jpg";
            }
            $image->destroy();
        } catch (\Throwable $e) {
            Log::warning('Gagal membuat versi kecil foto produk', [
                'product_id' => $product->id, 'photo' => $product->photo, 'error' => $e->getMessage(),
            ]);
            $disk->delete($written);

            return false;
        }

        foreach ($written as $path) {
            @chmod($disk->path($path), 0644);
        }
        if (! $webp) {
            // WebP gagal di tengah jalan: buang yang sempat dibuat agar tidak setengah lengkap.
            $disk->delete(array_filter($written, fn ($path) => str_ends_with($path, '.webp')));
        }

        $previous = [$product->photo_thumb, $product->photo_medium];
        $product->forceFill([
            'photo_thumb' => $paths['thumb'],
            'photo_medium' => $paths['medium'],
            'photo_webp' => $webp,
        ])->saveQuietly();
        // Dibuat ulang (mis. --force setelah ukuran diubah): buang versi lama yang namanya berbeda.
        $this->deleteFiles($product, null, ...$previous);

        return true;
    }

    /** Hapus foto asli lama + versinya, kecuali foto asli masih dipakai produk lain. */
    private function deleteFiles(Product $product, ?string $photo, ?string $thumb, ?string $medium): void
    {
        $disk = Storage::disk('local');
        foreach ([$thumb, $medium] as $variant) {
            if ($variant && $variant !== $product->photo_thumb && $variant !== $product->photo_medium) {
                $disk->delete([$variant, preg_replace('/\.jpe?g$/i', '.webp', $variant)]);
            }
        }
        if ($photo && $photo !== $product->photo
            && ! Product::withTrashed()->where('photo', $photo)->whereKeyNot($product->id)->exists()) {
            $disk->delete($photo);
        }
    }

    /**
     * Foto HP admin bisa mencapai 126 MP (8437×15000). Dengan Imagick, JPEG didekode langsung mendekati
     * ukuran yang dibutuhkan (DCT scaling 1/2–1/8) sehingga tidak perlu membuka seluruh piksel; tanpa ini
     * batas sumber daya ImageMagick di hosting terlampaui dan versi kecil gagal dibuat.
     */
    private function read(ImageManager $manager, string $path): \Intervention\Image\Image
    {
        if (! extension_loaded('imagick')) {
            return $manager->make($path);
        }
        $imagick = new \Imagick();
        $hint = max(self::SIZES) * 2;
        // Hanya untuk JPEG yang memang lebih besar dari petunjuk: ImageMagick 7 juga memperbesar JPEG kecil.
        $size = @getimagesize($path);
        if ($size && $size[2] === IMAGETYPE_JPEG && min($size[0], $size[1]) > $hint) {
            $imagick->setOption('jpeg:size', "{$hint}x{$hint}");
        }
        $imagick->readImage($path);

        return $manager->make($imagick);
    }

    private function hasMemoryFor(string $path): bool
    {
        if (extension_loaded('imagick')) {
            return true; // Imagick bekerja di luar memori PHP (terukur ±10 MB untuk foto 75 MP).
        }
        $limit = $this->bytes((string) ini_get('memory_limit'));
        $size = @getimagesize($path);
        if ($limit <= 0 || ! $size) {
            return true;
        }

        return memory_get_usage(true) + $size[0] * $size[1] * self::GD_BYTES_PER_PIXEL < $limit;
    }

    private function bytes(string $value): int
    {
        $number = (int) $value;

        return match (strtolower(substr(trim($value), -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }

    private function manager(): ImageManager
    {
        // Imagick jauh lebih hemat memori untuk foto besar; GD sebagai cadangan.
        return new ImageManager(['driver' => extension_loaded('imagick') ? 'imagick' : 'gd']);
    }
}
