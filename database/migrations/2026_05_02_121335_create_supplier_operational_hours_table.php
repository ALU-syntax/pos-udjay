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
         Schema::create('supplier_operational_hours', function (Blueprint $table) {
            $table->id();

            $table->foreignId('supplier_id')
                ->constrained('suppliers')
                ->cascadeOnDelete();

            /*
             * 1 = Monday
             * 2 = Tuesday
             * 3 = Wednesday
             * 4 = Thursday
             * 5 = Friday
             * 6 = Saturday
             * 7 = Sunday
             */
            $table->unsignedTinyInteger('day_of_week');

            $table->time('open_time')->nullable();
            $table->time('close_time')->nullable();

            /*
             * Untuk mendukung supplier yang punya lebih dari 1 sesi jam operasional
             * contoh:
             * Senin sesi 1: 08:00 - 12:00
             * Senin sesi 2: 13:00 - 17:00
             */
            $table->unsignedTinyInteger('sequence')->default(1);

            $table->boolean('is_24_hours')->default(false);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('supplier_id');
            $table->index('day_of_week');

            $table->unique([
                'supplier_id',
                'day_of_week',
                'sequence',
            ], 'supplier_operational_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_operational_hours');
    }
};
