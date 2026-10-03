<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ot_order_item_modifiers', function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->id();
            $table->foreignId('order_item_id')
                ->constrained('ot_order_items')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('modifier_id')->index();
            $table->string('name');
            $table->bigInteger('harga');
            $table->integer('qty')->default(1);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_order_item_modifiers');
    }
};
