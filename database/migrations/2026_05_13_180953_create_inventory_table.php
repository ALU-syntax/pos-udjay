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
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('outlet_id')->nullable();
            $table->unsignedBigInteger('brand_id')->nullable();

            $table->string('code', 50)->nullable();
            $table->string('name');

            $table->foreignId('inventory_type_id')
                ->constrained('inventory_types')
                ->restrictOnDelete();

            $table->boolean('is_active')->default(true);

            $table->softDeletes();
            $table->timestamps();

            $table->index('parent_id', 'idx_parent_id');
            $table->index('outlet_id', 'idx_outlet_id');
            $table->index('brand_id', 'idx_brand_id');
            $table->index('inventory_type_id', 'idx_type');
            $table->index('is_active', 'idx_is_active');

            $table->foreign('parent_id', 'inventory_parent_id_foreign')
                ->references('id')
                ->on('inventory')
                ->nullOnDelete();

            $table->foreign('outlet_id', 'inventory_outlet_id_foreign')
                ->references('id')
                ->on('outlets')
                ->nullOnDelete();

            $table->foreign('brand_id', 'inventory_brand_id_foreign')
                ->references('id')
                ->on('brands')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
