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
            // Satu kontak (PIC) dan satu alamat per customer; telepon & email di atas juga jadi tujuan reminder.
            $table->string('contact_name')->nullable()->comment('nama PIC');
            $table->text('address')->nullable()->comment('alamat penagihan sekaligus pengiriman');
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('postal_code', 10)->nullable();
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
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_customers');
    }
};
