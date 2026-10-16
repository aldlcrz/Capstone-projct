<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\CourierTrackingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncOrderCourierTrackingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Order $order;
    public int $tries = 3;
    public int $backoff = 300; // 5 minutes backoff between retries

    /**
     * Create a new job instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     */
    public function handle(CourierTrackingService $trackingService): void
    {
        if ($this->order->isStorePickup() || $this->order->isSpecialDelivery()) {
            return;
        }

        try {
            $trackingService->syncOrderTracking($this->order);
        } catch (\Throwable $e) {
            Log::warning("Failed sync job for order {$this->order->id}: " . $e->getMessage());
            throw $e;
        }
    }
}
