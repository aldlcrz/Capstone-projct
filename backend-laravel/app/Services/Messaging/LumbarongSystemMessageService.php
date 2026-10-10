<?php

namespace App\Services\Messaging;

use App\Models\Message;
use App\Models\Notification;
use App\Models\Order;
use App\Models\RefundTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LumbarongSystemMessageService
{
    /**
     * Get or create the official LumBarong system user.
     */
    public static function getSystemUser(): User
    {
        $user = User::where('role', 'superadmin')->first()
            ?: User::where('role', 'admin')->first()
            ?: User::where('email', 'support@lumbarong.com')->first();

        if (!$user) {
            $user = User::firstOrCreate(
                ['email' => 'support@lumbarong.com'],
                [
                    'id'             => (string) Str::uuid(),
                    'name'           => 'LumBarong Support',
                    'role'           => 'admin',
                    'status'         => 'active',
                    'isVerified'     => true,
                    'hasPasswordSet' => true,
                    'password'       => \Illuminate\Support\Facades\Hash::make(Str::random(32)),
                ]
            );
        }

        return $user;
    }

    /**
     * Send official LumBarong inbox message when a customer's sukli refund is processed.
     */
    public static function sendSukliRefundCompletedMessage(
        Order $order,
        RefundTransaction $refundTx,
        ?User $admin = null
    ): ?Message {
        if (empty($order->customerId)) {
            return null;
        }

        $systemUser = static::getSystemUser();
        $idempotencyTag = "<!-- [sukli_refund:{$refundTx->id}] -->";

        // Idempotency check: prevent duplicate messages for the same completed refund
        $existing = Message::where('receiverId', $order->customerId)
            ->where('content', 'LIKE', "%{$idempotencyTag}%")
            ->first();

        if ($existing) {
            return $existing;
        }

        $shortOrderNumber = '#LB-' . strtoupper(substr($order->id, -8));
        $formattedOrderTotal = '₱' . number_format((float) ($order->totalAmount ?? 0), 2);
        
        $originalPaymentAmount = 0.0;
        if ($order->latestPaymentTransaction && ($order->latestPaymentTransaction->detected_amount > 0 || $order->latestPaymentTransaction->expected_amount > 0)) {
            $originalPaymentAmount = (float) ($order->latestPaymentTransaction->detected_amount ?: $order->latestPaymentTransaction->expected_amount);
        } else {
            $originalPaymentAmount = (float) ($order->totalAmount + $refundTx->refund_amount);
        }
        $formattedOriginalPayment = '₱' . number_format($originalPaymentAmount, 2);
        $formattedSukliRefund = '₱' . number_format((float) $refundTx->refund_amount, 2);

        $methodName = ucfirst(strtolower($refundTx->refund_method ?: ($order->paymentMethod ?: 'GCash')));
        if (strtoupper($methodName) === 'GCASH') $methodName = 'GCash';
        if (strtoupper($methodName) === 'MAYA') $methodName = 'Maya';

        // Format masked destination
        $maskedDest = $refundTx->destination_account_masked;
        $encryptedAccount = (string) ($refundTx->destination_account_encrypted ?? '');
        if (empty($maskedDest) && !empty($encryptedAccount)) {
            try {
                $raw = Crypt::decryptString($encryptedAccount);
            } catch (\Throwable $e) {
                $raw = $encryptedAccount;
            }
            $clean = preg_replace('/\s+/', '', $raw);
            $len = strlen($clean);
            $maskedDest = $len > 4
                ? substr($clean, 0, 4) . str_repeat('*', max(2, $len - 7)) . substr($clean, -3)
                : $clean;
        }
        $maskedDest = $maskedDest ?: '09*********';

        $refNumber = $refundTx->transfer_reference ?: 'N/A';
        $refundDate = $refundTx->processed_at
            ? ($refundTx->processed_at instanceof \Carbon\Carbon ? $refundTx->processed_at->format('M d, Y h:i A') : \Carbon\Carbon::parse($refundTx->processed_at)->format('M d, Y h:i A'))
            : now()->format('M d, Y h:i A');

        // Build message content
        $lines = [
            "Hello! This is LumBarong.",
            "",
            "Your sukli refund for Order {$shortOrderNumber} has been processed successfully.",
            "",
            "Order Total: {$formattedOrderTotal}",
            "Original Payment: {$formattedOriginalPayment}",
            "Sukli Refunded: {$formattedSukliRefund}",
            "Refund Method: {$methodName}",
            "Refund Destination: {$maskedDest}",
            "Refund Reference: {$refNumber}",
            "Refund Date: {$refundDate}",
            "",
        ];

        if (!empty($refundTx->transfer_proof_path)) {
            $proofUrl = route('orders.refund-proof', ['orderId' => $order->id, 'refundId' => $refundTx->id]);
            $lines[] = "The refund proof/receipt is attached to this message for your reference.";
            $lines[] = "";
            $lines[] = "[View / Download Refund Receipt]({$proofUrl})";
            $lines[] = "[![Refund Proof Receipt]({$proofUrl})]({$proofUrl})";
            $lines[] = "";
        }

        $lines[] = "Thank you for shopping with LumBarong!";
        $lines[] = "";
        $lines[] = $idempotencyTag;

        $content = implode("\n", $lines);

        try {
            return Message::create([
                'id'         => (string) Str::uuid(),
                'senderId'   => $systemUser->id,
                'receiverId' => $order->customerId,
                'content'    => $content,
                'read'       => false,
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to send LumBarong sukli refund inbox message: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Send official LumBarong inbox message when an order is cancelled.
     */
    public static function sendOrderCancelledMessage(
        Order $order,
        ?string $reason = null,
        ?string $cancelledBy = null
    ): ?Message {
        if (empty($order->customerId)) {
            return null;
        }

        $systemUser = static::getSystemUser();
        $idempotencyTag = "<!-- [order_cancellation:{$order->id}] -->";

        // Idempotency check: prevent duplicate messages for the same order cancellation
        $existing = Message::where('receiverId', $order->customerId)
            ->where('content', 'LIKE', "%{$idempotencyTag}%")
            ->first();

        if ($existing) {
            return $existing;
        }

        $shortOrderNumber = '#LB-' . strtoupper(substr($order->id, -8));
        $cancelDate = now()->format('M d, Y h:i A');
        $displayReason = trim((string) ($reason ?: ($order->cancellationReason ?: 'Cancelled upon request')));
        $displayCancelledBy = trim((string) ($cancelledBy ?: 'LumBarong Platform'));

        // Check if customer made a payment
        $paymentStatusLower = strtolower(trim((string) ($order->paymentStatus ?? '')));
        $hasPaid = in_array($paymentStatusLower, ['paid', 'verified', 'payment verified', 'refund requested'], true)
            || ($order->latestPaymentTransaction && in_array($order->latestPaymentTransaction->status, ['VERIFIED', 'PAID'], true));

        $lines = [
            "Hello! This is LumBarong.",
            "",
            "We would like to inform you that Order {$shortOrderNumber} has been cancelled.",
            "",
            "Order Details:",
            "Order Number: {$shortOrderNumber}",
            "Cancellation Date: {$cancelDate}",
            "Cancellation Reason: {$displayReason}",
            "Cancelled By: {$displayCancelledBy}",
            "",
            "Please review your order history for the latest order status.",
            "",
        ];

        if ($hasPaid) {
            $lines[] = "If you have already paid for this order, any applicable refund will follow LumBarong's payment and refund process. We will notify you when the refund status changes.";
        } else {
            $lines[] = "No payment deduction was captured for this order.";
        }

        $lines[] = "";
        $lines[] = "Thank you for your understanding.";
        $lines[] = "";
        $lines[] = "LumBarong Support";
        $lines[] = "";
        $lines[] = $idempotencyTag;

        $content = implode("\n", $lines);

        try {
            return Message::create([
                'id'         => (string) Str::uuid(),
                'senderId'   => $systemUser->id,
                'receiverId' => $order->customerId,
                'content'    => $content,
                'read'       => false,
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to send LumBarong order cancellation inbox message: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Send official LumBarong inbox message when a cancelled order's refund is processed.
     */
    public static function sendCancellationRefundCompletedMessage(
        Order $order,
        RefundTransaction $refundTx,
        ?User $admin = null
    ): ?Message {
        if (empty($order->customerId)) {
            return null;
        }

        $systemUser = static::getSystemUser();
        $idempotencyTag = "<!-- [cancellation_refund:{$refundTx->id}] -->";

        // Idempotency check
        $existing = Message::where('receiverId', $order->customerId)
            ->where('content', 'LIKE', "%{$idempotencyTag}%")
            ->first();

        if ($existing) {
            return $existing;
        }

        $shortOrderNumber = '#LB-' . strtoupper(substr($order->id, -8));
        $formattedRefundAmount = '₱' . number_format((float) $refundTx->refund_amount, 2);

        $methodName = ucfirst(strtolower($refundTx->refund_method ?: ($order->paymentMethod ?: 'GCash')));
        if (strtoupper($methodName) === 'GCASH') $methodName = 'GCash';
        if (strtoupper($methodName) === 'MAYA') $methodName = 'Maya';

        $maskedDest = $refundTx->destination_account_masked ?: '09*********';
        $refNumber = $refundTx->transfer_reference ?: 'N/A';
        $refundDate = $refundTx->processed_at
            ? ($refundTx->processed_at instanceof \Carbon\Carbon ? $refundTx->processed_at->format('M d, Y h:i A') : \Carbon\Carbon::parse($refundTx->processed_at)->format('M d, Y h:i A'))
            : now()->format('M d, Y h:i A');

        $lines = [
            "Hello! This is LumBarong.",
            "",
            "The refund for your cancelled Order {$shortOrderNumber} has been processed successfully.",
            "",
            "Refund Amount: {$formattedRefundAmount}",
            "Refund Method: {$methodName}",
            "Refund Destination: {$maskedDest}",
            "Refund Reference: {$refNumber}",
            "Refund Date: {$refundDate}",
            "",
        ];

        if (!empty($refundTx->transfer_proof_path)) {
            $proofUrl = route('orders.refund-proof', ['orderId' => $order->id, 'refundId' => $refundTx->id]);
            $lines[] = "The refund proof/receipt is attached to this message for your reference.";
            $lines[] = "";
            $lines[] = "[View / Download Refund Receipt]({$proofUrl})";
            $lines[] = "[![Refund Proof Receipt]({$proofUrl})]({$proofUrl})";
            $lines[] = "";
        }

        $lines[] = "Thank you for shopping with LumBarong!";
        $lines[] = "";
        $lines[] = $idempotencyTag;

        $content = implode("\n", $lines);

        try {
            return Message::create([
                'id'         => (string) Str::uuid(),
                'senderId'   => $systemUser->id,
                'receiverId' => $order->customerId,
                'content'    => $content,
                'read'       => false,
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to send LumBarong cancellation refund inbox message: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Send official LumBarong inbox message when a seller completes a cash refund.
     */
    public static function sendCashRefundCompletedMessage(
        Order $order,
        RefundTransaction $refundTx,
        ?User $seller = null,
        ?string $reason = null
    ): ?Message {
        if (empty($order->customerId)) {
            return null;
        }

        $systemUser = static::getSystemUser();
        $idempotencyTag = "<!-- [cash_refund:{$refundTx->id}] -->";

        // Idempotency check: prevent duplicate messages for the same cash refund
        $existing = Message::where('receiverId', $order->customerId)
            ->where('content', 'LIKE', "%{$idempotencyTag}%")
            ->first();

        if ($existing) {
            return $existing;
        }

        $shortOrderNumber = '#LB-' . strtoupper(substr($order->id, -8));
        $formattedRefundAmount = '₱' . number_format((float) $refundTx->refund_amount, 2);
        $refundDate = $refundTx->processed_at
            ? ($refundTx->processed_at instanceof \Carbon\Carbon ? $refundTx->processed_at->format('M d, Y h:i A') : \Carbon\Carbon::parse($refundTx->processed_at)->format('M d, Y h:i A'))
            : now()->format('M d, Y h:i A');

        $sellerName = $seller?->name ?: ($order->seller?->name ?: 'Artisan / Seller');
        $displayReason = trim((string) ($reason ?: ($refundTx->notes ?: 'Cash refund completed directly with customer')));

        $lines = [
            "Hello! This is LumBarong.",
            "",
            "The artisan/seller ({$sellerName}) has recorded a completed cash refund for Order {$shortOrderNumber}.",
            "",
            "Cash Refund Details:",
            "Order Number: {$shortOrderNumber}",
            "Refund Amount: {$formattedRefundAmount}",
            "Refund Method: Cash (Pay at Store / Seller Cash Settlement)",
            "Refund Date: {$refundDate}",
            "Reason: {$displayReason}",
            "Handled By: {$sellerName}",
            "",
        ];

        if (!empty($refundTx->transfer_proof_path)) {
            $proofUrl = route('orders.refund-proof', ['orderId' => $order->id, 'refundId' => $refundTx->id]);
            $lines[] = "The cash refund receipt or acknowledgment is attached to this message:";
            $lines[] = "";
            $lines[] = "[View / Download Refund Receipt]({$proofUrl})";
            $lines[] = "[![Refund Proof Receipt]({$proofUrl})]({$proofUrl})";
            $lines[] = "";
        }

        $lines[] = "Please review your order history if you need further details.";
        $lines[] = "";
        $lines[] = "Thank you for shopping with LumBarong!";
        $lines[] = "";
        $lines[] = $idempotencyTag;

        $content = implode("\n", $lines);

        try {
            return Message::create([
                'id'         => (string) Str::uuid(),
                'senderId'   => $systemUser->id,
                'receiverId' => $order->customerId,
                'content'    => $content,
                'read'       => false,
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to send LumBarong cash refund inbox message: " . $e->getMessage());
            return null;
        }
    }
}

