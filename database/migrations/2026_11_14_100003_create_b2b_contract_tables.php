<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tanpa draft: kontrak langsung aktif. Label Terjadwal/Berjalan/Berakhir dihitung dari tanggal.
        Schema::create('b2b_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('b2b_customer_id')->constrained('b2b_customers');
            $table->string('contract_number', 40)->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['active', 'terminated'])->default('active');
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->text('notes')->nullable();
            // Audit trail: id user disimpan apa adanya, tanpa foreign key/index (sama dengan b2b_customers).
            $table->unsignedBigInteger('terminated_by')->nullable();
            $table->timestamp('terminated_at')->nullable();
            $table->string('termination_reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['b2b_customer_id', 'status', 'start_date', 'end_date'], 'b2b_contracts_lookup_index');
        });

        // Harga kontrak per varian (varian sudah menentukan outlet). Beberapa baris per varian = harga bertingkat per qty.
        // Soft delete supaya item yang dibuang saat kontrak diedit tetap bisa dirujuk pesanan lama.
        // Kombinasi (kontrak, varian, min_qty) divalidasi unik di aplikasi, bukan unique index (bentrok dengan baris terhapus).
        Schema::create('b2b_contract_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('b2b_contract_id')->constrained('b2b_contracts');
            $table->foreignId('variant_id')->constrained('variant_products');
            $table->unsignedInteger('min_qty')->default(1)->comment('harga berlaku mulai qty ini');
            $table->bigInteger('price')->comment('harga kontrak per unit (rupiah), boleh di atas harga normal');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['b2b_contract_id', 'variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_contract_items');
        Schema::dropIfExists('b2b_contracts');
    }
};
