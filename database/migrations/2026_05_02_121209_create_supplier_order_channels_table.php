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
        Schema::create('supplier_order_channels', function (Blueprint $table) {
            $table->id();

            $table->foreignId('supplier_id')
                ->constrained('suppliers')
                ->cascadeOnDelete();

            /*
             * Contoh channel_type:
             * whatsapp
             * phone
             * marketplace
             * app
             * website
             * email
             * offline_store
             * other
             */
            $table->string('channel_type', 50);

            /*
             * Contoh:
             * WhatsApp Pak Budi
             * Tokopedia
             * Shopee
             * Sukanda App
             * Toko Sumber Makmur
             */
            $table->string('channel_name')->nullable();

            /*
             * Bisa berisi:
             * - nomor WA
             * - nama toko marketplace
             * - username akun
             * - kode customer
             * - nama outlet offline
             */
            $table->string('identifier')->nullable();

            $table->string('url')->nullable();
            $table->text('address')->nullable();

            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('supplier_id');
            $table->index('channel_type');
            $table->index('is_primary');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_order_channels');
    }
};
