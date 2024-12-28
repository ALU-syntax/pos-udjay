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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('cascade');
            $table->boolean('status')->default(true);
            $table->string('photo')->nullable();
            $table->decimal('harga_jual', 15, 2)->default(0)->unsigned();
            $table->decimal('harga_modal', 15, 2)->default(0)->unsigned();
            $table->softDeletes();
            // $table->bigInteger('harga_jual')->nullable();
            // $table->bigInteger('harga_modal')->nullable();
            $table->bigInteger('stock')->nullable();
            $table->foreignId('outlet_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
