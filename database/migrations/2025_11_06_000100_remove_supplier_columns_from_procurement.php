<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('purchase_orders', 'supplier_id')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->dropColumn('supplier_id');
            });
        }

        if (Schema::hasColumn('shipments', 'supplier_id')) {
            Schema::table('shipments', function (Blueprint $table) {
                $table->dropColumn('supplier_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_orders', 'supplier_id')) {
                $table->foreignId('supplier_id')->nullable()->index();
            }
        });

        Schema::table('shipments', function (Blueprint $table) {
            if (!Schema::hasColumn('shipments', 'supplier_id')) {
                $table->foreignId('supplier_id')->nullable()->index();
            }
        });
    }
};

