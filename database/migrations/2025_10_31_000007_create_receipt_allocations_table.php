<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipt_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('po_line_id')->constrained()->cascadeOnDelete();
            $table->decimal('qty', 18, 4);
            $table->timestamps();

            $table->unique(['receipt_item_id', 'po_line_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_allocations');
    }
};

