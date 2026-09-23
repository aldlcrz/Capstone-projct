<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Address;
use App\Models\OrderShipping;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\User;
use App\Services\ShippingCalculatorService;
use App\Support\VariationFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    protected ShippingCalculatorService $shippingCalculator;

    public function __construct(ShippingCalculatorService $shippingCalculator)
    {
        $this->shippingCalculator = $shippingCalculator;
    }
    public function index(Request $request)
    {
        // Guard: Administrators cannot checkout or place orders
        if (Auth::check() && in_array(Auth::user()->role, ['admin', 'superadmin'])) {
            return redirect()->route(Auth::user()->role === 'superadmin' ? 'superadmin.dashboard' : 'admin.dashboard')
                ->with('error', 'Administrators cannot place orders or access checkout.');
        }

        $mode = $request->query('mode', 'cart');
        $cart = [];

        // Handle direct buy from product page
        if ($request->has('productId')) {
            $product = Product::findOrFail($request->productId);

            // Guard: Seller cannot buy own product
            if (Auth::check() && Auth::user()->role === 'seller' && Auth::id() === $product->sellerId) {
                return redirect()->route('products.show', $product->id)
                    ->with('error', 'Sellers cannot purchase their own products.');
            }
            $variation = VariationFormatter::label($request->input('variation'), $product->image)
                ?? $request->input('variation');
            $image = VariationFormatter::getImageForVariation($variation, $product) ?: $product->getImageUrl();

            $directItem = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->sale_price,
                'image' => $image,
                'quantity' => (int) $request->input('quantity', 1),
                'size' => $request->input('size'),
                'variation' => $variation,
                'sellerId' => $product->sellerId,
                'shippingFee' => $product->shippingFee ?? 0,
                'original_price' => $product->price,
                'discount_percentage' => $product->discount_percentage,
                'is_on_sale' => $product->is_on_sale && ($product->discount_percentage > 0),
                'category_name' => $product->category->name ?? 'Traditional',
            ];
            session()->put('buy_now_item', $directItem);
            $mode = 'buy_now';
        }

        if ($mode === 'buy_now') {
            $buyNowItem = session()->get('buy_now_item');
            if (!$buyNowItem) return redirect('/cart');
            $cart = [$buyNowItem];
        } elseif ($mode === 'selected') {
            $cart = session()->get('checkout_cart', []);
            if (empty($cart)) return redirect('/cart');
        } else {
            $cart = session()->get('cart', []);
            if (empty($cart)) return redirect('/cart');
        }

        // Live synchronisation of product image, name, and pricing
        foreach ($cart as &$item) {
            if (!empty($item['id'])) {
                $p = Product::find($item['id']);
                if ($p) {
                    $item['image'] = VariationFormatter::getImageForVariation($item['variation'] ?? null, $p) ?: $p->getImageUrl();
                    $item['name'] = $p->name;
                    $item['price'] = $p->sale_price;
                }
            }
        }
        unset($item);

        $addresses = Auth::user()->addresses ?? [];
        $subtotal = 0;
        foreach ($cart as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        $seller = null;
        $resolvedPayment = null;

        if (!empty($cart)) {
            $firstItem = reset($cart);
            $sellerId  = $firstItem['sellerId'] ?? null;
            $productId = $firstItem['id'] ?? null;

            if ($sellerId) {
                $seller = User::find($sellerId);
            }

            if ($productId) {
                $cartProduct = Product::with('seller')->find($productId);
                if ($cartProduct) {
                    if (!$seller && $cartProduct->seller) {
                        $seller = $cartProduct->seller;
                    }

                    // Build a resolved payment object that merges product overrides onto seller defaults
                    $resolvedPayment = (object) [
                        'isGcashAvailable' => $cartProduct->is_gcash_available ?? ($seller->isGcashAvailable ?? true),
                        'gcashNumber'      => $cartProduct->gcash_number ?: ($seller->gcashNumber ?? null),
                        'gcashQrCode'      => $cartProduct->gcash_qr_code ?: ($seller->gcashQrCode ?? null),
                        'isMayaAvailable'  => $cartProduct->is_maya_available  ?? ($seller->isMayaAvailable ?? false),
                        'mayaNumber'       => $cartProduct->maya_number ?: ($seller->mayaNumber ?? null),
                        'mayaQrCode'       => $cartProduct->maya_qr_code ?: ($seller->mayaQrCode ?? null),
                        'shopName'         => ($seller->shopName ?? null) ?: (($seller->name ?? null) ?: 'LumBarong Artisan Shop'),
                    ];
                }
            }
        }

        // If no product-level override, fall back to seller profile entirely
        $paymentSource = $resolvedPayment ?? $seller;

        $sellerIds = collect($cart)->map(function ($item) {
            $sId = $item['sellerId'] ?? null;
            if (!$sId && !empty($item['id'])) {
                $p = Product::find($item['id']);
                $sId = $p?->sellerId;
            }
            return $sId;
        })->filter()->unique();

        if ($sellerIds->count() > 1) {
            return redirect()->route('cart.index')->with('error', 'Orders are paid directly to each artisan\'s verified account. Please checkout one shop at a time.');
        }

        $sellers = User::whereIn('id', $sellerIds)->get();

        return view('checkout.index', compact('cart', 'addresses', 'subtotal', 'mode', 'seller', 'sellers', 'paymentSource'));
    }

    public function fromSelected(Request $request)
    {
        // Guard: Administrators cannot checkout or place orders
        if (Auth::check() && in_array(Auth::user()->role, ['admin', 'superadmin'])) {
            return redirect()->route(Auth::user()->role === 'superadmin' ? 'superadmin.dashboard' : 'admin.dashboard')
                ->with('error', 'Administrators cannot place orders or access checkout.');
        }

        $request->validate([
            'selected_keys' => 'required|array|min:1',
            'selected_keys.*' => 'required|string',
        ]);

        $cart = session()->get('cart', []);
        $selectedCart = [];

        foreach ($request->input('selected_keys', []) as $key) {
            if (isset($cart[$key])) {
                $selectedCart[$key] = $cart[$key];
            }
        }

        if (empty($selectedCart)) {
            return redirect()->route('cart.index')->with('error', 'No valid items selected for checkout.');
        }

        $sellerIds = collect($selectedCart)->map(function ($item) {
            $sId = $item['sellerId'] ?? null;
            if (!$sId && !empty($item['id'])) {
                $p = Product::find($item['id']);
                $sId = $p?->sellerId;
            }
            return $sId;
        })->filter()->unique();

        if ($sellerIds->count() > 1) {
            return redirect()->route('cart.index')->with('error', 'Orders are paid directly to each artisan\'s verified account. Please select items from one shop at a time to checkout.');
        }

        session()->put('checkout_cart', $selectedCart);
        session()->put('checkout_selected_keys', array_keys($selectedCart));

        return redirect()->route('checkout.index', ['mode' => 'selected']);
    }

    /**
     * Calculates dynamic shipping quote based on seller's configured preferred provider.
     * Returns informational quote with signed consistency token.
     */
    public function getShippingQuote(Request $request)
    {
        $request->validate([
            'address_id' => 'required|string',
            'mode'       => 'nullable|string',
        ]);

        $items = $request->input('items');
        if (!empty($items) && is_array($items)) {
            $cart = $items;
        } else {
            $mode = $request->input('mode', 'cart');
            if ($mode === 'buy_now') {
                $cart = [session()->get('buy_now_item')];
            } elseif ($mode === 'selected') {
                $cart = session()->get('checkout_cart', []);
            } else {
                $cart = session()->get('cart', []);
            }
        }

        if (empty($cart) || empty($cart[0])) {
            return response()->json(['success' => false, 'message' => 'Cart is empty.'], 422);
        }

        $address = Address::where('id', $request->address_id)
            ->where('userId', Auth::id())
            ->first();

        if (!$address) {
            return response()->json(['success' => false, 'message' => 'Selected delivery address not found.'], 404);
        }

        // Group items by seller
        $itemsBySeller = [];
        foreach ($cart as $item) {
            $sId = $item['sellerId'] ?? null;
            if (!$sId && !empty($item['id'])) {
                $p = Product::find($item['id']);
                $sId = $p?->sellerId;
            }
            if ($sId) {
                $itemsBySeller[$sId][] = $item;
            }
        }

        if (empty($itemsBySeller)) {
            return response()->json(['success' => false, 'message' => 'No valid items found in cart.'], 422);
        }

        $allSellerIds = array_keys($itemsBySeller);
        $sellerQuotes = [];
        $allSellerQuotes = [];

        try {
            foreach ($itemsBySeller as $sellerId => $sellerItems) {
                $seller = User::find($sellerId);
                if (!$seller) {
                    return response()->json(['success' => false, 'message' => 'Seller shop not found.'], 404);
                }

                $specificProviderId = $request->input('shipping_provider_id') ?: ($request->input('provider_id') ?: null);
                if ($specificProviderId) {
                    $quotes = $this->shippingCalculator->calculateQuotes($seller, $address, $sellerItems, $specificProviderId);
                } else {
                    $quotes = $this->shippingCalculator->calculateQuotes($seller, $address, $sellerItems);
                }

                if (empty($quotes)) {
                    return response()->json(['success' => false, 'message' => 'Delivery is currently not available for this delivery area.'], 422);
                }

                $preferredProvider = $this->shippingCalculator->getSellerPreferredProvider($seller);
                $primaryQuote = ($preferredProvider ? collect($quotes)->firstWhere('provider_id', $preferredProvider->id) : null)
                    ?: ($quotes[0] ?? null);

                $sellerQuotes[$sellerId] = $primaryQuote;
                $allSellerQuotes = array_merge($allSellerQuotes, $quotes);
            }

            $token = $this->shippingCalculator->generateQuoteToken($allSellerIds, $address->id, $cart);
            $primaryQuote = reset($sellerQuotes);

            // Determine if the destination is in the local cluster across all participating sellers
            $isLocalCluster = true;
            foreach ($itemsBySeller as $sellerId => $sellerItems) {
                $sellerUser = User::find($sellerId);
                if ($sellerUser && !$this->shippingCalculator->isLocalCluster($sellerUser, $address)) {
                    $isLocalCluster = false;
                    break;
                }
            }

            $availablePaymentMethods = $this->shippingCalculator->getAvailablePaymentMethods($isLocalCluster);

            return response()->json([
                'success'                  => true,
                'is_local_cluster'         => $isLocalCluster,
                'available_payment_methods'=> $availablePaymentMethods,
                'quote'                    => $primaryQuote,
                'quotes'                   => $allSellerQuotes,
                'seller_quotes'            => $sellerQuotes,
                'shipping_quote_token'     => $token,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function getShippingQuotes(Request $request)
    {
        return $this->getShippingQuote($request);
    }

    public function store(Request $request)
    {
        $inTransaction = false;
        // Guard: Administrators cannot place orders
        if (Auth::check() && in_array(Auth::user()->role, ['admin', 'superadmin'])) {
            return redirect()->route(Auth::user()->role === 'superadmin' ? 'superadmin.dashboard' : 'admin.dashboard')
                ->with('error', 'Administrators cannot place orders.');
        }

        $paymentMethod = trim($request->input('paymentMethod', 'GCash'));
        $isCod   = strcasecmp($paymentMethod, 'COD') === 0 || strcasecmp($paymentMethod, 'Cash on Delivery') === 0;
        $isGcash = strcasecmp($paymentMethod, 'GCash') === 0;
        $isMaya  = strcasecmp($paymentMethod, 'Maya') === 0;

        $validationRules = [
            'paymentMethod' => 'required|string',
            'address_id'    => 'required|string',
        ];

        if (!$isCod) {
            $validationRules['paymentReference'] = [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($isGcash, $isMaya) {
                    $raw = trim((string)$value);

                    if (preg_match('/^(\d)\1+$/', $raw)) {
                        $fail('Invalid payment reference number. Repeated digit sequences are not allowed.');
                        return;
                    }

                    if ($isGcash) {
                        if (!preg_match('/^\d{13}$/', $raw)) {
                            $fail('Reference number must be exactly 13 digits.');
                            return;
                        }
                    } elseif ($isMaya) {
                        if (!preg_match('/^\d{12}$/', $raw)) {
                            $fail('Reference number must be exactly 12 digits.');
                            return;
                        }
                    } else {
                        if (!preg_match('/^\d{10,16}$/', $raw)) {
                            $fail('The payment reference number must contain between 10 and 16 digits.');
                            return;
                        }
                    }

                    // Security: Reject already-used or currently claimed active payment reference numbers
                    $isDuplicate = PaymentTransaction::where('active_reference', $raw)->exists();
                    if (!$isDuplicate) {
                        // Fallback check against legacy orders if not yet backfilled
                        $isDuplicate = Order::where('paymentReference', $raw)
                            ->whereNotIn('status', ['Cancelled', 'Declined'])
                            ->where('paymentStatus', '!=', 'Payment Rejected')
                            ->exists();
                    }
                    if ($isDuplicate) {
                        $fail('This payment reference number is already tied to an active or verified order. Please provide a new and unique payment reference.');
                        return;
                    }
                },
            ];
            $validationRules['paymentScreenshot'] = 'required|image';
        }

        $request->validate($validationRules, [
            'paymentReference.required'  => 'Please provide your payment reference number.',
            'paymentScreenshot.required' => 'Payment receipt screenshot is required for online payments.',
            'address_id.required'        => 'Please provide a valid shipping address.',
        ]);

        try {
            // 1. Resolve cart items
            $mode = $request->input('mode', 'cart');
            $itemsInput = $request->input('items');
            if (!empty($itemsInput) && is_array($itemsInput)) {
                $cart = $itemsInput;
            } elseif ($mode === 'buy_now') {
                $cart = [session()->get('buy_now_item')];
            } elseif ($mode === 'selected') {
                $cart = session()->get('checkout_cart', []);
            } else {
                $cart = session()->get('cart', []);
            }
            
            if (empty($cart) || empty($cart[0])) throw new \Exception('Cart is empty');

            // 2. Resolve buyer shipping address strictly via address_id
            $addrRecord = Address::where('id', $request->address_id)
                ->where('userId', Auth::id())
                ->first();
            if (!$addrRecord) {
                throw new \Exception('Please provide a valid delivery address.');
            }
            $addressData = $addrRecord->toArray();

            $selectedProviderId = $request->input('shipping_provider_id') ?: $request->input('selected_provider_id');
            $quoteToken         = $request->input('shipping_quote_token');

            $itemsBySeller = [];
            foreach ($cart as $item) {
                $sellerId = $item['sellerId'] ?? null;
                if (!$sellerId && !empty($item['id'])) {
                    $prod = Product::find($item['id']);
                    $sellerId = $prod?->sellerId;
                }
                if ($sellerId) {
                    $itemsBySeller[$sellerId][] = $item;
                }
            }

            if (count($itemsBySeller) > 1) {
                throw new \Exception('Cross-shop checkout in a single payment is not supported. Please checkout each shop separately to ensure direct payment to each artisan.');
            }

            // Validate quote token across complete seller set if token provided
            if ($quoteToken) {
                if (!$this->shippingCalculator->validateQuoteToken($quoteToken, array_keys($itemsBySeller), $request->address_id, $cart)) {
                    throw new \Exception('Your shipping quote has expired or the order items changed. Please review and refresh your shipping quote.');
                }
            }

            // Validate COD locality
            if ($isCod) {
                foreach ($itemsBySeller as $sellerId => $items) {
                    $sellerUser = User::find($sellerId);
                    if ($sellerUser && !$this->shippingCalculator->isLocalCluster($sellerUser, $addressData)) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'paymentMethod' => ['Cash on Delivery (COD) is available only for nearby local deliveries. Please choose GCash or Maya.'],
                        ]);
                    }
                }
            }

            $sellerCalculatedQuotes = [];
            $totalExpectedAmount = 0;

            foreach ($itemsBySeller as $sellerId => $items) {
                $sellerUser = User::find($sellerId);
                if ($sellerUser) {
                    $shopName = $sellerUser->shopName ?: $sellerUser->name;
                    if (in_array($sellerUser->status, ['blocked', 'suspended'])) {
                        throw new \Exception("The shop '{$shopName}' is currently suspended and cannot process orders at this time.");
                    }
                    if ($sellerUser->status === 'frozen') {
                        throw new \Exception("The shop '{$shopName}' is currently frozen due to overdue monthly commission and cannot process orders at this time.");
                    }
                }

                $sellerSubtotal = 0;
                foreach ($items as $item) {
                    $sellerSubtotal += ((float)$item['price']) * ((int)$item['quantity']);
                }

                // Server-side authoritative calculation
                // Re-calculate quote against server database authoritatively using seller's preferred pricing provider or selected local method
                $preferredProvider = $this->shippingCalculator->getSellerPreferredProvider($sellerUser);
                $providerId = $selectedProviderId ?: ($preferredProvider?->id);
                $quotes = $this->shippingCalculator->calculateQuotes($sellerUser, $addressData, $items, $providerId);
                $chosenQuote = $quotes[0] ?? null;

                if (!$chosenQuote) {
                    throw new \Exception("Shipping service is currently not available for this delivery route or weight bracket.");
                }

                $sellerCalculatedQuotes[$sellerId] = $chosenQuote;
                $totalExpectedAmount += ($sellerSubtotal + (float)$chosenQuote['shipping_fee']);
            }

            // 3. Server-side Receipt Screening (only for online payments)
            $screening = null;
            if (!$isCod && $request->hasFile('paymentScreenshot')) {
                $tempPath = $request->file('paymentScreenshot')->getRealPath();
                $origName = $request->file('paymentScreenshot')->getClientOriginalName();
                $screening = \App\Services\AiService::verifyReceipt(
                    $tempPath,
                    $request->paymentReference,
                    $request->paymentMethod,
                    $totalExpectedAmount,
                    $origName
                );

                if (($screening['status'] ?? '') === 'REJECT' || !($screening['is_receipt'] ?? true)) {
                    $errorMessage = $screening['message'] ?? 'The uploaded file does not appear to be a valid mobile payment receipt screenshot. Please attach a genuine transaction confirmation.';
                    return redirect()->back()->withInput()->with('error', $errorMessage);
                }
            }

            $inTransaction = true;
            DB::beginTransaction();

            $orders = [];
            $postCommitTasks = [];
            foreach ($itemsBySeller as $sellerId => $items) {
                $sellerUser = User::find($sellerId);
                $orderId = (string) Str::uuid();
                $chosenShipping = $sellerCalculatedQuotes[$sellerId];
                $shippingFee = (float) $chosenShipping['shipping_fee'];

                $totalAmount = 0;
                foreach ($items as $item) {
                    $totalAmount += $item['price'] * $item['quantity'];
                }
                $totalAmount += $shippingFee;

                // 1. Group/aggregate demands and validate per-product & per-size requirements
                $requestedQuantities = [];
                $requestedSizes = [];
                foreach ($items as $item) {
                    $pId = (string) $item['id'];
                    $qty = max(1, (int)$item['quantity']);
                    $requestedQuantities[$pId] = ($requestedQuantities[$pId] ?? 0) + $qty;
                    if (!empty($item['size'])) {
                        $sizeKey = (string) $item['size'];
                        $requestedSizes[$pId][$sizeKey] = ($requestedSizes[$pId][$sizeKey] ?? 0) + $qty;
                    }
                }

                // 2. Sort product IDs deterministically to prevent deadlock across concurrent checkout transactions
                $sortedProductIds = collect(array_keys($requestedQuantities))->sort()->values();

                // 3. Lock each product row in sorted deterministic order and validate inventory
                $lockedProducts = [];
                foreach ($sortedProductIds as $pId) {
                    $product = Product::whereKey($pId)->lockForUpdate()->first();
                    if (!$product) {
                        throw new \Exception("A product in your cart is no longer available.");
                    }

                    $totalDemand = $requestedQuantities[$pId];
                    if ($product->stock < $totalDemand) {
                        throw new \Exception("Insufficient stock for \"{$product->name}\". Only {$product->stock} piece(s) available (requested: {$totalDemand}).");
                    }

                    // Validate per-size stock if tracked
                    if (!empty($product->size_stocks) && isset($requestedSizes[$pId])) {
                        $sizeStocks = $product->size_stocks;
                        foreach ($requestedSizes[$pId] as $sz => $szQty) {
                            $availSizeStock = isset($sizeStocks[$sz]) ? (int)$sizeStocks[$sz] : null;
                            if ($availSizeStock !== null && $availSizeStock < $szQty) {
                                throw new \Exception("Insufficient stock for size \"{$sz}\" of \"{$product->name}\". Only {$availSizeStock} available (requested: {$szQty}).");
                            }
                        }
                    }

                    $lockedProducts[$pId] = $product;
                }

                $paymentRefToSave = trim((string) $request->input('paymentReference', ''));
                if ($isCod && empty($paymentRefToSave)) {
                    $paymentRefToSave = 'COD-' . date('Ymd') . '-' . strtoupper(Str::random(6));
                }

                $order = Order::create([
                    'id' => $orderId,
                    'customerId' => Auth::id(),
                    'sellerId' => $sellerId,
                    'totalAmount' => $totalAmount,
                    'status' => 'Pending',
                    'paymentMethod' => $request->paymentMethod,
                    'paymentReference' => $paymentRefToSave,
                    'paymentStatus' => $isCod ? 'Pending Payment (COD)' : 'Payment Submitted',
                    'shippingAddress' => $addressData,
                    'courierName' => $chosenShipping['provider_name'],
                    'createdAt' => now(),
                    'updatedAt' => now(),
                ]);

                // Create Immutable OrderShipping calculation snapshot
                OrderShipping::create([
                    'id'                             => (string) Str::uuid(),
                    'order_id'                       => $orderId,
                    'provider_id'                    => $chosenShipping['provider_id'],
                    'provider_name'                  => $chosenShipping['provider_name'],
                    'pricing_provider_id'            => $chosenShipping['provider_id'],
                    'pricing_provider_name'          => $chosenShipping['provider_name'],
                    'fulfillment_provider_id'        => null,
                    'fulfillment_provider_name'      => null,
                    'shipping_rate_id'               => $chosenShipping['shipping_rate_id'],
                    'origin_zone_id'                 => $chosenShipping['origin_zone_id'],
                    'origin_zone_name'               => $chosenShipping['origin_zone_name'],
                    'destination_zone_id'            => $chosenShipping['destination_zone_id'],
                    'destination_zone_name'          => $chosenShipping['destination_zone_name'],
                    'actual_weight'                  => $chosenShipping['actual_weight'],
                    'volumetric_weight'              => $chosenShipping['volumetric_weight'],
                    'chargeable_weight'              => $chosenShipping['chargeable_weight'],
                    'rate_base_snapshot'             => $chosenShipping['rate_base_snapshot'],
                    'additional_weight_rate_snapshot'=> $chosenShipping['additional_weight_rate_snapshot'],
                    'volumetric_divisor_snapshot'    => $chosenShipping['volumetric_divisor_snapshot'],
                    'shipping_fee'                   => $shippingFee,
                    'estimated_days_min'             => $chosenShipping['estimated_days_min'],
                    'estimated_days_max'             => $chosenShipping['estimated_days_max'],
                    'shipping_status'                => 'Pending',
                ]);

                // Record initial OrderStatusHistory
                \App\Models\OrderStatusHistory::create([
                    'orderId' => $orderId,
                    'previousStatus' => null,
                    'newStatus' => 'Pending',
                    'updatedBy' => Auth::id(),
                    'userRole' => 'customer',
                    'notes' => $isCod 
                        ? 'Order placed by customer via Cash on Delivery / Pay on Claim.' 
                        : 'Order placed by customer. Payment proof submitted, awaiting artisan verification.',
                ]);

                $storedPath = null;
                if ($request->hasFile('paymentScreenshot')) {
                    $storedPath = $request->file('paymentScreenshot')->store('payments', 'public');
                    $order->paymentProof = $storedPath;
                    $order->save();
                }

                if (!$isCod) {
                    // Create PaymentTransaction attempt linked to this order
                    $rawRef = trim((string) $paymentRefToSave);
                    $detectedAmt = isset($screening['detected_amount']) && is_numeric($screening['detected_amount'])
                        ? (float) $screening['detected_amount']
                        : null;
                    $tier = in_array(($screening['status'] ?? ''), ['PASS', 'REVIEW', 'REJECT'])
                        ? $screening['status']
                        : 'REVIEW';

                    PaymentTransaction::create([
                        'order_id' => $orderId,
                        'customer_id' => Auth::id(),
                        'seller_id' => $sellerId,
                        'reference_number' => $rawRef,
                        'active_reference' => $rawRef,
                        'wallet_type' => $request->paymentMethod,
                        'expected_amount' => $totalAmount,
                        'detected_amount' => $detectedAmt,
                        'amount_confidence' => $screening['amount_confidence'] ?? null,
                        'reference_confidence' => $screening['reference_confidence'] ?? null,
                        'confidence' => $screening['confidence'] ?? null,
                        'status' => 'UNVERIFIED',
                        'verification_tier' => $tier,
                        'receipt_path' => $storedPath,
                        'notes' => $screening['message'] ?? 'Initial submission at checkout',
                    ]);
                }

                foreach ($items as $item) {
                    $product = $lockedProducts[$item['id']] ?? null;

                    $orderItemData = [
                        'id' => (string) Str::uuid(),
                        'orderId' => $orderId,
                        'productId' => $item['id'],
                        'product_name' => $product?->name ?? ($item['name'] ?? 'Heritage Piece'),
                        'product_image' => !empty($item['image']) ? $item['image'] : ($product ? VariationFormatter::getImageForVariation($item['variation'] ?? null, $product) : null),
                        'quantity' => $item['quantity'] ?? 1,
                        'price' => $item['price'] ?? ($product?->price ?? 0),
                        'size' => $item['size'] ?? null,
                        'variation' => VariationFormatter::label($item['variation'] ?? null, $product?->image)
                            ?? ($item['variation'] ?? 'Original'),
                    ];

                    OrderItem::create($orderItemData);
                }

                // 4. Deduct inventory atomically across locked products
                foreach ($lockedProducts as $pId => $product) {
                    $deductQty = $requestedQuantities[$pId];

                    if (!empty($product->size_stocks) && isset($requestedSizes[$pId])) {
                        $sizeStocks = $product->size_stocks;
                        foreach ($requestedSizes[$pId] as $sz => $szQty) {
                            if (isset($sizeStocks[$sz])) {
                                $sizeStocks[$sz] = max(0, ((int)$sizeStocks[$sz]) - $szQty);
                            }
                        }
                        $product->size_stocks = $sizeStocks;
                    }

                    $product->stock = max(0, $product->stock - $deductQty);
                    $product->save();

                    // Low/Out of Stock Warnings
                    $freshStock = $product->stock;
                    $prodName = $product->name;
                    $postCommitTasks[] = function() use ($sellerId, $prodName, $freshStock) {
                        if ($freshStock <= 0) {
                            \App\Models\Notification::send($sellerId, '⚠️ Out of Stock', "\"{$prodName}\" is now out of stock.", 'system', '/seller/products', 'seller');
                        } elseif ($freshStock <= 5) {
                            \App\Models\Notification::send($sellerId, 'Low Stock Alert', "\"{$prodName}\" has only {$freshStock} items left.", 'system', '/seller/products', 'seller');
                        }
                    };
                }
                
                // Collect side-effects to run after commit
                $currentOrderId = $orderId;
                $currentSellerId = $sellerId;
                $currentTotalAmount = (float) $totalAmount;
                $currentCustomerUser = Auth::user();
                $currentSellerUser = $sellerUser;
                $postCommitTasks[] = function() use ($currentOrderId, $currentSellerId, $currentTotalAmount, $currentCustomerUser, $currentSellerUser) {
                    \App\Models\Notification::send($currentCustomerUser->id, 'Order Placed', 'Your order has been placed successfully and is awaiting confirmation.', 'order', '/orders/' . $currentOrderId, 'customer');
                    \App\Models\Notification::send($currentSellerId, 'New order received', 'A customer has placed a new order in your shop.', 'order', '/seller/orders', 'seller');

                    try {
                        \App\Models\Message::create([
                            'senderId'   => $currentSellerId,
                            'receiverId' => $currentCustomerUser->id,
                            'content'    => "Thank you for placing your order (#" . substr($currentOrderId, 0, 8) . ")! We have received your order and will prepare your handcrafted pieces with care. Feel free to message us here if you have any questions or custom requests.",
                            'read'       => false,
                        ]);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('Automatic checkout chat message error: ' . $e->getMessage());
                    }

                    if ($currentCustomerUser && $currentCustomerUser->email) {
                        try {
                            $cMail = new \App\Mail\OrderStatusUpdatedMail($currentCustomerUser->name, $currentOrderId, 'Order Confirmed', 'Your order has been placed successfully and confirmed.');
                            \App\Services\EmailNotificationService::sendNotification($currentCustomerUser->email, $cMail, 'order_status_updated', $currentCustomerUser->id, 'Order', $currentOrderId);
                        } catch (\Throwable $e) {
                            \Illuminate\Support\Facades\Log::warning('Customer order confirmation email failed: ' . $e->getMessage());
                        }
                    }

                    if ($currentSellerUser && $currentSellerUser->email) {
                        try {
                            $sMail = new \App\Mail\NewOrderSellerMail($currentSellerUser->name, $currentOrderId, $currentTotalAmount, $currentCustomerUser?->name);
                            \App\Services\EmailNotificationService::sendNotification($currentSellerUser->email, $sMail, 'new_order', $currentSellerUser->id, 'Order', $currentOrderId);
                        } catch (\Throwable $e) {
                            \Illuminate\Support\Facades\Log::warning('Seller new order notification email failed: ' . $e->getMessage());
                        }
                    }
                };

                $orders[] = $order;
            }

            if ($mode === 'cart') {
                session()->forget('cart');
                if (Auth::check()) {
                    User::find(Auth::id())->update(['cart' => json_encode([])]);
                }
            } elseif ($mode === 'selected') {
                $mainCart = session()->get('cart', []);
                foreach (session()->get('checkout_selected_keys', []) as $key) {
                    unset($mainCart[$key]);
                }
                session()->put('cart', $mainCart);
                session()->forget(['checkout_cart', 'checkout_selected_keys']);
                if (Auth::check()) {
                    User::find(Auth::id())->update(['cart' => json_encode($mainCart)]);
                }
            } else {
                session()->forget('buy_now_item');
            }

            DB::commit();

            // Run post-commit notifications/emails safely outside the transaction
            foreach ($postCommitTasks as $task) {
                try {
                    $task();
                } catch (\Throwable $taskEx) {
                    \Illuminate\Support\Facades\Log::error('Post-checkout side effect failed: ' . $taskEx->getMessage(), ['exception' => $taskEx]);
                }
            }

            if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
                return response()->json([
                    'success'  => true,
                    'message'  => 'Order placed successfully!',
                    'redirect' => route('orders'),
                ]);
            }

            return redirect()->route('orders')->with('success', 'Order placed successfully!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            if (!empty($inTransaction) && DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            throw $e;
        } catch (\Illuminate\Database\QueryException $e) {
            if (!empty($inTransaction) && DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            \Illuminate\Support\Facades\Log::error("CHECKOUT_DB_ERROR: " . $e->getMessage());
            $msg = ($e->getCode() == 23000 || str_contains($e->getMessage(), 'active_reference') || str_contains($e->getMessage(), 'UNIQUE constraint failed'))
                ? 'This payment reference has already been claimed by another active order. Please provide a new and unique payment reference.'
                : 'Failed to place order: ' . $e->getMessage();

            if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->back()->withInput()->with('error', $msg);
        } catch (\Throwable $e) {
            if (!empty($inTransaction) && DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            \Illuminate\Support\Facades\Log::error("CHECKOUT_ERROR: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return redirect()->back()->withInput()->with('error', 'Failed to place order: ' . $e->getMessage());
        }
    }
}
