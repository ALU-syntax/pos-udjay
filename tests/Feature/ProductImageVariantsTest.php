<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\ProductImageVariants;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageVariantsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Schema::create('categories', fn (Blueprint $t) => $t->id());
        foreach ([
            '2024_11_20_234719_create_products_table.php',
            '2026_10_30_000003_add_photo_variants_to_products.php',
        ] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
        Storage::fake('local');
    }

    private function product(string $name = 'Kopi'): Product
    {
        return Product::forceCreate(['name' => $name, 'outlet_id' => 11]);
    }

    private function upload(int $width = 3000, int $height = 2000): UploadedFile
    {
        return UploadedFile::fake()->image('foto.jpg', $width, $height);
    }

    public function test_upload_keeps_original_and_creates_small_jpeg_and_webp_versions(): void
    {
        $product = $this->product();
        app(ProductImageVariants::class)->attach($product, $this->upload());
        $product->refresh();

        $disk = Storage::disk('local');
        $this->assertTrue($disk->exists($product->photo));
        $this->assertStringStartsWith('product/variants/', $product->photo_thumb);
        $this->assertTrue((bool) $product->photo_webp);
        foreach (['photo_thumb' => 320, 'photo_medium' => 1280] as $column => $side) {
            $jpeg = $product->{$column};
            $this->assertTrue($disk->exists($jpeg));
            $this->assertTrue($disk->exists(preg_replace('/\.jpg$/', '.webp', $jpeg)));
            $this->assertSame([$side, $side], array_slice(getimagesize($disk->path($jpeg)), 0, 2));
        }
    }

    public function test_portrait_photos_are_sharp_enough_for_square_display(): void
    {
        // Foto HP portrait 9:16: dulu diperkecil per sisi terpanjang menjadi 608×1080 sehingga blur di detail.
        $product = $this->product();
        app(ProductImageVariants::class)->attach($product, $this->upload(1800, 3200));
        $this->assertSame([1280, 1280], array_slice(getimagesize(Storage::disk('local')->path($product->refresh()->photo_medium)), 0, 2));
    }

    public function test_very_large_photos_are_decoded_at_reduced_size_and_still_sharp(): void
    {
        // Foto HP admin hingga 126 MP gagal diproses di hosting; JPEG besar didekode lebih kecil (jpeg:size).
        $product = $this->product();
        app(ProductImageVariants::class)->attach($product, $this->upload(2700, 4800));
        $product->refresh();
        $this->assertSame([1280, 1280], array_slice(getimagesize(Storage::disk('local')->path($product->photo_medium)), 0, 2));
        $this->assertSame([320, 320], array_slice(getimagesize(Storage::disk('local')->path($product->photo_thumb)), 0, 2));
    }

    public function test_small_photos_are_cropped_square_but_not_enlarged(): void
    {
        $product = $this->product();
        app(ProductImageVariants::class)->attach($product, $this->upload(200, 100));
        $this->assertSame([100, 100], array_slice(getimagesize(Storage::disk('local')->path($product->refresh()->photo_medium)), 0, 2));
    }

    public function test_replacing_photo_deletes_old_original_and_versions(): void
    {
        $images = app(ProductImageVariants::class);
        $product = $this->product();
        $images->attach($product, $this->upload());
        $old = $product->refresh()->only(['photo', 'photo_thumb', 'photo_medium']);

        $images->attach($product, $this->upload());
        $product->refresh();

        $disk = Storage::disk('local');
        $this->assertNotSame($old['photo'], $product->photo);
        foreach ($old as $path) {
            $this->assertFalse($disk->exists($path), "{$path} seharusnya terhapus");
        }
        $this->assertFalse($disk->exists(preg_replace('/\.jpg$/', '.webp', $old['photo_thumb'])));
        $this->assertTrue($disk->exists($product->photo));
        $this->assertTrue($disk->exists($product->photo_thumb));
    }

    public function test_original_shared_with_another_product_is_not_deleted(): void
    {
        $images = app(ProductImageVariants::class);
        $product = $this->product();
        $images->attach($product, $this->upload());
        $shared = $product->refresh()->photo;
        $this->product('Salinan')->forceFill(['photo' => $shared])->save();

        $images->attach($product, $this->upload());

        $this->assertTrue(Storage::disk('local')->exists($shared));
    }

    public function test_unreadable_photo_falls_back_to_original_without_partial_files(): void
    {
        $disk = Storage::disk('local');
        $disk->put('product/logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>');
        $product = $this->product();
        $product->forceFill(['photo' => 'product/logo.svg'])->save();

        $this->assertFalse(app(ProductImageVariants::class)->generate($product));
        $product->refresh();
        $this->assertNull($product->photo_thumb);
        $this->assertNull($product->photo_medium);
        $this->assertSame([], $disk->files('product/variants'));
    }

    public function test_backfill_command_supports_dry_run_and_is_idempotent(): void
    {
        $disk = Storage::disk('local');
        $disk->putFileAs('product', $this->upload(1600, 1200), 'lama.jpg');
        $product = $this->product();
        $product->forceFill(['photo' => 'product/lama.jpg'])->save();

        $this->artisan('products:generate-image-variants', ['--dry-run' => true])->assertSuccessful();
        $this->assertNull($product->refresh()->photo_thumb);

        $this->artisan('products:generate-image-variants')->expectsOutputToContain('Berhasil: 1 foto.')->assertSuccessful();
        $this->assertNotNull($product->refresh()->photo_thumb);
        $this->assertTrue($disk->exists('product/lama.jpg'));

        $this->artisan('products:generate-image-variants')
            ->expectsOutputToContain('Semua foto produk sudah punya versi kecil.')->assertSuccessful();
    }

    public function test_regenerating_removes_versions_with_outdated_names(): void
    {
        $disk = Storage::disk('local');
        $product = $this->product();
        app(ProductImageVariants::class)->attach($product, $this->upload());
        // Versi lama sebelum ukuran ikut di nama file (mis. buatan rilis sebelumnya).
        $disk->put('product/variants/lama-medium.jpg', 'x');
        $disk->put('product/variants/lama-medium.webp', 'x');
        $product->forceFill(['photo_medium' => 'product/variants/lama-medium.jpg'])->saveQuietly();

        $this->artisan('products:generate-image-variants', ['--force' => true])->assertSuccessful();

        $this->assertStringEndsWith('-medium-1280.jpg', $product->refresh()->photo_medium);
        $this->assertFalse($disk->exists('product/variants/lama-medium.jpg'));
        $this->assertFalse($disk->exists('product/variants/lama-medium.webp'));
        $this->assertTrue($disk->exists($product->photo_medium));
    }
}
