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

        DB::statement("UPDATE items SET cnt = '' WHERE cnt IS NULL");
        DB::statement('ALTER TABLE items MODIFY cnt VARCHAR(255) NOT NULL');
    }

    public function down(): void
    {
        // revert cnt to nullable
        DB::statement('ALTER TABLE items MODIFY cnt VARCHAR(255) NULL');

        Schema::table('items', function (Blueprint $table) {
            if (Schema::hasColumn('items', 'sku')) {
                try { $table->dropUnique('items_sku_unique'); } catch (\Throwable $e) {}
                $table->dropColumn('sku');
            }
        });
    }
};

