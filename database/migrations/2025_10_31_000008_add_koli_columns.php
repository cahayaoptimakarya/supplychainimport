<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('po_lines', function (Blueprint $table) {
            $table->decimal('koli_ordered', 18, 4)->default(0)->after('qty_ordered');
        });
        Schema::table('shipment_items', function (Blueprint $table) {
            $table->decimal('koli_expected', 18, 4)->default(0)->after('qty_expected');
        });
        Schema::table('receipt_items', function (Blueprint $table) {
            $table->decimal('koli_received', 18, 4)->default(0)->after('qty_received');
        });
    }

    public function down(): void
    {
        Schema::table('po_lines', function (Blueprint $table) {
            $table->dropColumn('koli_ordered');
        });
        Schema::table('shipment_items', function (Blueprint $table) {
            $table->dropColumn('koli_expected');
        });
        Schema::table('receipt_items', function (Blueprint $table) {
            $table->dropColumn('koli_received');
        });
    }
};

