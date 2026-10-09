<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Address;
use App\Models\OrderShipping;
use App\Models\PaymentTransaction;
use App\Models\RefundTransaction;
use App\Models\ReturnRequest;
use App\Models\Notification;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\User;
use App\Services\CreateOrderService;
use App\Services\ShippingCalculatorService;
use App\Support\VariationFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    protected ShippingCalculatorService $shippingCalculator;
    protected CreateOrderService $createOrderService;

    public function __construct(
        ShippingCalculatorService $shippingCalculator,
        CreateOrderService $createOrderService
    ) {
        $this->shippingCalculator = $shippingCalculator;
        $this->createOrderService = $createOrderService;
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
                'original_price' => (float) $product->price,
                'discount_percentage' => $product->isSaleActive() ? (float) $product->discount_percentage : 0,
                'is_on_sale' => $product->isSaleActive(),
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
                    $item['price'] = (float) $p->sale_price;
                    $item['original_price'] = (float) $p->price;
                    $item['is_on_sale'] = $p->isSaleActive();
                    $item['discount_percentage'] = $p->isSaleActive() ? (float) $p->discount_percentage : 0;
                }
            }
        }
        unset($item);

        $addresses = collect();
        if (Auth::check()) {
            $userId = Auth::id();
            // Ensure at least one default address is flagged if addresses exist
            if (Address::where('userId', $userId)->exists() && !Address::where('userId', $userId)->where('isDefault', true)->exists()) {
                Address::where('userId', $userId)->orderByDesc('createdAt')->first()?->update(['isDefault' => true]);
            }
            $addresses = Address::where('userId', $userId)->orderByDesc('isDefault')->orderByDesc('createdAt')->get();
        }
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

        $cartValues = array_values(array_filter((array)$cart, fn($i) => !empty($i)));
        if (empty($cartValues)) {
            return response()->json(['success' => false, 'message' => 'Cart is empty.'], 422);
        }
        $cart = $cartValues;

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
            'paymentMethod'   => 'required|string',
            'address_id'      => 'required_without:shippingAddress|nullable|string',
            'shippingAddress' => 'required_without:address_id|nullable',
        ];

        if (!$isCod) {
            $validationRules['paymentReference'] = 'nullable|string';
            if ($request->hasFile('paymentScreenshots')) {
                $validationRules['paymentScreenshots'] = 'required|array|min:1';
                $validationRules['paymentScreenshots.*'] = 'required|image|max:10240';
            } else {
                $validationRules['paymentScreenshot'] = 'required|image|max:10240';
            }
        }

        $request->validate($validationRules, [
            'paymentScreenshot.required' => 'Payment receipt screenshot is required for online payments.',
            'paymentScreenshots.required' => 'Payment receipt screenshot is required for online payments.',
            'address_id.required_without' => 'Please provide a valid shipping address.',
            'shippingAddress.required_without' => 'Please provide a valid shipping address.',
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

            $cartValues = array_values(array_filter((array)$cart, fn($i) => !empty($i)));
            if (empty($cartValues)) {
                throw new \Exception('Cart is empty');
            }
            $cart = $cartValues;

            // 2. Handle Receipt Upload & Screening for Online Payments (Single or Multi-Receipt)
            $receiptList = [];
            if (!$isCod) {
                $uploadedFiles = [];
                if ($request->hasFile('paymentScreenshots')) {
                    $uploadedFiles = (array) $request->file('paymentScreenshots');
                } elseif ($request->hasFile('paymentScreenshot')) {
                    $uploadedFiles = [$request->file('paymentScreenshot')];
                }

                if (empty($uploadedFiles)) {
                    if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
                        return response()->json(['success' => false, 'message' => 'Payment receipt screenshot is required for online payments.'], 422);
                    }
                    return redirect()->back()->withInput()->with('error', 'Payment receipt screenshot is required for online payments.');
                }

                // Calculate authoritative expected total from database products for screening context
                $authoritativeSubtotal = 0.0;
                foreach ($cart as $cItem) {
                    $p = Product::find($cItem['id'] ?? null);
                    if ($p) {
                        $authoritativeSubtotal += ((float)$p->sale_price) * (int)($cItem['quantity'] ?? 1);
                    }
                }

                $submittedRefs = (array) $request->input('paymentReferences', []);
                if (empty($submittedRefs) && $request->filled('paymentReference')) {
                    $submittedRefs = [$request->input('paymentReference')];
                }

                foreach ($uploadedFiles as $idx => $file) {
                    $tempPath = $file->getRealPath();
                    $origName = $file->getClientOriginalName();
                    $expectedRef = $submittedRefs[$idx] ?? ($request->input('paymentReference') ?? '');

                    // Store receipt privately
                    $storedFileName = (string) Str::uuid() . '.' . ($file->getClientOriginalExtension() ?: 'jpg');
                    $storedPath = $file->storeAs('payments', $storedFileName, 'local');
                    $paymentProofPath = 'private/' . $storedPath;

                    $screening = \App\Services\AiService::verifyReceipt(
                        $tempPath,
                        (string) $expectedRef,
                        $paymentMethod,
                        $authoritativeSubtotal,
                        $origName
                    );

                    if (($screening['status'] ?? '') === 'REJECT' || !($screening['is_receipt'] ?? true)) {
                        $errorMessage = $screening['message'] ?? 'The uploaded file does not appear to be a valid mobile payment receipt screenshot. Please attach a genuine transaction confirmation.';
                        if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
                            return response()->json(['success' => false, 'message' => $errorMessage], 422);
                        }
                        return redirect()->back()->withInput()->with('error', $errorMessage);
                    }

                    $receiptList[] = [
                        'screening'        => $screening,
                        'paymentProof'     => $paymentProofPath,
                        'paymentReference' => $screening['detected_ref'] ?: $expectedRef,
                    ];
                }
            }

            // 3. Delegate to Canonical CreateOrderService
            $order = $this->createOrderService->createOrder([
                'customer'            => Auth::user(),
                'items'               => $cart,
                'address_id'          => $request->address_id,
                'shippingAddress'     => $request->input('shippingAddress'),
                'paymentMethod'       => $paymentMethod,
                'paymentReference'    => $receiptList[0]['paymentReference'] ?? $request->input('paymentReference'),
                'paymentProof'        => $receiptList[0]['paymentProof'] ?? null,
                'screening'           => $receiptList[0]['screening'] ?? null,
                'receipts'            => $receiptList,
                'refund_mobile_number'=> $request->input('refund_mobile_number') ?: $request->input('refundMobileNumber'),
                'quoteToken'          => $request->input('shipping_quote_token'),
                'selectedProviderId'  => $request->input('shipping_provider_id') ?: $request->input('selected_provider_id'),
                'idempotencyKey'      => $request->input('idempotency_key') ?: $request->header('X-Idempotency-Key'),
                'visitorSessionId'    => $request->header('X-Visitor-Session') ?? $request->visitorSessionId,
            ]);

            // 4. Cart Session Cleanup
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

            if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
                return response()->json([
                    'success'  => true,
                    'message'  => 'Order placed successfully!',
                    'order_id' => $order->id,
                    'redirect' => route('orders'),
                ]);
            }

            return redirect()->route('orders')->with('success', 'Order placed successfully!');

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Illuminate\Database\QueryException $e) {
            \Illuminate\Support\Facades\Log::error("CHECKOUT_DB_ERROR: " . $e->getMessage());
            $msg = ($e->getCode() == 23000 || str_contains($e->getMessage(), 'active_reference') || str_contains($e->getMessage(), 'UNIQUE constraint failed'))
                ? 'This payment reference has already been claimed by another active order. Please provide a new and unique payment reference.'
                : 'Failed to place order: ' . $e->getMessage();

            if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->back()->withInput()->with('error', $msg);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("CHECKOUT_ERROR: " . $e->getMessage());
            if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return redirect()->back()->withInput()->with('error', 'Failed to place order: ' . $e->getMessage());
        }
    }

    /**
     * Cancel checkout and record refund request if the customer already sent GCash / Maya payment.
     */
    public function cancelAndRefund(Request $request)
    {
        $alreadyPaid = filter_var($request->input('already_paid', false), FILTER_VALIDATE_BOOLEAN);

        if (!$alreadyPaid) {
            // Simply clear the checkout session and return safely to cart
            $mode = $request->input('mode', 'cart');
            if ($mode === 'buy_now') {
                session()->forget('buy_now_item');
            } elseif ($mode === 'selected') {
                session()->forget(['checkout_cart', 'checkout_selected_keys']);
            }

            if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
                return response()->json([
                    'success'  => true,
                    'message'  => 'Checkout cancelled. No payment was recorded.',
                    'redirect' => route('cart.index'),
                ]);
            }
            return redirect()->route('cart.index')->with('info', 'Checkout cancelled.');
        }

        $validated = $request->validate([
            'refund_method'        => 'required|string|in:GCash,Maya,gcash,maya,Card,card',
            'refund_account_name'  => 'required|string|max:255',
            'refund_mobile_number' => 'required|string|regex:/^09\d{9}$/',
            'refund_amount'        => 'required|numeric|min:1',
            'refund_reference'     => 'nullable|string|max:100',
            'reason'               => 'nullable|string|max:500',
            'payment_screenshot'   => 'nullable|image|max:10240',
        ], [
            'refund_mobile_number.regex'   => 'Please enter a valid 11-digit Philippine mobile number starting with 09 (e.g. 09123456789).',
            'refund_account_name.required' => 'Please enter the account holder name.',
            'refund_amount.required'       => 'Please enter the amount transferred.',
        ]);

        $customer = Auth::user();
        $refundMethod = ucfirst(strtolower($validated['refund_method']));
        if (strtoupper($refundMethod) === 'GCASH') $refundMethod = 'GCash';
        if (strtoupper($refundMethod) === 'MAYA') $refundMethod = 'Maya';

        $refundMobile = trim($validated['refund_mobile_number']);
        $refundAccountName = trim($validated['refund_account_name']);
        $refundAmount = (float) $validated['refund_amount'];
        $refundReference = trim($validated['refund_reference'] ?? '');
        $reason = trim($validated['reason'] ?? 'Customer cancelled during checkout');

        // Store proof receipt if uploaded
        $paymentProofPath = null;
        if ($request->hasFile('payment_screenshot')) {
            $file = $request->file('payment_screenshot');
            $storedFileName = (string) Str::uuid() . '.' . ($file->getClientOriginalExtension() ?: 'jpg');
            $storedPath = $file->storeAs('payments', $storedFileName, 'local');
            $paymentProofPath = 'private/' . $storedPath;
        }

        // Determine sellerId from cart or items
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

        $sellerId = null;
        foreach ((array) $cart as $item) {
            if (!empty($item['sellerId'])) {
                $sellerId = $item['sellerId'];
                break;
            }
            if (!empty($item['id'])) {
                $p = Product::find($item['id']);
                if ($p && $p->sellerId) {
                    $sellerId = $p->sellerId;
                    break;
                }
            }
        }

        if (!$sellerId && $request->filled('seller_id')) {
            $sellerId = $request->input('seller_id');
        }
        if (!$sellerId) {
            $sellerId = User::where('role', 'artisan')->value('id') ?? User::whereIn('role', ['admin', 'superadmin'])->value('id');
        }

        return DB::transaction(function () use (
            $customer,
            $sellerId,
            $refundMethod,
            $refundMobile,
            $refundAccountName,
            $refundAmount,
            $refundReference,
            $reason,
            $paymentProofPath,
            $request,
            $mode
        ) {
            // Resolve shipping address for order model constraint
            $shippingAddressStr = 'N/A - Cancelled during checkout';
            if ($request->filled('shippingAddress')) {
                $shippingAddressStr = is_array($request->input('shippingAddress')) ? json_encode($request->input('shippingAddress')) : (string)$request->input('shippingAddress');
            } elseif ($request->filled('address_id')) {
                $addr = Address::find($request->input('address_id'));
                if ($addr) {
                    $shippingAddressStr = "{$addr->houseNo}, {$addr->street}, {$addr->barangay}, {$addr->city}, {$addr->province} {$addr->postalCode}";
                }
            } elseif ($customer) {
                $defaultAddr = Address::where('userId', $customer->id)->where('isDefault', true)->first()
                    ?? Address::where('userId', $customer->id)->first();
                if ($defaultAddr) {
                    $shippingAddressStr = "{$defaultAddr->houseNo}, {$defaultAddr->street}, {$defaultAddr->barangay}, {$defaultAddr->city}, {$defaultAddr->province} {$defaultAddr->postalCode}";
                }
            }

            $orderId = (string) Str::uuid();

            // Create cancelled order record so history and refund can be tracked
            $order = Order::create([
                'id'                   => $orderId,
                'customerId'           => $customer ? $customer->id : null,
                'sellerId'             => $sellerId,
                'shippingAddress'      => $shippingAddressStr,
                'totalAmount'          => $refundAmount,
                'status'               => 'cancelled',
                'paymentStatus'        => 'Refund Requested',
                'paymentMethod'        => $refundMethod,
                'paymentReference'     => $refundReference ?: null,
                'paymentProof'         => $paymentProofPath,
                'refund_mobile_number' => $refundMobile,
                'cancellationReason'   => "Customer cancelled during checkout and requested refund to {$refundMethod} ({$refundMobile}). Reason: {$reason}",
                'createdAt'            => now(),
            ]);

            // Record status history
            OrderStatusHistory::create([
                'id'             => (string) Str::uuid(),
                'orderId'        => $order->id,
                'previousStatus' => 'pending',
                'newStatus'      => 'cancelled',
                'userRole'       => $customer ? ($customer->role ?? 'customer') : 'customer',
                'notes'          => "Order cancelled at checkout. Refund requested to {$refundMethod} ({$refundMobile} - {$refundAccountName}). Reference: " . ($refundReference ?: 'N/A'),
                'updatedBy'      => $customer ? $customer->id : null,
                'createdAt'      => now(),
            ]);

            // Create ReturnRequest / Refund record for centralized Admin dispute & disbursement management
            $returnReason = "Checkout Payment Refund: Customer transferred ₱" . number_format($refundAmount, 2) . " via {$refundMethod} and cancelled checkout. Send refund to: {$refundMobile} ({$refundAccountName}). Reference: " . ($refundReference ?: 'N/A') . ". Customer Notes: {$reason}";

            $returnReq = ReturnRequest::create([
                'id'               => (string) Str::uuid(),
                'orderId'          => $order->id,
                'customer_id'      => $customer ? $customer->id : null,
                'seller_id'        => $sellerId,
                'reason'           => $returnReason,
                'status'           => 'pending',
                'return_status'    => 'requested',
                'refund_status'    => 'pending',
                'requested_amount' => $refundAmount,
                'createdAt'        => now(),
            ]);

            // Create pending RefundTransaction record
            RefundTransaction::create([
                'id'                            => (string) Str::uuid(),
                'order_id'                      => $order->id,
                'return_request_id'             => $returnReq->id,
                'payment_method'                => $refundMethod,
                'refund_method'                 => $refundMethod,
                'refund_amount'                 => $refundAmount,
                'destination_account_encrypted' => $refundMobile,
                'destination_account_masked'    => substr($refundMobile, 0, 4) . '***' . substr($refundMobile, -3),
                'destination_account_name'      => $refundAccountName,
                'status'                        => 'pending',
            ]);

            // Notify Platform Administrators
            $adminUser = User::whereIn('role', ['admin', 'superadmin'])->first();
            if ($adminUser) {
                Notification::create([
                    'id'         => (string) Str::uuid(),
                    'userId'     => $adminUser->id,
                    'targetRole' => 'admin',
                    'title'      => 'Checkout Refund Request',
                    'message'    => "Customer " . ($customer ? $customer->name : 'Buyer') . " cancelled checkout after paying ₱" . number_format($refundAmount, 2) . " via {$refundMethod}. Please disburse refund to {$refundMobile} ({$refundAccountName}).",
                    'type'       => 'refund_requested',
                    'read'       => false,
                    'link'       => route('admin.returns.index', ['search' => $order->id]),
                    'createdAt'  => now(),
                ]);
            }

            // Notify Customer in-app
                Notification::create([
                    'id'         => (string) Str::uuid(),
                    'userId'     => $customer->id,
                    'targetRole' => 'customer',
                    'title'      => 'Cancellation & Refund Request Received',
                    'message'    => "Your request to cancel and refund ₱" . number_format($refundAmount, 2) . " has been submitted to the Admin. The funds will be sent to your {$refundMethod} number ({$refundMobile}) once verified.",
                    'type'       => 'refund_requested',
                    'read'       => false,
                    'link'       => route('orders'),
                    'createdAt'  => now(),
                ]);

                // Send Official LumBarong Inbox Message
                \App\Services\Messaging\LumbarongSystemMessageService::sendOrderCancelledMessage(
                    $order,
                    "Checkout cancelled by customer: {$reason}. Refund requested to {$refundMethod} ({$refundMobile}).",
                    'Customer'
                );
            }

            // Cleanup cart / checkout sessions
            if ($mode === 'buy_now') {
                session()->forget('buy_now_item');
            } elseif ($mode === 'selected') {
                session()->forget(['checkout_cart', 'checkout_selected_keys']);
            }

            $successMsg = "Your cancellation and refund request of ₱" . number_format($refundAmount, 2) . " has been submitted to the Admin. Your refund will be sent to your {$refundMethod} number ({$refundMobile}) after verification.";

            if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
                return response()->json([
                    'success'  => true,
                    'message'  => $successMsg,
                    'order_id' => $order->id,
                    'redirect' => route('orders'),
                ]);
            }

            return redirect()->route('orders')->with('success', $successMsg);
        });
    }
}
