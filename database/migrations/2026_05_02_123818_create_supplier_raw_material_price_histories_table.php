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
        Schema::create('supplier_raw_material_price_histories', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('supplier_raw_material_id');


            $table->decimal('price', 18, 2);

            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();

            /*
             * contoh:
             * non_tax
             * tax_inclusive
             * tax_exclusive
             */
            $table->string('tax_type', 50)->default('non_tax');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->foreign('supplier_raw_material_id', 'srm_price_hist_srm_id_fk')
                ->references('id')
                ->on('supplier_raw_materials')
                ->cascadeOnDelete();

            $table->index('supplier_raw_material_id', 'srm_price_hist_srm_id_idx');
            $table->index('effective_from');
            $table->index('effective_until');
            $table->index('tax_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_raw_material_price_histories');
    }
};
