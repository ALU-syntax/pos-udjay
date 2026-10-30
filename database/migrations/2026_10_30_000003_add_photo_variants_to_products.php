<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Versi kecil foto produk untuk order table (lihat ProductImageVariants). Kolom berisi path JPEG;
     * photo_webp menandai file .webp dengan nama sama ikut dibuat. Kosong = pakai foto asli.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('photo_thumb')->nullable();
            $table->string('photo_medium')->nullable();
            $table->boolean('photo_webp')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['photo_thumb', 'photo_medium', 'photo_webp']);
        });
    }
};
