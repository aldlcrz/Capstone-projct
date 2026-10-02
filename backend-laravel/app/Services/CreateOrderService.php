<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderIdempotencyRecord;
use App\Models\OrderItem;
use App\Models\OrderShipping;
use App\Models\OrderStatusHistory;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ShippingProvider;
use App\Models\SystemSetting;
use App\Models\User;
use App\Support\VariationFormatter;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateOrderService
{
    protected ShippingCalculatorService $shippingCalculator;

    public function __construct(ShippingCalculatorService $shippingCalculator)
    {
        $this->shippingCalculator = $shippingCalculator;
    }

    /**
     * Create an order through the canonical transactional pipeline.
     *
     * @param array $params
     * @return Order
     * @throws \Throwable
     */
    public function createOrder(array $params): Order
    {
        // 1. Maintenance Mode Guard
        $maintenance = SystemSetting::where('key', 'maintenanceMode')->first();
        $customer = $params['customer'] ?? null;
        if (!$customer && !empty($params['customerId'])) {
            $customer = User::find($params['customerId']);
        }
        if (!$customer) {
            throw new DomainException('Authenticated customer is required to place an order.');
        }

        if ($maintenance && ($maintenance->value === 'true' || $maintenance->value === true) && $customer->role !== 'admin') {
            throw new DomainException('Transactions are temporarily paused for maintenance. Please try again later.');
        }

        // Administrators cannot place orders
        if (in_array($customer->role, ['admin', 'superadmin'], true)) {
            throw new DomainException('Administrators cannot place orders.');
        }

        // 2. Resolve and Normalize Cart Items
        $rawItems = $params['items'] ?? [];
        if (empty($rawItems) || !is_array($rawItems)) {
            throw new DomainException('Cart is empty.');
        }

        // 3. Resolve Delivery Address
        $addressData = null;
        if (!empty($params['address_id'])) {
            $addrRecord = Address::where('id', $params['address_id'])
                ->where('userId', $customer->id)
                ->first();
            if (!$addrRecord) {
                throw new DomainException('Selected delivery address not found.');
            }
            $addressData = $addrRecord->toArray();
        } elseif (!empty($params['shippingAddress'])) {
            if (is_array($params['shippingAddress'])) {
                $addressData = $params['shippingAddress'];
            } elseif (is_string($params['shippingAddress'])) {
                $decoded = json_decode($params['shippingAddress'], true);
                if (is_array($decoded)) {
                    $addressData = $decoded;
                }
            }
        }

        if (!$addressData) {
            throw new DomainException('Please provide a valid delivery address.');
        }

        $paymentMethod = trim((string)($params['paymentMethod'] ?? 'GCash'));
        $isCod   = strcasecmp($paymentMethod, 'COD') === 0 || strcasecmp($paymentMethod, 'Cash on Delivery') === 0;
        $isGcash = strcasecmp($paymentMethod, 'GCash') === 0;
        $isMaya  = strcasecmp($paymentMethod, 'Maya') === 0;

        $quoteToken = $params['quoteToken'] ?? null;
        $selectedProviderId = $params['selectedProviderId'] ?? null;
        $visitorSessionId = $params['visitorSessionId'] ?? null;

        // 4. Compute Request Hash & Check Database-backed Idempotency
        $idempotencyKey = !empty($params['idempotencyKey']) ? trim((string)$params['idempotencyKey']) : null;
        $requestPayloadSummary = [
            'customer_id' => $customer->id,
            'items'       => array_map(function ($it) {
                return [
                    'id'        => (string)($it['productId'] ?? ($it['id'] ?? '')),
                    'quantity'  => (int)($it['quantity'] ?? 1),
                    'size'      => $it['size'] ?? null,
                    'variation' => $it['variation'] ?? null,
                ];
            }, $rawItems),
            'address'     => (string)($addressData['id'] ?? ($addressData['postalCode'] ?? ($addressData['city'] ?? ''))),
            'payment'     => $paymentMethod,
            'reference'   => (string)($params['paymentReference'] ?? ''),
        ];
        $requestHash = hash('sha256', json_encode($requestPayloadSummary));

        if ($idempotencyKey) {
            $existingRecord = OrderIdempotencyRecord::where('customer_id', $customer->id)
                ->where('idempotency_key', $idempotencyKey)
                ->where('created_at', '>=', now()->subHours(24))
                ->first();

            if ($existingRecord) {
                if ($existingRecord->status === 'completed' && $existingRecord->order_id) {
                    if ($existingRecord->request_hash === $requestHash) {
                        $existingOrder = Order::with(['seller', 'items.product', 'shipping', 'latestPaymentTransaction'])
                            ->find($existingRecord->order_id);
                        if ($existingOrder) {
                            return $existingOrder;
                        }
                    } else {
                        throw new DomainException('This idempotency key has already been used for a different checkout request.');
                    }
                } elseif ($existingRecord->status === 'processing') {
                    throw new DomainException('A checkout request with this idempotency key is already being processed.');
                }
            }
        }

        // Execute inside single DB Transaction with row-level locks
        return DB::transaction(fn () => $this->processOrderCreation(
            $customer,
            $rawItems,
            $addressData,
            $paymentMethod,
            $isCod,
            $isGcash,
            $isMaya,
            $quoteToken,
            $selectedProviderId,
            $visitorSessionId,
            $idempotencyKey,
            $requestHash,
            $params
        ));
    }

    /**
     * Internal atomic pipeline for order creation within the database transaction.
     */
    protected function processOrderCreation(
        User $customer,
        array $rawItems,
        array $addressData,
        string $paymentMethod,
        bool $isCod,
        bool $isGcash,
        bool $isMaya,
        ?string $quoteToken,
        ?string $selectedProviderId,
        ?string $visitorSessionId,
        ?string $idempotencyKey,
        string $requestHash,
        array $params
    ): Order {
        // Atomic DB Claim on idempotency record to prevent concurrent races
            if ($idempotencyKey) {
                $idempRecord = OrderIdempotencyRecord::lockForUpdate()
                    ->where('customer_id', $customer->id)
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();

                if ($idempRecord) {
                    if ($idempRecord->status === 'completed' && $idempRecord->order_id) {
                        if ($idempRecord->request_hash === $requestHash) {
                            $existingOrder = Order::with(['seller', 'items.product', 'shipping', 'latestPaymentTransaction'])
                                ->find($idempRecord->order_id);
                            if ($existingOrder) {
                                return $existingOrder;
                            }
                        }
                        throw new DomainException('This idempotency key has already been used for a different checkout request.');
                    }
                    if ($idempRecord->status === 'processing') {
                        throw new DomainException('A checkout request with this idempotency key is already being processed.');
                    }
                } else {
                    try {
                        OrderIdempotencyRecord::create([
                            'id'              => (string) Str::uuid(),
                            'customer_id'     => $customer->id,
                            'idempotency_key' => $idempotencyKey,
                            'request_hash'    => $requestHash,
                            'status'          => 'processing',
                        ]);
                    } catch (QueryException $e) {
                        $sqlState = (string) $e->getCode();
                        $errorCode = $e->errorInfo[1] ?? null;
                        $message = strtolower($e->getMessage());

                        // Check for unique key constraint violation (SQLSTATE 23000, MySQL 1062, SQLite 19/2067)
                        $isDuplicate = $sqlState === '23000'
                            || $errorCode === 1062
                            || $errorCode === 19
                            || str_contains($message, 'duplicate')
                            || str_contains($message, 'unique');

                        if ($isDuplicate) {
                            $existing = OrderIdempotencyRecord::where('customer_id', $customer->id)
                                ->where('idempotency_key', $idempotencyKey)
                                ->first();
                            if ($existing && $existing->status === 'completed' && $existing->order_id && $existing->request_hash === $requestHash) {
                                return Order::with(['seller', 'items.product', 'shipping', 'latestPaymentTransaction'])->find($existing->order_id);
                            }
                            throw new DomainException('A concurrent checkout request with this idempotency key is currently in progress.');
                        }

                        // Unrelated database error: preserve observability and rethrow original exception
                        throw $e;
                    }
                }
            }
            // Extract product IDs and sort to prevent database deadlocks
            $productRequests = [];
            foreach ($rawItems as $item) {
                $pId = $item['productId'] ?? ($item['id'] ?? null);
                if (!$pId) {
                    throw new DomainException('Invalid product specification in cart item.');
                }
                $qty = (int) ($item['quantity'] ?? 1);
                if ($qty <= 0) {
                    throw new DomainException('Quantity must be at least 1.');
                }
                $productRequests[] = [
                    'productId' => (string) $pId,
                    'quantity'  => $qty,
                    'size'      => $item['size'] ?? null,
                    'variation' => $item['variation'] ?? null,
                ];
            }

            $productIds = array_values(array_unique(array_column($productRequests, 'productId')));
            sort($productIds);

            // Row-level lock all target products
            $lockedProducts = Product::whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if (count($lockedProducts) !== count($productIds)) {
                throw new DomainException('One or more products in your cart could not be found.');
            }

            // Derive and validate Seller Ownership & Shop Status
            $sellerIds = [];
            foreach ($lockedProducts as $prod) {
                $sellerIds[] = $prod->sellerId;
            }
            $sellerIds = array_values(array_unique(array_filter($sellerIds)));

            if (count($sellerIds) > 1) {
                throw new DomainException('Cross-shop checkout in a single payment is not supported. Please checkout one artisan shop at a time.');
            }
            if (empty($sellerIds)) {
                throw new DomainException('No valid artisan seller found for the selected products.');
            }

            $sellerId = $sellerIds[0];
            $sellerUser = User::find($sellerId);
            if (!$sellerUser) {
                throw new DomainException('Artisan seller profile not found.');
            }

            // Guard: Seller cannot purchase own product
            if ($customer->role === 'seller' && $customer->id === $sellerId) {
                throw new DomainException('Sellers cannot purchase their own products.');
            }

            $shopName = $sellerUser->shopName ?: $sellerUser->name;
            if (in_array(strtolower($sellerUser->status ?? ''), ['blocked', 'suspended', 'banned'], true)) {
                throw new DomainException("The shop '{$shopName}' is currently suspended and cannot accept orders.");
            }
            if (strtolower($sellerUser->status ?? '') === 'frozen') {
                throw new DomainException("The shop '{$shopName}' is currently frozen due to overdue monthly commission and cannot process orders.");
            }

            // Validate Quote Token if supplied
            if ($quoteToken) {
                $cartForToken = array_map(function ($pr) use ($lockedProducts) {
                    $p = $lockedProducts[$pr['productId']];
                    return [
                        'id' => $p->id,
                        'sellerId' => $p->sellerId,
                        'quantity' => $pr['quantity'],
                        'price' => $p->sale_price,
                        'size' => $pr['size'],
                        'variation' => $pr['variation'],
                    ];
                }, $productRequests);

                $addrIdForToken = $addressData['id'] ?? null;
                if ($addrIdForToken && !$this->shippingCalculator->validateQuoteToken($quoteToken, [$sellerId], $addrIdForToken, $cartForToken)) {
                    throw new DomainException('Your shipping quote has expired or the order items changed. Please review and refresh your shipping quote.');
                }
            }

            // Validate COD locality
            if ($isCod) {
                if (!$this->shippingCalculator->isLocalCluster($sellerUser, $addressData)) {
                    throw ValidationException::withMessages([
                        'paymentMethod' => ['Cash on Delivery (COD) is available only for nearby local deliveries. Please choose GCash or Maya.'],
                    ]);
                }
            }

            // Validate Stock and Calculate Subtotal Authoritatively
            $calculatedSubtotal = 0.0;
            $preparedItems = [];
            $sellerItemsForShipping = [];

            foreach ($productRequests as $req) {
                /** @var Product $product */
                $product = $lockedProducts[$req['productId']];

                $prodStatus = strtolower($product->status ?? 'active');
                if (in_array($prodStatus, ['rejected', 'archived', 'blocked', 'inactive', 'suspended', 'pending'], true)) {
                    throw new DomainException("Product '{$product->name}' is currently not available for purchase.");
                }

                $qty = $req['quantity'];
                $requestedSize = $req['size'] ? trim($req['size']) : null;

                // Validate and update size stocks if present
                $sizeStocks = $product->size_stocks;
                if (!empty($sizeStocks) && is_array($sizeStocks) && $requestedSize) {
                    $availableSizeStock = $sizeStocks[$requestedSize] ?? null;
                    if ($availableSizeStock !== null && (int)$availableSizeStock < $qty) {
                        throw new DomainException("Insufficient stock for size '{$requestedSize}' of '{$product->name}'. Available: {$availableSizeStock}, requested: {$qty}.");
                    }
                    if ($availableSizeStock !== null) {
                        $sizeStocks[$requestedSize] = max(0, (int)$availableSizeStock - $qty);
                        $product->size_stocks = $sizeStocks;
                    }
                }

                // Validate total stock
                if ($product->stock < $qty) {
                    throw new DomainException("Insufficient stock for '{$product->name}'. Available: {$product->stock}, requested: {$qty}.");
                }

                // Authoritative Price Calculation
                $itemPrice = (float) $product->sale_price;
                $itemTotal = round($itemPrice * $qty, 2);
                $calculatedSubtotal = round($calculatedSubtotal + $itemTotal, 2);

                $variationLabel = VariationFormatter::label($req['variation'], $product->image) ?? $req['variation'];
                $productImg = VariationFormatter::getImageForVariation($variationLabel, $product) ?: $product->getImageUrl();

                $preparedItems[] = [
                    'product'       => $product,
                    'productId'     => $product->id,
                    'product_name'  => $product->name,
                    'product_image' => $productImg,
                    'quantity'      => $qty,
                    'price'         => $itemPrice,
                    'size'          => $requestedSize,
                    'variation'     => $variationLabel,
                ];

                $sellerItemsForShipping[] = [
                    'id'                  => $product->id,
                    'productId'           => $product->id,
                    'quantity'            => $qty,
                    'price'               => $itemPrice,
                    'weight'              => $product->weight ?? null,
                    'length'              => $product->length ?? null,
                    'width'               => $product->width ?? null,
                    'height'              => $product->height ?? null,
                    'package_weight_grams'=> $product->package_weight_grams ?? null,
                    'package_length_cm'   => $product->package_length_cm ?? null,
                    'package_width_cm'    => $product->package_width_cm ?? null,
                    'package_height_cm'   => $product->package_height_cm ?? null,
                ];
            }

            // Server-Side Authoritative Shipping Calculation
            $preferredProvider = $this->shippingCalculator->getSellerPreferredProvider($sellerUser);
            $providerId = $selectedProviderId ?: ($preferredProvider?->id);
            $quotes = $this->shippingCalculator->calculateQuotes($sellerUser, $addressData, $sellerItemsForShipping, $providerId);
            $chosenQuote = $quotes[0] ?? null;

            if (!$chosenQuote) {
                throw new DomainException('Shipping service is currently not available for this delivery route or package specifications.');
            }

            $shippingFee = round((float) $chosenQuote['shipping_fee'], 2);
            $totalExpectedAmount = round($calculatedSubtotal + $shippingFee, 2);

            // Process Payment Validation / Reference Claiming
            $paymentReference = null;
            $paymentProofPath = null;
            $initialPaymentStatus = $isCod ? 'Unpaid' : 'Pending';
            $transactionStatus = 'UNVERIFIED';
            $verificationTier = 'REVIEW';
            $screening = $params['screening'] ?? null;
            $receiptPath = $params['paymentProof'] ?? null;

            if (!$isCod) {
                if ($screening && (($screening['status'] ?? '') === 'REJECT' || ($screening['is_receipt'] ?? true) === false)) {
                    throw new DomainException($screening['message'] ?? 'Payment receipt verification failed. Please attach an authentic payment confirmation.');
                }

                if ($screening && isset($screening['detected_amount']) && is_numeric($screening['detected_amount'])) {
                    $detectedAmt = (float) $screening['detected_amount'];
                    if ($totalExpectedAmount > 0 && $detectedAmt < ($totalExpectedAmount * 0.90)) {
                        throw new DomainException("Amount mismatch: The receipt shows ₱" . number_format($detectedAmt, 2) . ", but the required order total is ₱" . number_format($totalExpectedAmount, 2) . ". Payment cannot be accepted.");
                    }
                }

                $rawSubmittedRef = trim((string)($params['paymentReference'] ?? ''));
                $cleanSubmittedRef = preg_replace('/\D/', '', $rawSubmittedRef);

                $detectedRef = !empty($screening['detected_ref']) ? preg_replace('/\D/', '', (string)$screening['detected_ref']) : null;
                $resolvedRef = $detectedRef ?: $cleanSubmittedRef;

                if (empty($resolvedRef)) {
                    throw new DomainException('Could not detect a valid transaction reference number from the uploaded payment receipt.');
                }

                if ($isGcash && strlen($resolvedRef) !== 13) {
                    throw new DomainException('GCash reference number extracted from receipt must be exactly 13 digits.');
                } elseif ($isMaya && strlen($resolvedRef) !== 12) {
                    throw new DomainException('Maya reference number extracted from receipt must be exactly 12 digits.');
                }

                if (preg_match('/^(\d)\1+$/', $resolvedRef)) {
                    throw new DomainException('Invalid payment reference number detected. Repeated digit sequences are not allowed.');
                }

                // Atomic uniqueness verification for active reference claims
                $isDuplicate = PaymentTransaction::where('active_reference', $resolvedRef)->exists();
                if (!$isDuplicate) {
                    $isDuplicate = Order::where('paymentReference', $resolvedRef)
                        ->whereNotIn('status', ['Cancelled', 'Declined'])
                        ->where('paymentStatus', '!=', 'Payment Rejected')
                        ->exists();
                }

                if ($isDuplicate) {
                    throw new DomainException('This payment reference number has already been used in another order. Please provide a new and unique payment receipt.');
                }

                $paymentReference = $resolvedRef;
                $paymentProofPath = $receiptPath;

                if (($screening['status'] ?? '') === 'PASS') {
                    $verificationTier = 'PASS';
                    // Note: even if AI passed, payment stays UNVERIFIED until order confirmation / seller verification
                    $transactionStatus = 'UNVERIFIED';
                    $initialPaymentStatus = 'Pending Verification';
                } elseif (($screening['status'] ?? '') === 'REVIEW') {
                    $verificationTier = 'REVIEW';
                    $transactionStatus = 'UNVERIFIED';
                    $initialPaymentStatus = 'Pending Verification';
                } else {
                    $verificationTier = 'REVIEW';
                    $transactionStatus = 'UNVERIFIED';
                    $initialPaymentStatus = 'Pending Verification';
                }
            }

            if ($isCod) {
                $paymentReference = null;
                $initialPaymentStatus = 'Pending Payment (COD)';
                $transactionStatus = 'UNVERIFIED';
                $verificationTier = 'COD';
            }

            // Create Order
            $orderAttributes = [
                'id'                     => (string) Str::uuid(),
                'customerId'             => $customer->id,
                'sellerId'               => $sellerId,
                'totalAmount'            => $totalExpectedAmount,
                'status'                 => 'Pending',
                'paymentMethod'          => $paymentMethod,
                'paymentReference'       => $paymentReference,
                'paymentProof'           => $paymentProofPath,
                'paymentStatus'          => $initialPaymentStatus,
                'shippingAddress'        => $addressData,
            ];

            if (Schema::hasColumn('orders', 'visitorSessionId')) {
                $orderAttributes['visitorSessionId'] = $visitorSessionId;
            }

            if (Schema::hasColumn('orders', 'paymentRejectionReason')) {
                $orderAttributes['paymentRejectionReason'] = null;
            }

            $order = Order::create($orderAttributes);

            // Create Order Shipping Snapshot (Immutable pricing data)
            $pricingProvider = ShippingProvider::find($chosenQuote['provider_id'] ?? null);
            OrderShipping::create([
                'order_id'                       => $order->id,
                'provider_id'                    => $chosenQuote['provider_id'] ?? null,
                'provider_name'                  => $chosenQuote['provider_name'] ?? 'Standard Delivery',
                'pricing_provider_id'            => $chosenQuote['provider_id'] ?? null,
                'pricing_provider_name'          => $chosenQuote['provider_name'] ?? 'Standard Delivery',
                'fulfillment_provider_id'        => $chosenQuote['provider_id'] ?? null,
                'fulfillment_provider_name'      => $chosenQuote['provider_name'] ?? 'Standard Delivery',
                'shipping_rate_id'               => $chosenQuote['shipping_rate_id'] ?? ($chosenQuote['rate_id'] ?? null),
                'origin_zone_id'                 => $chosenQuote['origin_zone_id'] ?? null,
                'origin_zone_name'               => $chosenQuote['origin_zone_name'] ?? 'Lumban Origin',
                'destination_zone_id'            => $chosenQuote['destination_zone_id'] ?? null,
                'destination_zone_name'          => $chosenQuote['destination_zone_name'] ?? 'Destination',
                'actual_weight'                  => $chosenQuote['actual_weight'] ?? 0.5,
                'volumetric_weight'              => $chosenQuote['volumetric_weight'] ?? 0.5,
                'chargeable_weight'              => $chosenQuote['chargeable_weight'] ?? 0.5,
                'rate_base_snapshot'             => $chosenQuote['rate_base_snapshot'] ?? $shippingFee,
                'additional_weight_rate_snapshot'=> $chosenQuote['additional_weight_rate_snapshot'] ?? 0.0,
                'volumetric_divisor_snapshot'    => $chosenQuote['volumetric_divisor_snapshot'] ?? 3500,
                'shipping_fee'                   => $shippingFee,
                'estimated_days_min'             => $chosenQuote['estimated_days_min'] ?? 2,
                'estimated_days_max'             => $chosenQuote['estimated_days_max'] ?? 4,
                'shipping_status'                => 'Pending',
            ]);

            // Create Order Status History
            OrderStatusHistory::create([
                'orderId'        => $order->id,
                'previousStatus' => null,
                'newStatus'      => 'Pending',
                'updatedBy'      => $customer->id,
                'userRole'       => 'customer',
                'notes'          => 'Order placed by customer via secure checkout.',
            ]);

            // Create Order Items and Deduct Stock
            foreach ($preparedItems as $pItem) {
                /** @var Product $prod */
                $prod = $pItem['product'];
                $prod->decrement('stock', $pItem['quantity']);
                $prod->save();

                OrderItem::create([
                    'orderId'       => $order->id,
                    'productId'     => $pItem['productId'],
                    'product_name'  => $pItem['product_name'],
                    'product_image' => $pItem['product_image'],
                    'quantity'      => $pItem['quantity'],
                    'price'         => $pItem['price'],
                    'size'          => $pItem['size'],
                    'variation'     => $pItem['variation'],
                ]);
            }

            // Create Payment Transaction & Claim active reference atomically (for digital payments)
            if ($paymentReference && !$isCod) {
                $notes = 'Receipt uploaded at checkout.';
                if ($idempotencyKey) {
                    $notes .= " [idempotency:{$idempotencyKey}]";
                }

                PaymentTransaction::create([
                    'order_id'             => $order->id,
                    'customer_id'          => $customer->id,
                    'seller_id'            => $sellerId,
                    'reference_number'     => $paymentReference,
                    'active_reference'     => $paymentReference,
                    'wallet_type'          => $isMaya ? 'Maya' : 'GCash',
                    'expected_amount'      => $totalExpectedAmount,
                    'detected_amount'      => $screening['detected_amount'] ?? null,
                    'amount_confidence'    => $screening['amount_confidence'] ?? null,
                    'reference_confidence' => $screening['reference_confidence'] ?? null,
                    'confidence'           => $screening['confidence'] ?? null,
                    'status'               => $transactionStatus,
                    'verification_tier'    => $verificationTier,
                    'receipt_path'         => $paymentProofPath,
                    'verified_at'          => null,
                    'notes'                => $notes,
                ]);
            }

            // 9. Update Idempotency Record to completed atomically
            if ($idempotencyKey) {
                OrderIdempotencyRecord::where('customer_id', $customer->id)
                    ->where('idempotency_key', $idempotencyKey)
                    ->update([
                        'order_id' => $order->id,
                        'status'   => 'completed',
                    ]);
            }

            // Dispatch post-transaction side effects after commit
            DB::afterCommit(function () use ($order, $customer, $sellerUser, $preparedItems, $totalExpectedAmount) {
                $this->dispatchPostOrderNotifications($order, $customer, $sellerUser, $preparedItems, $totalExpectedAmount);
            });

            return $order;
    }

    /**
     * Dispatch non-blocking in-app notifications, automatic seller chat message, and queued email jobs.
     */
    protected function dispatchPostOrderNotifications(
        Order $order,
        User $customer,
        User $sellerUser,
        array $preparedItems,
        float $totalExpectedAmount
    ): void {
        try {
            // 1. Send Idempotent Automatic Purchase Message from Seller to Buyer
            $this->sendAutomaticPurchaseMessage($order, $customer, $sellerUser, $preparedItems, $totalExpectedAmount);

            // Check low/out-of-stock for products
            foreach ($preparedItems as $pItem) {
                /** @var Product $freshProd */
                $freshProd = $pItem['product']->fresh();
                if ($freshProd) {
                    if ($freshProd->stock <= 0) {
                        Notification::create([
                            'userId'     => $sellerUser->id,
                            'title'      => '⚠️ Out of Stock',
                            'message'    => "\"{$freshProd->name}\" is now out of stock.",
                            'type'       => 'system',
                            'link'       => '/seller/products',
                            'targetRole' => 'seller',
                            'isRead'     => false,
                        ]);
                    } elseif ($freshProd->stock <= 5) {
                        Notification::create([
                            'userId'     => $sellerUser->id,
                            'title'      => '🔔 Low Stock Alert',
                            'message'    => "\"{$freshProd->name}\" has only {$freshProd->stock} items left.",
                            'type'       => 'system',
                            'link'       => '/seller/products',
                            'targetRole' => 'seller',
                            'isRead'     => false,
                        ]);
                    }
                }
            }

            // In-app notifications
            Notification::create([
                'userId'     => $customer->id,
                'title'      => 'Order Placed',
                'message'    => 'Your order has been placed successfully and is awaiting confirmation.',
                'type'       => 'order',
                'link'       => "/orders/{$order->id}",
                'targetRole' => 'customer',
                'isRead'     => false,
            ]);

            Notification::create([
                'userId'     => $sellerUser->id,
                'title'      => 'New Order Received',
                'message'    => "A customer placed a new order (#LB-" . strtoupper(substr($order->id, -8)) . ") in your shop.",
                'type'       => 'order',
                'link'       => "/seller/orders?order_id={$order->id}",
                'targetRole' => 'seller',
                'isRead'     => false,
            ]);

            // Queued / Non-blocking email delivery
            if ($customer->email) {
                $cMail = new \App\Mail\OrderStatusUpdatedMail(
                    $customer->name,
                    $order->id,
                    'Order Confirmed',
                    'Your order has been placed successfully and is being prepared.'
                );
                EmailNotificationService::sendNotification(
                    $customer->email,
                    $cMail,
                    'order_status_updated',
                    $customer->id,
                    'Order',
                    $order->id
                );
            }

            if ($sellerUser->email) {
                $sMail = new \App\Mail\NewOrderSellerMail(
                    $sellerUser->name,
                    $order->id,
                    $totalExpectedAmount,
                    $customer->name
                );
                EmailNotificationService::sendNotification(
                    $sellerUser->email,
                    $sMail,
                    'new_order',
                    $sellerUser->id,
                    'Order',
                    $order->id
                );
            }
        } catch (\Throwable $e) {
            Log::warning("Post-order notification failed for order {$order->id}: " . $e->getMessage());
        }
    }

    /**
     * Send an idempotent automatic purchase notification/message from seller to buyer.
     */
    protected function sendAutomaticPurchaseMessage(
        Order $order,
        User $customer,
        User $sellerUser,
        array $preparedItems,
        float $totalExpectedAmount
    ): void {
        try {
            $orderShortId = strtoupper(substr($order->id, -8));
            $idTag = "[order:{$order->id}]";

            // Idempotency check: prevent duplicate auto messages for the same order
            $alreadySent = Message::where('senderId', $sellerUser->id)
                ->where('receiverId', $customer->id)
                ->where(function ($q) use ($idTag, $orderShortId) {
                    $q->where('content', 'like', "%{$idTag}%")
                      ->orWhere('content', 'like', "%#LB-{$orderShortId}%");
                })
                ->exists();

            if ($alreadySent) {
                return;
            }

            $shopName = $sellerUser->shopName ?: $sellerUser->name ?: 'Artisan Shop';
            $dateStr = ($order->createdAt ?: now())->format('M d, Y h:i A');
            $statusStr = ucfirst($order->status ?: 'Pending');
            $orderLink = "/orders/{$order->id}";

            $lines = [];
            $lines[] = "✨ **Thank you for your order!**";
            $lines[] = "Your order **#LB-{$orderShortId}** from **{$shopName}** has been placed successfully.";
            $lines[] = "";
            $lines[] = "📅 **Purchase Date:** {$dateStr}";
            $lines[] = "📦 **Order Status:** {$statusStr}";
            $lines[] = "💰 **Total Amount:** ₱" . number_format($totalExpectedAmount, 2);
            $lines[] = "";
            $lines[] = "**Purchased Item(s):**";

            foreach ($preparedItems as $item) {
                $pName = $item['product_name'] ?? ($item['product']->name ?? 'Heritage Piece');
                $pQty = $item['quantity'] ?? 1;
                $pVar = $item['variation'] ?? null;
                $pPrice = number_format((float)($item['price'] ?? 0), 2);

                $itemLine = "• **{$pName}**";
                if ($pVar && strcasecmp($pVar, 'Original') !== 0) {
                    $itemLine .= " ({$pVar})";
                }
                $itemLine .= " — Qty: {$pQty} × ₱{$pPrice}";
                $lines[] = $itemLine;

                // Resolve product image with fallback chain
                $pImg = $item['product_image']
                    ?? ($item['product']?->getImageUrl() ?? null);

                if ($pImg) {
                    // Ensure absolute URL for image rendering in chat
                    if (!str_starts_with($pImg, 'http') && !str_starts_with($pImg, '//')) {
                        $pImg = rtrim(config('app.url', ''), '/') . '/' . ltrim($pImg, '/');
                    }
                    // Clickable image linking directly to order details
                    $lines[] = "[![{$pName}]({$pImg})]({$orderLink})";
                }
            }

            $lines[] = "";
            $lines[] = "👉 [View Order & Track Status]({$orderLink})";
            $lines[] = "<!-- {$idTag} -->";

            $content = implode("\n", $lines);

            Message::create([
                'senderId'   => $sellerUser->id,
                'receiverId' => $customer->id,
                'content'    => $content,
                'read'       => false,
            ]);
        } catch (\Throwable $e) {
            Log::warning("Automatic purchase message failed for order {$order->id}: " . $e->getMessage());
        }
    }
}
