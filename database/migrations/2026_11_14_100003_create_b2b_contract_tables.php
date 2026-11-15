<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('b2b_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('b2b_customer_id')->constrained('b2b_customers')->cascadeOnDelete();
            $table->string('contract_number', 40)->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['draft', 'active', 'expired', 'terminated'])->default('draft');
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('activated_at')->nullable();
            $table->foreignId('terminated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('terminated_at')->nullable();
            $table->string('termination_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['b2b_customer_id', 'status', 'start_date', 'end_date'], 'b2b_contracts_lookup_index');
        });

        Schema::create('b2b_contract_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('b2b_contract_id')->constrained('b2b_contracts')->cascadeOnDelete();
            $table->enum('scope', ['all', 'category', 'product', 'variant']);
            $table->foreignId('outlet_id')->nullable()->comment('kosong = semua outlet')->constrained('outlets')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('variant_products')->cascadeOnDelete();
            $table->enum('discount_type', ['percent', 'nominal', 'fixed_price']);
            $table->decimal('discount_value', 15, 2);
            $table->unsignedInteger('min_qty')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_contract_rules');
        Schema::dropIfExists('b2b_contracts');
    }
};
