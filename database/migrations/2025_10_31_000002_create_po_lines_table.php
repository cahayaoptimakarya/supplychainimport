<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('po_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('qty_ordered', 18, 4);
            $table->decimal('qty_fulfilled', 18, 4)->default(0);
            $table->decimal('qty_remaining', 18, 4)->default(0);
            $table->decimal('fulfillment_percent', 9, 4)->default(0);
            $table->enum('status', ['open', 'partial', 'fulfilled'])->default('open');
            $table->timestamp('fulfillment_refreshed_at')->nullable();
            $table->decimal('cnt_ordered', 18, 4)->nullable();
            $table->string('pcs_cnt')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('po_lines');
    }
};
