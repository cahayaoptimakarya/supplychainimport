<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('po_lines', 'qty_fulfilled')) {
            return;
        }

        Schema::table('po_lines', function (Blueprint $table) {
            $table->decimal('qty_fulfilled', 18, 4)->default(0)->after('qty_ordered');
            $table->decimal('qty_remaining', 18, 4)->default(0)->after('qty_fulfilled');
            $table->decimal('fulfillment_percent', 9, 4)->default(0)->after('qty_remaining');
            $table->string('status', 20)->default('open')->after('fulfillment_percent');
            $table->timestamp('fulfillment_refreshed_at')->nullable()->after('status');
        });

        DB::table('po_lines')->chunkById(500, function ($lines) {
            foreach ($lines as $line) {
                $ordered = (float) $line->qty_ordered;
                $fulfilled = (float) DB::table('receipt_allocations')
                    ->where('po_line_id', $line->id)
                    ->sum('qty');
                $remaining = max(0.0, $ordered - $fulfilled);
                $percent = $ordered > 0 ? ($fulfilled / $ordered) * 100 : 0;
                $status = match (true) {
                    $ordered <= 0 => 'open',
                    $remaining <= 0.00001 => 'fulfilled',
                    $fulfilled > 0 => 'partial',
                    default => 'open',
                };

                DB::table('po_lines')->where('id', $line->id)->update([
                    'qty_fulfilled' => round($fulfilled, 4),
                    'qty_remaining' => round($remaining, 4),
                    'fulfillment_percent' => round(min(100, max(0, $percent)), 4),
                    'status' => $status,
                    'fulfillment_refreshed_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('po_lines', 'qty_fulfilled')) {
            return;
        }

        Schema::table('po_lines', function (Blueprint $table) {
            $table->dropColumn([
                'qty_fulfilled',
                'qty_remaining',
                'fulfillment_percent',
                'status',
                'fulfillment_refreshed_at',
            ]);
        });
    }
};
