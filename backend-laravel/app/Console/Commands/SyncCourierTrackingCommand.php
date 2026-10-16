<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\CourierTrackingService;
use Illuminate\Console\Command;

class SyncCourierTrackingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'courier:sync-tracking {--order= : Specific Order ID to sync} {--simulate : Run in demo simulation mode}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize live parcel tracking status from courier networks (J&T, LBC, Flash Express)';

    /**
     * Execute the console command.
     */
    public function handle(CourierTrackingService $trackingService): int
    {
        $this->info("=== LumBarong Real-Time Courier Tracking Synchronizer ===");

        $orderId = $this->option('order');

        $query = Order::query()
            ->whereIn('status', ['Shipped', 'In Transit'])
            ->whereNotNull('trackingNumber')
            ->where('trackingNumber', '!=', '')
            ->whereNotIn('courierName', ['Store Pickup', 'Special Delivery (Local Artisan Rider)']);

        if ($orderId) {
            $query->where('id', $orderId);
        }

        $orders = $query->get();

        if ($orders->isEmpty()) {
            $this->comment("No active courier parcels found needing synchronization.");
            return Command::SUCCESS;
        }

        $this->info("Found {$orders->count()} active courier parcel(s) to synchronize.");

        $syncedCount = 0;
        foreach ($orders as $order) {
            $this->line("Checking Order #{$order->id} | {$order->courierName} #{$order->trackingNumber} (Current: {$order->status})");

            $result = $trackingService->syncOrderTracking($order);

            if ($result['synced'] ?? false) {
                $syncedCount++;
                $this->info("  ✓ Successfully updated to {$result['new_status']}!");
            } else {
                $this->comment("  - No transition: " . ($result['message'] ?? $result['reason'] ?? 'Up to date'));
            }
        }

        $this->info("Synchronization complete: {$syncedCount} order(s) updated.");
        return Command::SUCCESS;
    }
}
