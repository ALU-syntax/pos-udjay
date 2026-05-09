<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        Schema::create('raw_materials', function (Blueprint $table) {
            $table->id();

            $table->string('code', 50)->nullable()->unique();
            // contoh: RM-0001, RM-0002

            $table->string('name')->unique();
            // contoh: Daging Ayam, Telur Ayam, Susu UHT

            $table->foreignId('raw_material_category_id')
                ->nullable()
                ->constrained('raw_material_categories')
                ->nullOnDelete();

            $table->foreignId('base_unit_id')
                ->constrained('satuans')
                ->restrictOnDelete();

            $table->unsignedTinyInteger('is_stockable')->default(1);

            /*
             * contoh:
             * dry     = bahan kering
             * chilled = bahan dingin
             * frozen  = bahan beku
             * other   = lainnya
             */
            $table->string('storage_type', 50)->default('dry');

            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('raw_material_category_id');
            $table->index('base_unit_id');
            $table->index('storage_type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raw_materials');
    }
};
