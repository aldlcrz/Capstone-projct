<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use App\Services\Messaging\LumbarongSystemMessageService;
use App\Services\Financial\FinancialLedgerService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordSellerPaymentService
{
    /**
     * Record an actual direct payment received by the seller for Store Pickup, Special Delivery, or COD.
     */
    public function recordPayment(
        Order $order,
        User $seller,
        string $paymentMethod,
        float $amountReceived,
        ?\DateTimeInterface $receivedAt = null,
        ?string $referenceNumber = null,
        ?UploadedFile $proofFile = null,
        ?string $notes = null
    ): Order {
        // 1. Authorization: Only the assigned artisan seller can record direct payments
        if ($seller->role !== 'seller' || (string)$order->sellerId !== (string)$seller->id) {
            abort(403, 'Unauthorized. Only the assigned artisan seller can record direct payments for this order.');
        }

        // 2. Order Eligibility: Must be a direct-to-seller arrangement (Store Pickup, Special Delivery, COD)
        if ($order->isPlatformHeldPayment()) {
            throw ValidationException::withMessages([
                'order' => ['This order is paid through platform online payment. Sellers cannot record platform-held payments.'],
            ]);
        }

        // Cannot record payment on cancelled or declined orders
        $statusLower = strtolower(trim((string)$order->status));
        if (in_array($statusLower, ['cancelled', 'declined'], true)) {
            throw ValidationException::withMessages([
                'order' => ['Cannot record payment for a cancelled or declined order.'],
            ]);
        }

        return DB::transaction(function () use ($order, $seller, $paymentMethod, $amountReceived, $receivedAt, $referenceNumber, $proofFile, $notes) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();

            $orderTotal = (float) $lockedOrder->totalAmount;
            $previouslyPaid = $lockedOrder->totalPaidAmount();
            $outstanding = max(0.00, round($orderTotal - $previouslyPaid, 2));

            // Prevent recording twice if already fully paid
            if ($outstanding <= 0.00 || in_array(strtolower(trim((string)$lockedOrder->paymentStatus)), ['paid', 'verified'], true)) {
                throw ValidationException::withMessages([
                    'order' => ['Payment for this order has already been fully recorded and verified.'],
                ]);
            }

            // Validate amount received does not exceed remaining outstanding balance
            if ($amountReceived <= 0 || round($amountReceived, 2) > round($outstanding, 2)) {
                throw ValidationException::withMessages([
                    'amount_received' => ["Amount received must be greater than 0 and cannot exceed the outstanding balance of ₱" . number_format($outstanding, 2) . "."],
                ]);
            }

            // Handle optional proof upload
            $proofPath = null;
            if ($proofFile && $proofFile->isValid()) {
                $proofPath = $proofFile->store('payments', 'public');
            }

            // Cumulative payment calculation
            $cumulativePaid = round($previouslyPaid + $amountReceived, 2);
            $isFullyPaid = round($cumulativePaid, 2) >= round($orderTotal, 2);

            // Update order attributes
            $cleanMethod = trim($paymentMethod);
            $lockedOrder->paymentMethod = $cleanMethod;
            $lockedOrder->paymentStatus = $isFullyPaid ? 'Paid' : 'Partially Paid';
            $lockedOrder->total_verified_payments = (string) number_format($cumulativePaid, 2, '.', '');
            if ($referenceNumber) {
                $lockedOrder->paymentReference = trim($referenceNumber);
            }
            if ($proofPath) {
                $lockedOrder->paymentProof = $proofPath;
            }
            $lockedOrder->save();

            // Status history audit
            $statusDesc = $isFullyPaid ? "Paid in full" : "Partially paid (₱" . number_format($cumulativePaid, 2) . " of ₱" . number_format($orderTotal, 2) . ")";
            OrderStatusHistory::create([
                'orderId'        => $lockedOrder->id,
                'previousStatus' => $lockedOrder->status,
                'newStatus'      => $lockedOrder->status,
                'updatedBy'      => $seller->id,
                'userRole'       => 'seller',
                'notes'          => "Direct payment of ₱" . number_format($amountReceived, 2) . " via {$cleanMethod} recorded by seller. Status: {$statusDesc}." . ($notes ? " Notes: {$notes}" : ""),
            ]);

            // Notify customer in LumBarong official system messaging
            $shopName = $seller->shopName ?: ($seller->name ?: 'Artisan Shop');
            $idempotencyTag = "<!-- [seller_payment_recorded:{$lockedOrder->id}] -->";
            $shortOrder = '#LB-' . strtoupper(substr($lockedOrder->id, -8));
            $formattedAmt = '₱' . number_format($amountReceived, 2);
            $dateStr = ($receivedAt ? \Carbon\Carbon::instance($receivedAt) : now())->format('M d, Y h:i A');
            $remainingAfter = max(0.00, round($orderTotal - $cumulativePaid, 2));

            $lines = [
                "Hello! This is LumBarong.",
                "",
                "The artisan shop ({$shopName}) has recorded your payment for Order {$shortOrder}.",
                "",
                "Payment Details:",
                "Order Number: {$shortOrder}",
                "Amount Received: {$formattedAmt}",
                "Total Verified Paid: ₱" . number_format($cumulativePaid, 2) . " of ₱" . number_format($orderTotal, 2),
                "Remaining Balance: ₱" . number_format($remainingAfter, 2),
                "Payment Method: {$cleanMethod}",
                "Date Received: {$dateStr}",
                "Status: " . ($isFullyPaid ? "Paid (Confirmed by Artisan)" : "Partially Paid (Confirmed by Artisan)"),
            ];

            if ($referenceNumber) {
                $lines[] = "Reference Number: " . trim($referenceNumber);
            }

            $lines[] = "";
            $lines[] = "Thank you for supporting our heritage artisans!";
            $lines[] = "";
            $lines[] = "LumBarong Support";
            $lines[] = "";
            $lines[] = $idempotencyTag;

            try {
                \App\Models\Message::create([
                    'id'         => (string) \Illuminate\Support\Str::uuid(),
                    'senderId'   => LumbarongSystemMessageService::getSystemUser()->id,
                    'receiverId' => $lockedOrder->customerId,
                    'content'    => implode("\n", $lines),
                    'read'       => false,
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("Failed to send seller payment recorded message: " . $e->getMessage());
            }

            return $lockedOrder;
        });
    }
}
