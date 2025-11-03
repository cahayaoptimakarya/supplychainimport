<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            if (!Schema::hasColumn('items', 'sku')) {
                $table->string('sku')->after('name')->unique();
            }
        });
        // Keep cnt nullable; no change to cnt constraint in dev phase
    }

    public function down(): void
    {
        // No change to cnt constraint on down; only drop sku if present

        Schema::table('items', function (Blueprint $table) {
            if (Schema::hasColumn('items', 'sku')) {
                try { $table->dropUnique('items_sku_unique'); } catch (\Throwable $e) {}
                $table->dropColumn('sku');
            }
        });
    }
};
