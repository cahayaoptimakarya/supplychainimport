<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->nullable()->index();
            $table->string('container_no')->nullable();
            $table->string('pl_no')->nullable();
            $table->date('etd')->nullable();
            $table->date('eta')->nullable();
            $table->string('status')->default('planned'); // planned -> ready_at_port -> on_board -> arrived -> under_bc -> released -> delivered_to_main_wh -> received
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
