<?php

namespace App\Console\Commands;

use App\Models\SmmOrder;
use App\Services\SmmOrder\SmmOrderEngine;
use Illuminate\Console\Command;

class SyncSmmOrdersCommand extends Command
{
    protected $signature = 'smm:sync-orders {--limit=50 : Max orders to sync in one run}';
    protected $description = 'Synchronize pending and in-progress SMM orders with provider APIs';

    public function handle(SmmOrderEngine $orderEngine): int
    {
        $limit = (int)$this->option('limit');
        $orders = SmmOrder::whereIn('status', [
            SmmOrder::STATUS_PENDING,
            SmmOrder::STATUS_PROCESSING,
            SmmOrder::STATUS_IN_PROGRESS,
        ])
        ->whereNotNull('provider_order_id')
        ->limit($limit)
        ->get();

        $this->info("Found {$orders->count()} active orders to synchronize.");

        $synced = 0;
        foreach ($orders as $order) {
            $orderEngine->syncOrderStatus($order);
            $synced++;
        }

        $this->info("Successfully synchronized {$synced} orders.");
        return Command::SUCCESS;
    }
}
