<?php

namespace App\Services\Financial;

use App\Models\CommissionRecord;
use App\Models\Notification;
use App\Models\Order;
use App\Models\RefundTransaction;
use App\Models\ReturnRequest;
use App\Models\SellerPayout;
use App\Models\SystemSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FinancialLedgerService
{
    /**
     * Get the authoritative platform commission rate configured in system settings.
     */
    public static function getCommissionRate(): float
    {
        $setting = SystemSetting::where('key', 'commission_rate')->value('value');
        if ($setting !== null && is_numeric($setting)) {
            $val = (float) $setting;
            if ($val >= 0 && $val <= 100 && is_finite($val)) {
                return round($val, 2);
            }
        }
        return 5.00;
    }

    /**
     * Check if payment method is platform-held online e-wallet (GCash / Maya).
     */
    public static function isOnlinePaymentMethod(?string $paymentMethod): bool
    {
        $method = strtoupper(trim((string) $paymentMethod));
        return in_array($method, ['GCASH', 'MAYA', 'PAYMAYA', 'CARD', 'CREDIT_CARD', 'DEBIT_CARD'], true);
    }

    /**
     * Check if payment method is cash-based (COD, Pay in Shop / Store Pickup cash, Special Delivery cash).
     */
    public static function isCashPaymentMethod(?string $paymentMethod): bool
    {
        $method = strtoupper(trim((string) $paymentMethod));
        return empty($method) || in_array($method, ['COD', 'CASH', 'CASH ON DELIVERY', 'PAY ON CLAIM', 'PAY IN SHOP', 'STORE_PICKUP_CASH'], true);
    }

    /**
     * Calculate authoritative commissionable sales amount for an order attributable to a seller.
     * Rule: Platform commission is calculated ONLY on eligible cash transactions, NOT on online GCash/Maya payments.
     * Commissionable sales strictly exclude shipping fees, taxes, and customer overpayment/sukli.
     */
    public static function calculateCommissionableSales(Order $order, ?string $sellerId = null): float
    {
        $targetSellerId = $sellerId ?: $order->sellerId;

        // Platform-held online payments (prepaid through LumBarong official platform QR) do NOT incur platform commission under current policy.
        if ($order->isPlatformHeldPayment()) {
            return 0.00;
        }

        // Cancelled orders have no commissionable sales
        $orderStatus = strtolower(trim((string) $order->status));
        if (in_array($orderStatus, ['cancelled', 'cancellation pending', 'cancellation requested'], true)) {
            return 0.00;
        }

        // Unpaid direct-settlement or COD orders do not automatically accrue commission
        if ($order->totalPaidAmount() <= 0.0) {
            return 0.00;
        }

        // Calculate product revenue attributable to this seller from order items
        $items = $order->relationLoaded('items') ? $order->items : $order->items()->with('product')->get();

        // Commissionable sales strictly exclude refunds and cannot exceed net retained funds
        $netPaid = max(0.00, round($order->totalPaidAmount() - $order->totalRefundedAmount(), 2));

        if ($items->isNotEmpty()) {
            $sellerItems = $items->filter(function ($item) use ($targetSellerId, $order) {
                $itemSellerId = $item->seller_id ?? ($item->product?->sellerId ?? ($item->product?->userId ?? $order->sellerId));
                return (string) $itemSellerId === (string) $targetSellerId;
            });

            if ($sellerItems->isNotEmpty()) {
                $productRevenue = (float) $sellerItems->sum(function ($item) {
                    return (float) $item->price * (int) $item->quantity;
                });

                // Calculate completed refunds attributable specifically to this seller
                $refunds = $order->relationLoaded('refundTransactions')
                    ? $order->refundTransactions
                    : $order->refundTransactions()->with('returnRequest.orderItem.product')->get();

                $completedRefunds = $refunds->whereIn('status', ['transferred', 'completed', 'refunded']);
                $sellerRefunded = 0.0;

                $allSellerIds = $items->map(fn($i) => (string) ($i->seller_id ?? ($i->product?->sellerId ?? ($i->product?->userId ?? $order->sellerId))))->unique()->values();
                $isMultiSeller = $allSellerIds->count() > 1;

                foreach ($completedRefunds as $ref) {
                    $refSellerId = null;
                    if ($ref->returnRequest) {
                        $refSellerId = $ref->returnRequest->seller_id
                            ?? ($ref->returnRequest->orderItem?->seller_id
                            ?? ($ref->returnRequest->orderItem?->product?->sellerId ?? ($ref->returnRequest->orderItem?->product?->userId ?? null)));
                    } elseif ($ref->processed_by) {
                        $refSellerId = $ref->processed_by;
                    }

                    if ($refSellerId !== null) {
                        if ((string) $refSellerId === (string) $targetSellerId) {
                            $sellerRefunded += (float) $ref->refund_amount;
                        }
                    } else {
                        if (!$isMultiSeller && (string) ($allSellerIds->first() ?? $order->sellerId) === (string) $targetSellerId) {
                            $sellerRefunded += (float) $ref->refund_amount;
                        } elseif ($isMultiSeller && $allSellerIds->contains((string) $targetSellerId)) {
                            $totalProductRev = (float) $items->sum(fn($i) => (float) $i->price * (int) $i->quantity);
                            if ($totalProductRev > 0) {
                                $sellerRefunded += round((float) $ref->refund_amount * ($productRevenue / $totalProductRev), 2);
                            }
                        }
                    }
                }

                $sellerNetRetained = max(0.00, round($productRevenue - $sellerRefunded, 2));
                return round(min($sellerNetRetained, $netPaid), 2);
            }
        }

        // Fallback: order totalAmount minus shipping fee and refunds
        $shippingFee = (float) ($order->shipping_fee ?: ($order->shipping?->shipping_fee ?: 0));
        $net = (float) $order->totalAmount - $shippingFee;

        return round(max(0.00, min($net, $netPaid)), 2);
    }

    /**
     * Calculate authoritative seller settlement breakdown for an order.
     * Excludes customer overpayment/sukli. Traceable per seller.
     */
    public static function calculateSellerSettlementBreakdown(Order $order, ?string $sellerId = null): array
    {
        $targetSellerId = $sellerId ?: $order->sellerId;
        $isOnline = static::isOnlinePaymentMethod($order->paymentMethod);

        $items = $order->relationLoaded('items') ? $order->items : $order->items()->with('product')->get();
        $sellerItems = $items->filter(function ($item) use ($targetSellerId, $order) {
            $itemSellerId = $item->seller_id ?? ($item->product?->sellerId ?? ($item->product?->userId ?? $order->sellerId));
            return (string) $itemSellerId === (string) $targetSellerId;
        });

        $productGross = $sellerItems->isNotEmpty()
            ? (float) $sellerItems->sum(fn($i) => (float) $i->price * (int) $i->quantity)
            : max(0.00, (float) $order->totalAmount - (float) ($order->shipping_fee ?? 0));

        $shippingAmount = (float) ($order->shipping_fee ?: ($order->shipping?->shipping_fee ?: max(0.00, (float) $order->totalAmount - $productGross)));
        $discountAmount = 0.00;
        
        // Under current policy: 0% platform commission on online payments (GCash / Maya).
        // Cash sales: configured commission rate applied to productGross.
        $commissionRate = ($order->commission_rate !== null && is_numeric($order->commission_rate))
            ? (float) $order->commission_rate
            : static::getCommissionRate();
        $commissionDeducted = $isOnline ? 0.00 : round($productGross * ($commissionRate / 100), 2);

        // Net settlement calculation: product proceeds + shipping minus discounts and commission (excluding sukli)
        $netSettlement = $isOnline
            ? round($productGross + $shippingAmount - $discountAmount, 2)
            : round($productGross - $commissionDeducted, 2);

        // Check payout eligibility
        $isPaymentVerified = in_array(strtolower(trim((string) $order->paymentStatus)), ['paid', 'verified', 'paid (verified)', 'confirmed'], true)
            || ($order->latestPaymentTransaction && $order->latestPaymentTransaction->status === 'VERIFIED');

        $isOrderFulfilled = $order->isCompleted();

        // Check if there are active unresolved return/dispute requests
        $hasActiveDispute = ReturnRequest::where('orderId', $order->id)
            ->where(function ($rq) use ($targetSellerId) {
                $rq->where('seller_id', $targetSellerId)
                   ->orWhereNull('seller_id');
            })
            ->whereIn('return_status', ['pending', 'requested', 'in_review', 'under_review', 'disputed', 'escalated', 'approved'])
            ->exists();

        // Check completed refunds attributable to this seller
        $refunds = $order->relationLoaded('refundTransactions')
            ? $order->refundTransactions
            : $order->refundTransactions()->with('returnRequest.orderItem.product')->get();

        $completedRefunds = $refunds->whereIn('status', ['transferred', 'completed', 'refunded']);
        $sellerRefunded = 0.0;
        $allSellerIds = $items->map(fn($i) => (string) ($i->seller_id ?? ($i->product?->sellerId ?? ($i->product?->userId ?? $order->sellerId))))->unique()->values();
        $isMultiSeller = $allSellerIds->count() > 1;

        foreach ($completedRefunds as $ref) {
            $refSellerId = null;
            if ($ref->returnRequest) {
                $refSellerId = $ref->returnRequest->seller_id
                    ?? ($ref->returnRequest->orderItem?->seller_id
                    ?? ($ref->returnRequest->orderItem?->product?->sellerId ?? ($ref->returnRequest->orderItem?->product?->userId ?? null)));
            } elseif ($ref->processed_by) {
                $refSellerId = $ref->processed_by;
            }

            if ($refSellerId !== null) {
                if ((string) $refSellerId === (string) $targetSellerId) {
                    $sellerRefunded += (float) $ref->refund_amount;
                }
            } else {
                if (!$isMultiSeller && (string) ($allSellerIds->first() ?? $order->sellerId) === (string) $targetSellerId) {
                    $sellerRefunded += (float) $ref->refund_amount;
                } elseif ($isMultiSeller && $allSellerIds->contains((string) $targetSellerId)) {
                    $totalProductRev = (float) $items->sum(fn($i) => (float) $i->price * (int) $i->quantity);
                    if ($totalProductRev > 0) {
                        $sellerRefunded += round((float) $ref->refund_amount * ($productGross / $totalProductRev), 2);
                    }
                }
            }
        }

        $hasRefundDisbursed = $sellerRefunded >= $netSettlement && $netSettlement > 0;

        $isEligible = $isOnline && $isPaymentVerified && $isOrderFulfilled && !$hasActiveDispute && !$hasRefundDisbursed;

        $status = 'PENDING_ELIGIBILITY';
        if ($hasActiveDispute) {
            $status = 'ON_HOLD';
        } elseif ($isEligible) {
            $status = 'AVAILABLE_FOR_PAYOUT';
        }

        return [
            'seller_id'             => $targetSellerId,
            'order_id'              => $order->id,
            'payment_method'        => $order->paymentMethod ?: 'COD',
            'is_online_payment'     => $isOnline,
            'commission_rate'       => $commissionRate,
            'gross_sales'           => round($productGross, 2),
            'shipping_amount'       => round($shippingAmount, 2),
            'discount_amount'       => round($discountAmount, 2),
            'commission_deducted'   => round($commissionDeducted, 2),
            'net_settlement_amount' => round($netSettlement, 2),
            'status'                => $status,
            'is_eligible'           => $isEligible,
            'is_payment_verified'   => $isPaymentVerified,
            'is_order_fulfilled'    => $isOrderFulfilled,
            'has_active_dispute'    => $hasActiveDispute,
        ];
    }

    /**
     * Synchronize and reconcile the SellerPayout record for an online order.
     */
    public static function reconcileSellerSettlementForOrder(Order $order): ?SellerPayout
    {
        if (!static::isOnlinePaymentMethod($order->paymentMethod)) {
            return null;
        }

        $orderStatus = strtolower(trim((string) $order->status));
        if (in_array($orderStatus, ['cancelled', 'cancellation pending', 'cancellation requested'], true)) {
            $existing = SellerPayout::where('order_id', $order->id)->first();
            if ($existing) {
                if ($existing->status !== 'PAID') {
                    $existing->update(['status' => 'ON_HOLD', 'hold_reason' => 'Order cancelled']);
                }
                return $existing;
            }
            return SellerPayout::create([
                'id'                    => (string) Str::uuid(),
                'order_id'              => $order->id,
                'seller_id'             => $order->sellerId,
                'gross_sales'           => (float) $order->totalAmount,
                'shipping_amount'       => 0.00,
                'discount_amount'       => 0.00,
                'commission_deducted'   => 0.00,
                'net_settlement_amount' => (float) $order->totalAmount,
                'status'                => 'ON_HOLD',
                'hold_reason'           => 'Order cancelled',
            ]);
        }

        $breakdown = static::calculateSellerSettlementBreakdown($order);

        // Fetch seller payout credentials
        $seller = User::find($breakdown['seller_id']);
        $payoutMethod = strtoupper(trim((string) $order->paymentMethod)) === 'MAYA' ? 'Maya' : 'GCash';
        $destinationAccount = $payoutMethod === 'Maya'
            ? ($seller?->mayaNumber ?: $seller?->phone)
            : ($seller?->gcashNumber ?: $seller?->phone);
        $destinationName = $seller?->shopName ?: ($seller?->name ?: 'Seller');

        $existing = SellerPayout::where('order_id', $order->id)
            ->where('seller_id', $breakdown['seller_id'])
            ->first();

        if ($existing) {
            // Do not alter already PAID payouts
            if ($existing->status === 'PAID') {
                return $existing;
            }

            $newStatus = $breakdown['status'];
            if ($existing->status === 'PAYOUT_PROCESSING' && $newStatus === 'AVAILABLE_FOR_PAYOUT') {
                $newStatus = 'PAYOUT_PROCESSING';
            }

            $existing->update([
                'gross_sales'               => $breakdown['gross_sales'],
                'shipping_amount'           => $breakdown['shipping_amount'],
                'discount_amount'           => $breakdown['discount_amount'],
                'commission_deducted'       => $breakdown['commission_deducted'],
                'commission_rate'           => $breakdown['commission_rate'],
                'net_settlement_amount'     => $breakdown['net_settlement_amount'],
                'status'                    => $newStatus,
                'payout_method'             => $existing->payout_method ?: $payoutMethod,
                'payout_destination_account'=> $existing->payout_destination_account ?: $destinationAccount,
                'payout_destination_name'   => $existing->payout_destination_name ?: $destinationName,
                'hold_reason'               => $breakdown['has_active_dispute'] ? 'Active return or refund dispute in review' : null,
            ]);

            return $existing;
        }

        return SellerPayout::create([
            'id'                        => (string) Str::uuid(),
            'order_id'                  => $order->id,
            'seller_id'                 => $breakdown['seller_id'],
            'gross_sales'               => $breakdown['gross_sales'],
            'shipping_amount'           => $breakdown['shipping_amount'],
            'discount_amount'           => $breakdown['discount_amount'],
            'commission_deducted'       => $breakdown['commission_deducted'],
            'commission_rate'           => $breakdown['commission_rate'],
            'net_settlement_amount'     => $breakdown['net_settlement_amount'],
            'status'                    => $breakdown['status'],
            'payout_method'             => $payoutMethod,
            'payout_destination_account'=> $destinationAccount,
            'payout_destination_name'   => $destinationName,
            'hold_reason'               => $breakdown['has_active_dispute'] ? 'Active return or refund dispute in review' : null,
        ]);
    }

    /**
     * Record a manual seller payout transfer performed by Admin or Super Admin.
     */
    public static function processManualSellerPayout(
        SellerPayout $payout,
        array|User $dataOrAdmin,
        User|string|null $adminOrRef = null,
        ?UploadedFile $proofFile = null,
        ?string $adminNotes = null
    ): SellerPayout {
        if ($dataOrAdmin instanceof User) {
            $admin = $dataOrAdmin;
            $transferReference = (string) $adminOrRef;
        } else {
            $admin = $adminOrRef instanceof User ? $adminOrRef : Auth::user();
            $transferReference = $dataOrAdmin['transaction_reference'] ?? ($dataOrAdmin['transfer_reference'] ?? ($dataOrAdmin['reference_number'] ?? ''));
            $proofFile = $proofFile ?: ($dataOrAdmin['transfer_proof'] ?? null);
            $adminNotes = $adminNotes ?: ($dataOrAdmin['notes'] ?? ($dataOrAdmin['admin_notes'] ?? null));
            if (!empty($dataOrAdmin['payout_method'])) {
                $payout->payout_method = $dataOrAdmin['payout_method'];
            }
            if (!empty($dataOrAdmin['payout_destination'])) {
                $payout->payout_destination_account = $dataOrAdmin['payout_destination'];
            }
        }

        if (!$admin || !in_array($admin->role, ['admin', 'superadmin'], true)) {
            throw ValidationException::withMessages([
                'admin' => ['Unauthorized. Only Platform Administrators can record seller payouts.'],
            ]);
        }

        return DB::transaction(function () use ($payout, $admin, $transferReference, $proofFile, $adminNotes) {
            $locked = SellerPayout::where('id', $payout->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === 'PAID') {
                throw ValidationException::withMessages([
                    'payout' => ['This settlement payout has already been marked as PAID.'],
                ]);
            }

            if ($locked->status === 'ON_HOLD') {
                throw ValidationException::withMessages([
                    'payout' => ['This settlement is currently ON HOLD and cannot be disbursed until resolved.'],
                ]);
            }

            $cleanRef = trim($transferReference);
            if (empty($cleanRef)) {
                throw ValidationException::withMessages([
                    'transfer_reference' => ['Transfer reference number is required.'],
                ]);
            }

            // Check duplicate reference idempotency
            $duplicateRef = SellerPayout::where('transfer_reference', $cleanRef)
                ->where('id', '!=', $locked->id)
                ->where('status', 'PAID')
                ->exists();

            if ($duplicateRef) {
                throw ValidationException::withMessages([
                    'transfer_reference' => ['This transfer reference number has already been recorded for another payout.'],
                ]);
            }

            $proofPath = $locked->transfer_proof_path;
            if ($proofFile && $proofFile->isValid()) {
                $proofPath = $proofFile->store('payout_proofs', 'public');
            }

            $locked->update([
                'status'                     => 'PAID',
                'payout_method'              => $payout->payout_method ?: $locked->payout_method,
                'payout_destination_account' => $payout->payout_destination_account ?: $locked->payout_destination_account,
                'transfer_reference'         => $cleanRef,
                'transfer_proof_path'        => $proofPath,
                'admin_notes'                => $adminNotes,
                'processed_by'               => $admin->id,
                'processed_at'               => now(),
                'paid_at'                    => now(),
            ]);

            // Notify Seller
            try {
                Notification::create([
                    'userId'     => $locked->seller_id,
                    'title'      => '💰 Settlement Payout Transferred',
                    'message'    => "Your payout of ₱" . number_format($locked->net_settlement_amount, 2) . " has been transferred via " . ($locked->payout_method ?: 'GCash') . " (Ref: {$cleanRef}).",
                    'type'       => 'payout',
                    'link'       => '/seller/commission',
                    'targetRole' => 'seller',
                    'isRead'     => false,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Could not create notification for payout: ' . $e->getMessage());
            }

            return $locked;
        });
    }

    /**
     * Compute comprehensive financial metrics for a seller.
     */
    public static function getSellerFinancialSummary(User|string $sellerOrId, ?string $period = null): array
    {
        $sellerId = $sellerOrId instanceof User ? $sellerOrId->id : $sellerOrId;
        $seller = $sellerOrId instanceof User ? $sellerOrId : User::find($sellerId);
        $period = $period ?: Carbon::now()->format('Y-m');
        $rate = static::getCommissionRate();

        [$year, $month] = explode('-', $period);

        // Fetch non-cancelled orders for this seller
        $orders = Order::where('sellerId', $sellerId)
            ->whereNotIn('status', ['Cancelled', 'cancellation pending', 'cancellation requested'])
            ->with(['items.product', 'shipping', 'paymentTransactions', 'refundTransactions', 'sellerPayout'])
            ->get();

        $allTimeOrders = $orders;
        $periodOrders = $orders->filter(function ($o) use ($year, $month) {
            $created = Carbon::parse($o->createdAt);
            return $created->year == (int) $year && $created->month == (int) $month;
        });

        // 1. Sales categorization (Seller-collected direct settlements vs platform-held online prepayments)
        $cashOrders = $periodOrders->filter(fn(Order $o) => $o->isSellerHeldPayment());
        $onlineOrders = $periodOrders->filter(fn(Order $o) => $o->isPlatformHeldPayment());

        $periodCashProductSales = (float) $cashOrders->sum(fn(Order $o) => static::calculateCommissionableSales($o, $sellerId));
        $periodOnlineGrossSales = (float) $onlineOrders->sum(fn(Order $o) => (float) $o->totalAmount - (float) ($o->overpayment_amount ?? 0));
        $periodTotalGrossSales  = $periodCashProductSales + $periodOnlineGrossSales;

        // 2. Commission calculation (ONLY on eligible cash sales)
        // 3. Commission records and arrears
        $commissionRecords = CommissionRecord::where('sellerId', $sellerId)
            ->orderByDesc('period')
            ->get();

        $currentCommissionRecord = $commissionRecords->firstWhere('period', $period);

        // Historical preservation: If a commission record already exists for this period, retain its historically recorded rate and amount!
        if ($currentCommissionRecord && (float) $currentCommissionRecord->commissionRate > 0) {
            $effectiveRate = (float) $currentCommissionRecord->commissionRate;
            $commissionDueThisPeriod = (float) $currentCommissionRecord->commissionAmount;
        } else {
            $effectiveRate = $rate;
            $commissionDueThisPeriod = round($periodCashProductSales * ($rate / 100), 2);
        }

        $totalCommissionPaid = (float) $commissionRecords->where('status', 'paid')->sum('commissionAmount');
        $unpaidCommissionRecords = $commissionRecords->whereIn('status', ['unpaid', 'verification_pending']);
        $totalCommissionOutstanding = (float) $unpaidCommissionRecords->sum('commissionAmount');

        if (!$currentCommissionRecord && $commissionDueThisPeriod > 0) {
            $totalCommissionOutstanding += $commissionDueThisPeriod;
        }

        // 4. Online settlements & payouts
        $sellerPayouts = SellerPayout::where('seller_id', $sellerId)
            ->with('order')
            ->orderByDesc('created_at')
            ->get();

        $availableSettlements = (float) $sellerPayouts->where('status', 'AVAILABLE_FOR_PAYOUT')->sum('net_settlement_amount');
        $pendingSettlements   = (float) $sellerPayouts->where('status', 'PENDING_ELIGIBILITY')->sum('net_settlement_amount');
        $processingPayouts    = (float) $sellerPayouts->where('status', 'PAYOUT_PROCESSING')->sum('net_settlement_amount');
        $completedPayouts     = (float) $sellerPayouts->where('status', 'PAID')->sum('net_settlement_amount');
        $onHoldSettlements    = (float) $sellerPayouts->where('status', 'ON_HOLD')->sum('net_settlement_amount');

        return [
            'seller'                      => $seller,
            'period'                      => $period,
            'commission_rate'             => $effectiveRate,
            'commissionRate'              => $effectiveRate,
            'gross_sales'                 => $periodTotalGrossSales,
            'cash_product_sales'          => $periodCashProductSales,
            'commission_due'              => $commissionDueThisPeriod,
            'periodCashProductSales'      => $periodCashProductSales,
            'periodOnlineGrossSales'      => $periodOnlineGrossSales,
            'periodTotalGrossSales'       => $periodTotalGrossSales,
            'commissionDueThisPeriod'     => $commissionDueThisPeriod,
            'current_commission_record'   => $currentCommissionRecord,
            'currentCommissionRecord'     => $currentCommissionRecord,
            'past_commission_records'     => $commissionRecords,
            'commissionRecords'           => $commissionRecords,
            'totalCommissionPaid'         => $totalCommissionPaid,
            'totalCommissionOutstanding'  => $totalCommissionOutstanding,
            'online_available_for_payout' => $availableSettlements,
            'availableSettlements'        => $availableSettlements,
            'online_pending_settlement'   => $pendingSettlements,
            'pendingSettlements'          => $pendingSettlements,
            'online_payout_processing'    => $processingPayouts,
            'processingPayouts'           => $processingPayouts,
            'online_paid_payouts'         => $completedPayouts,
            'completedPayouts'            => $completedPayouts,
            'onHoldSettlements'           => $onHoldSettlements,
            'payouts'                     => $sellerPayouts,
            'sellerPayouts'               => $sellerPayouts,
            'recentOrders'                => $periodOrders->values(),
        ];
    }
}
