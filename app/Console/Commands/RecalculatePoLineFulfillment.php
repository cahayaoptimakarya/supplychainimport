<?php

namespace App\Console\Commands;

use App\Models\PoLine;
use Illuminate\Console\Command;

class RecalculatePoLineFulfillment extends Command
{
    protected $signature = 'po-lines:recalculate {--chunk=500 : Number of PO lines processed per chunk}';

    protected $description = 'Recalculate stored fulfillment metrics for all purchase order lines.';

    public function handle(): int
    {
        $chunkSize = (int) $this->option('chunk');
        if ($chunkSize <= 0) {
            $chunkSize = 500;
        }

        $total = PoLine::count();
        if ($total === 0) {
            $this->info('No PO lines found. Nothing to recalculate.');
            return self::SUCCESS;
        }

        $this->info(sprintf('Recalculating fulfillment metrics for %d PO line(s)...', $total));
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        PoLine::with('allocations')->chunkById($chunkSize, function ($lines) use ($bar) {
            foreach ($lines as $line) {
                $line->refreshFulfillmentMetrics();
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);
        $this->info('Fulfillment metrics refreshed successfully.');

        return self::SUCCESS;
    }
}

