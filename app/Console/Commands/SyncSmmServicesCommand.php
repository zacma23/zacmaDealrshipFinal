<?php

namespace App\Console\Commands;

use App\Models\SmmProvider;
use App\Services\SmmProvider\SmmProviderManager;
use Illuminate\Console\Command;

class SyncSmmServicesCommand extends Command
{
    protected $signature = 'smm:sync-services {provider_id? : Optional provider ID to sync}';
    protected $description = 'Synchronize services and pricing from upstream SMM providers';

    public function handle(SmmProviderManager $providerManager): int
    {
        $providerId = $this->argument('provider_id');
        $providers = $providerId 
            ? SmmProvider::where('id', $providerId)->get() 
            : SmmProvider::where('status', 'active')->get();

        if ($providers->isEmpty()) {
            $this->warn("No active SMM providers found to sync.");
            return Command::SUCCESS;
        }

        foreach ($providers as $provider) {
            $this->info("Syncing provider [{$provider->name}]...");
            $adapter = $providerManager->getAdapter($provider);

            // Sync balance
            $balanceResult = $adapter->getBalance();
            if ($balanceResult['success']) {
                $this->info("Provider balance: {$balanceResult['balance']} {$balanceResult['currency']}");
            }

            // Sync services
            $servicesResult = $adapter->getServices();
            if ($servicesResult['success']) {
                $count = count($servicesResult['services']);
                $provider->update(['last_sync_at' => now(), 'health_status' => 'healthy']);
                $this->info("Synced {$count} services from [{$provider->name}].");
            } else {
                $this->error("Failed to sync services from [{$provider->name}]: " . ($servicesResult['error'] ?? 'Unknown error'));
            }
        }

        return Command::SUCCESS;
    }
}
