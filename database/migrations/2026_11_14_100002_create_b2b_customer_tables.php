<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Customer B2B sengaja terpisah dari tabel `customers` (B2C, ikut sync kasir Android & email ulang tahun).
        Schema::create('b2b_customers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->enum('type', ['individual', 'pt', 'cv'])->comment('individual = perorangan');
            $table->string('name');
            $table->string('npwp', 30)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            // Reminder diatur per customer. Tidak diatur = tidak ada yang dikirim (tidak ada pengaturan global).
            $table->boolean('reminder_enabled')->default(false);
            $table->json('reminder_channels')->nullable()->comment('["email"], ["whatsapp"], atau keduanya');
            $table->json('reminder_days')->nullable()->comment('hari relatif ke jatuh tempo, mis. [-3, -1, 0]');
            $table->boolean('reminder_overdue_daily')->default(false)->comment('kirim tiap hari setelah lewat jatuh tempo');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            // Audit trail: id user disimpan apa adanya, tanpa foreign key/index (user tidak dihapus permanen).
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('modified_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('type');
        });

        Schema::create('b2b_customer_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('b2b_customer_id')->constrained('b2b_customers');
            $table->string('name');
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('position', 100)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('receive_reminder')->default(false)->comment('lewat channel yang dipilih di customer');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('b2b_customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('b2b_customer_id')->constrained('b2b_customers');
            $table->string('label', 100);
            $table->string('recipient_name')->nullable();
            $table->string('phone', 30)->nullable();
            $table->text('address');
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_default_billing')->default(false);
            $table->boolean('is_default_shipping')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_customer_addresses');
        Schema::dropIfExists('b2b_customer_contacts');
        Schema::dropIfExists('b2b_customers');
    }
};
