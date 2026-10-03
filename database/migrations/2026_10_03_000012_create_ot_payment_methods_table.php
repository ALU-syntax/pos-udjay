<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ot_payment_methods', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id();
            $table->unsignedBigInteger('outlet_id')->nullable()->index();
            $table->unsignedBigInteger('payment_id')->nullable()->index();
            $table->unsignedBigInteger('category_payment_id')->nullable()->index();
            $table->string('code', 30);
            $table->string('label', 100);
            $table->string('nama_tipe_pembayaran', 50)->nullable();
            $table->integer('qris_expiry_minutes')->nullable()->default(15);
            $table->integer('payment_due_minutes')->nullable()->default(60);
            $table->boolean('enabled')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['outlet_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_payment_methods');
    }
};
