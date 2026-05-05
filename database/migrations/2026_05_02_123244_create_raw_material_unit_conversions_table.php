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
        Schema::create('raw_material_unit_conversions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('raw_material_id')
                ->constrained('raw_materials')
                ->cascadeOnDelete();

            $table->foreignId('from_unit_id')
                ->constrained('satuans')
                ->restrictOnDelete();

            $table->foreignId('to_unit_id')
                ->constrained('satuans')
                ->restrictOnDelete();

            /*
             * contoh:
             * 1 box telur = 15 kg
             * multiplier = 15
             *
             * 1 kg = 1000 gram
             * multiplier = 1000
             */
            $table->decimal('multiplier', 18, 6);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique([
                'raw_material_id',
                'from_unit_id',
                'to_unit_id',
            ], 'raw_material_unit_conversion_unique');

            $table->index('raw_material_id');
            $table->index('from_unit_id');
            $table->index('to_unit_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('raw_material_unit_conversions');
    }
};
