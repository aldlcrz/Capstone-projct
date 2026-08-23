<?php

use App\Http\Controllers\WebController;
use App\Http\Controllers\WebAuthController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\ReturnRequestController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminCategoryController;
use App\Http\Controllers\AdminBannerController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\ProductManagementController;
use App\Http\Controllers\SuperAdminController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [WebController::class, 'index'])->name('home');
Route::get('/products/{id}', [WebController::class, 'productDetails'])->name('products.show');
Route::get('/shops/{id}', [WebController::class, 'sellerShop'])->name('shops.show');
Route::get('/shop/{id}', [WebController::class, 'sellerShop'])->name('shop.show');

// Public Pages
Route::get('/about', function() { return view('pages.about'); })->name('about');
Route::get('/privacy', function() { return view('pages.privacy'); })->name('privacy');
Route::get('/terms', function() { return view('pages.terms'); })->name('terms');

// Auth Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
    Route::get('/register', [WebAuthController::class, 'showRegister'])->name('register');
    Route::get('/seller/register', [WebAuthController::class, 'showSellerRegister'])->name('seller.register');
    Route::post('/seller/register', [WebAuthController::class, 'sellerRegister'])->name('seller.register.submit');

    // Email Verification Routes
    Route::get('/verify-email', [WebAuthController::class, 'showVerifyEmail'])->name('verify.email');
    Route::post('/verify-email', [WebAuthController::class, 'verifyEmail'])->name('verify.email.submit');
    Route::post('/resend-verification', [WebAuthController::class, 'resendVerificationCode'])->name('verify.email.resend');

    // 6-Digit Code Password Reset Routes
    Route::get('/forgot-password', function() { return view('auth.forgot-password'); })->name('password.request');
    Route::post('/forgot-password', [WebAuthController::class, 'forgotPassword'])->name('password.email');
    Route::get('/forgot-password/verify', [WebAuthController::class, 'showVerifyResetCode'])->name('password.verify.code');
    Route::post('/forgot-password/verify', [WebAuthController::class, 'verifyResetCode'])->name('password.verify.code.submit');
    Route::get('/reset-password', [WebAuthController::class, 'showResetPassword'])->name('password.reset.new');
    Route::post('/reset-password', [WebAuthController::class, 'resetPassword'])->name('password.update.submit');

    Route::post('/login', [WebAuthController::class, 'login']);
    Route::post('/register', [WebAuthController::class, 'register']);
    Route::post('/auth/google', [WebAuthController::class, 'handleGoogleLogin'])->name('auth.google');
    Route::post('/auth/google/signup', [WebAuthController::class, 'handleGoogleSignup'])->name('auth.google.signup');
    Route::post('/auth/google/seller/signup', [WebAuthController::class, 'handleGoogleSellerSignup'])->name('auth.google.seller.signup');
});

Route::post('/submit-commission-payment', [WebAuthController::class, 'submitCommissionPayment'])->name('commission.submit-payment');

Route::middleware('auth')->group(function () {
    // Profile
    Route::get('/profile', [WebAuthController::class, 'profile'])->name('profile');
    Route::post('/profile', [WebAuthController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile', [WebAuthController::class, 'updateProfile']);
    Route::get('/profile/addresses', [WebAuthController::class, 'addresses'])->name('profile.addresses');
    Route::get('/profile/change-password', [WebAuthController::class, 'changePasswordPage'])->name('profile.change-password');
    Route::post('/profile/change-password', [WebAuthController::class, 'changePassword'])->name('profile.change-password.submit');

    // Cart
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
    Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');
    Route::post('/cart/remove-selected', [CartController::class, 'removeSelected'])->name('cart.remove-selected');
    Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');
    Route::post('/cart/remove/{key}', [CartController::class, 'remove'])->name('cart.remove')->where('key', '.*');

    // Checkout
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::post('/checkout/selected', [CheckoutController::class, 'fromSelected'])->name('checkout.selected');

    // Orders
    Route::get('/orders', fn() => redirect()->route('orders'));
    Route::get('/orders/my-orders', [WebController::class, 'orders'])->name('orders');
    Route::get('/orders/{id}', [WebController::class, 'orderDetail'])->name('orders.show');
    Route::patch('/orders/{id}/confirm', [OrderController::class, 'confirmReceived'])->name('orders.confirm');
    Route::patch('/api/orders/{id}/status', [OrderController::class, 'updateOrderStatus']);
    Route::patch('/seller/api/orders/{id}/status', [OrderController::class, 'updateOrderStatus']);
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancelOrder'])->name('orders.cancel');
    Route::post('/api/orders/{id}/cancel', [OrderController::class, 'cancelOrder']);
    Route::post('/seller/api/orders/{id}/cancel', [OrderController::class, 'cancelOrder'])->name('orders.seller-cancel');
    Route::post('/seller/api/orders/{id}/reject-payment', [OrderController::class, 'rejectPayment'])->name('orders.reject-payment');
    Route::post('/api/orders/{id}/resubmit-payment', [OrderController::class, 'resubmitPayment'])->name('orders.resubmit-payment');
    Route::post('/seller/api/orders/{id}/packing-proof', [OrderController::class, 'uploadPackingProof'])->name('orders.packing-proof');


    // Notifications
    Route::get('/notifications', [WebController::class, 'notifications'])->name('notifications.index');
    Route::post('/notifications/read-all', [WebController::class, 'readAllNotifications'])->name('notifications.read-all');
    Route::get('/api/notifications', [NotificationController::class, 'getMyNotifications']);
    Route::post('/api/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::get('/api/notifications/unread-count', [NotificationController::class, 'getUnreadCount']);

    // Wishlist
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/toggle', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::get('/notifications/{id}/read', [NotificationController::class, 'readAndRedirect'])->name('notifications.read-and-redirect');
    Route::get('/notifications/{id}/read-and-redirect', [NotificationController::class, 'readAndRedirect']);

    Route::match(['get', 'post'], '/logout', [WebAuthController::class, 'logout'])->name('logout');

    // Address Management
    Route::get('/api/addresses', [AddressController::class, 'index']);
    Route::post('/api/addresses', [AddressController::class, 'store']);
    Route::put('/api/addresses/{id}', [AddressController::class, 'update']);
    Route::delete('/api/addresses/{id}', [AddressController::class, 'destroy']);
    Route::patch('/api/addresses/{id}/set-default', [AddressController::class, 'setDefault']);

    // Chat (Web & API routes for chat-widget and artisan messaging)
    Route::get('/chat/conversations', [ChatController::class, 'getConversations']);
    Route::get('/chat/conversation/{otherUserId}', [ChatController::class, 'getConversation']);
    Route::get('/chat/messages/{otherUserId}', [ChatController::class, 'getConversation']);
    Route::post('/chat/send', [ChatController::class, 'sendMessage']);
    Route::post('/chat/message', [ChatController::class, 'sendMessage']);
    Route::delete('/chat/conversation/{otherUserId}', [ChatController::class, 'destroy']);

    Route::get('/api/chat/conversations', [ChatController::class, 'getConversations']);
    Route::get('/api/chat/conversation/{otherUserId}', [ChatController::class, 'getConversation']);
    Route::get('/api/chat/messages/{otherUserId}', [ChatController::class, 'getConversation']);
    Route::post('/api/chat/send', [ChatController::class, 'sendMessage']);
    Route::post('/api/chat/message', [ChatController::class, 'sendMessage']);
    Route::delete('/api/chat/conversation/{otherUserId}', [ChatController::class, 'destroy']);

    // Reviews
    Route::post('/api/reviews', [ReviewController::class, 'store']);
    Route::get('/api/reviews/seller/{sellerId}', [ReviewController::class, 'getSellerReviews']);

    // Categories
    Route::get('/api/categories', [CategoryController::class, 'index']);

    // Refunds & Returns
    Route::post('/api/refunds', [RefundController::class, 'store']);
    Route::get('/api/refunds/customer', [RefundController::class, 'customerIndex']);
    Route::post('/api/returns', [ReturnRequestController::class, 'store']);
    Route::get('/api/returns', [ReturnRequestController::class, 'index']);
});

// Admin Routes
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/export-global-report', [AdminController::class, 'exportGlobalReport'])->name('admin.export');
    Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
    Route::match(['post', 'patch'], '/users/{id}/ban', [AdminController::class, 'banUser'])->name('admin.users.ban');
    Route::get('/users/{id}/ban', function() { return redirect()->route('admin.users'); });
    Route::match(['post', 'patch'], '/users/{id}/unban', [AdminController::class, 'unbanUser'])->name('admin.users.unban');
    Route::get('/users/{id}/unban', function() { return redirect()->route('admin.users'); });
    Route::delete('/users/{id}', [AdminController::class, 'deleteUser'])->name('admin.users.delete');
    Route::get('/sellers', [AdminController::class, 'sellers'])->name('admin.sellers');
    Route::match(['post', 'patch'], '/sellers/{id}/verify', [AdminController::class, 'verifySellerWeb'])->name('admin.sellers.verify');
    Route::match(['post', 'patch'], '/sellers/{id}/unverify', [AdminController::class, 'unverifySellerWeb'])->name('admin.sellers.unverify');
    Route::match(['post', 'patch'], '/sellers/{id}/suspend', [AdminController::class, 'suspendSeller'])->name('admin.sellers.suspend');
    Route::get('/sellers/{id}/suspend', function() { return redirect()->route('admin.sellers'); });
    Route::match(['post', 'patch'], '/sellers/{id}/unsuspend', [AdminController::class, 'unsuspendSeller'])->name('admin.sellers.unsuspend');
    Route::get('/sellers/{id}/unsuspend', function() { return redirect()->route('admin.sellers'); });
    Route::delete('/sellers/{id}', [AdminController::class, 'deleteSeller'])->name('admin.sellers.delete');
    Route::get('/products', [AdminController::class, 'products'])->name('admin.products');
    Route::match(['post', 'patch'], '/products/{id}/approve', [AdminController::class, 'approveProductWeb'])->name('admin.products.approve');
    Route::match(['post', 'patch'], '/products/{id}/reject', [AdminController::class, 'rejectProductWeb'])->name('admin.products.reject');
    Route::delete('/products/{id}', [AdminController::class, 'deleteProductWeb'])->name('admin.products.delete');

    // Categories
    Route::get('/categories', [AdminCategoryController::class, 'index'])->name('admin.categories.index');
    Route::post('/categories', [AdminCategoryController::class, 'store'])->name('admin.categories.store');
    Route::put('/categories/{id}', [AdminCategoryController::class, 'update'])->name('admin.categories.update');
    Route::delete('/categories/{id}', [AdminCategoryController::class, 'destroy'])->name('admin.categories.destroy');
    Route::get('/categories/{id}', function() { return redirect()->route('admin.categories.index'); });
    Route::post('/categories/initialize', [AdminCategoryController::class, 'initializeDefaults'])->name('admin.categories.initialize');

    // Hero Banners
    Route::get('/banners', [AdminBannerController::class, 'index'])->name('admin.banners.index');
    Route::get('/banners/search-destinations', [AdminBannerController::class, 'searchDestinations'])->name('admin.banners.search-destinations');
    Route::post('/banners/reorder', [AdminBannerController::class, 'reorder'])->name('admin.banners.reorder');
    Route::post('/banners', [AdminBannerController::class, 'store'])->name('admin.banners.store');
    Route::put('/banners/{id}', [AdminBannerController::class, 'update'])->name('admin.banners.update');
    Route::delete('/banners/{id}', [AdminBannerController::class, 'destroy'])->name('admin.banners.destroy');
    Route::patch('/banners/{id}/toggle', [AdminBannerController::class, 'toggleActive'])->name('admin.banners.toggle');
    Route::patch('/banners/{id}/approve', [AdminBannerController::class, 'approve'])->name('admin.banners.approve');
    Route::patch('/banners/{id}/reject', [AdminBannerController::class, 'reject'])->name('admin.banners.reject');
    
    // Reports
    Route::get('/reports', [AdminController::class, 'reports'])->name('admin.reports');
    Route::patch('/reports/{id}/resolve', [AdminController::class, 'resolveReport'])->name('admin.reports.resolve');
    Route::delete('/reports/{id}', [AdminController::class, 'deleteReport'])->name('admin.reports.delete');

    // Archive Hub
    Route::get('/archives', [AdminController::class, 'archives'])->name('admin.archives');
    Route::post('/archives/{id}/restore', [AdminController::class, 'restoreArchive'])->name('admin.archives.restore');
    Route::delete('/archives/{id}', [AdminController::class, 'purgeArchive'])->name('admin.archives.purge');

    // Admin Notifications
    Route::get('/notifications', [AdminController::class, 'notifications'])->name('admin.notifications.index');
    Route::post('/notifications/read-all', [AdminController::class, 'readAllNotifications'])->name('admin.notifications.read-all');

    // Settings Pages
    Route::get('/settings',    [AdminSettingsController::class, 'index'])->name('admin.settings');
    Route::post('/settings',   [AdminSettingsController::class, 'update'])->name('admin.settings.update');
    Route::get('/maintenance', [AdminSettingsController::class, 'maintenance'])->name('admin.maintenance');
    Route::post('/maintenance/toggle', [AdminSettingsController::class, 'toggleMaintenance'])->name('admin.maintenance.toggle');
    Route::get('/audit-logs',  [AdminSettingsController::class, 'auditLogs'])->name('admin.audit-logs');
    Route::get('/email-logs',  [AdminController::class, 'emailLogs'])->name('admin.email-logs');
    Route::get('/platform',    [AdminSettingsController::class, 'platform'])->name('admin.platform');
});

// Seller Routes
Route::middleware(['auth', 'seller'])->prefix('seller')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'sellerDashboard'])->name('seller.dashboard');
    Route::get('/analytics', [AnalyticsController::class, 'sellerAnalytics'])->name('seller.analytics');
    Route::get('/export-report', [DashboardController::class, 'exportSellerReport'])->name('seller.export');
    Route::get('/profile', [DashboardController::class, 'sellerProfile'])->name('seller.profile');
    Route::put('/profile', [DashboardController::class, 'updateSellerProfile'])->name('seller.profile.update');
    Route::get('/policies', [DashboardController::class, 'sellerPolicies'])->name('seller.policies.index');
    Route::put('/policies', [DashboardController::class, 'updateSellerPolicies'])->name('seller.policies.update');
    Route::post('/policies/ai-assist', [AiController::class, 'assistPolicy'])->name('seller.policies.ai');
    Route::get('/orders', [DashboardController::class, 'sellerOrders'])->name('seller.orders');
    Route::get('/customers', [DashboardController::class, 'sellerCustomers'])->name('seller.customers');
    Route::get('/commission', [DashboardController::class, 'sellerCommission'])->name('seller.commission');
    Route::post('/commission', [DashboardController::class, 'submitCommissionPayment'])->name('seller.commission.submit');
    Route::patch('/api/orders/{id}/status', [OrderController::class, 'updateOrderStatus']);
    Route::get('/messages', [ChatController::class, 'sellerChatView'])->name('seller.messages');
    Route::get('/products', [ProductManagementController::class, 'index'])->name('seller.products.index');
    Route::get('/products/create', [ProductManagementController::class, 'create'])->name('seller.products.create');
    Route::post('/products', [ProductManagementController::class, 'store'])->name('seller.products.store');
    Route::get('/products/{id}/edit', [ProductManagementController::class, 'edit'])->name('seller.products.edit');
    Route::put('/products/{id}', [ProductManagementController::class, 'update'])->name('seller.products.update');
    Route::delete('/products/{id}', [ProductManagementController::class, 'destroy'])->name('seller.products.destroy');
    Route::post('/size-guides', [ProductManagementController::class, 'updateSizeGuides'])->name('seller.sizeguides.update');
    Route::delete('/size-guides/{targetGroup}', [ProductManagementController::class, 'deleteSizeGuide'])->name('seller.sizeguides.delete');

    // Seller Reviews Reply
    Route::post('/reviews/{id}/reply', [ReviewController::class, 'sellerReply'])->name('seller.reviews.reply');

    // Seller Notifications
    Route::get('/notifications', [DashboardController::class, 'notifications'])->name('seller.notifications.index');
    Route::post('/notifications/read-all', [DashboardController::class, 'readAllNotifications'])->name('seller.notifications.read-all');
});

// ─── Super Admin Routes ────────────────────────────────────────────────────
Route::get('/superadmin/login',  [SuperAdminController::class, 'showLogin'])->name('superadmin.login');
Route::post('/superadmin/login', [SuperAdminController::class, 'login'])->name('superadmin.login.submit');
Route::match(['get', 'post'], '/superadmin/logout', [SuperAdminController::class, 'logout'])->name('superadmin.logout');

Route::middleware(['auth', 'superadmin'])->prefix('superadmin')->group(function () {
    Route::get('/dashboard',    [SuperAdminController::class, 'dashboard'])->name('superadmin.dashboard');
    Route::get('/commissions',  [SuperAdminController::class, 'commissions'])->name('superadmin.commissions');
    Route::get('/payment-settings', [SuperAdminController::class, 'paymentSettings'])->name('superadmin.payment-settings');
    Route::post('/payment-settings', [SuperAdminController::class, 'updatePaymentSettings'])->name('superadmin.payment-settings.update');
    Route::post('/commission-rate', [SuperAdminController::class, 'updateCommissionRate'])->name('superadmin.commission-rate');
    Route::patch('/commissions/{sellerId}/mark-paid', [SuperAdminController::class, 'markPaid'])->name('superadmin.commissions.mark-paid');
    Route::patch('/shops/{id}/freeze',   [SuperAdminController::class, 'freezeShop'])->name('superadmin.shops.freeze');
    Route::patch('/shops/{id}/unfreeze', [SuperAdminController::class, 'unfreezeShop'])->name('superadmin.shops.unfreeze');

    // Customer & Seller Management
    Route::get('/sellers', [SuperAdminController::class, 'sellers'])->name('superadmin.sellers');
    Route::patch('/sellers/{id}/verify', [SuperAdminController::class, 'verifySeller'])->name('superadmin.sellers.verify');
    Route::patch('/sellers/{id}/unverify', [SuperAdminController::class, 'unverifySeller'])->name('superadmin.sellers.unverify');
    Route::get('/customers', [SuperAdminController::class, 'customers'])->name('superadmin.customers');
    Route::match(['post', 'patch'], '/customers/{id}/ban', [SuperAdminController::class, 'banCustomer'])->name('superadmin.customers.ban');
    Route::match(['post', 'patch'], '/customers/{id}/unban', [SuperAdminController::class, 'unbanCustomer'])->name('superadmin.customers.unban');

    // Subscriptions
    Route::get('/subscriptions', [SuperAdminController::class, 'subscriptions'])->name('superadmin.subscriptions.index');
    Route::post('/subscriptions/{id}/approve', [SuperAdminController::class, 'approveSubscription'])->name('superadmin.subscriptions.approve');
    Route::post('/subscriptions/{id}/reject', [SuperAdminController::class, 'rejectSubscription'])->name('superadmin.subscriptions.reject');
    Route::post('/subscriptions/settings', [SuperAdminController::class, 'updateSubscriptionSettings'])->name('superadmin.subscriptions.settings');

    // Categories
    Route::get('/categories', [SuperAdminController::class, 'categories'])->name('superadmin.categories.index');
    Route::post('/categories', [AdminCategoryController::class, 'store'])->name('superadmin.categories.store');
    Route::put('/categories/{id}', [AdminCategoryController::class, 'update'])->name('superadmin.categories.update');
    Route::delete('/categories/{id}', [AdminCategoryController::class, 'destroy'])->name('superadmin.categories.destroy');
    Route::post('/categories/initialize', [AdminCategoryController::class, 'initializeDefaults'])->name('superadmin.categories.initialize');

    // Product Moderation
    Route::get('/products', [SuperAdminController::class, 'products'])->name('superadmin.products');
    Route::match(['get', 'post'], '/products/{id}/approve', [SuperAdminController::class, 'approveProductWeb'])->name('superadmin.products.approve');
    Route::match(['get', 'post'], '/products/{id}/reject', [SuperAdminController::class, 'rejectProductWeb'])->name('superadmin.products.reject');
    Route::delete('/products/{id}', [SuperAdminController::class, 'deleteProductWeb'])->name('superadmin.products.delete');

    // Hero Banners & Promotions
    Route::get('/banners', [SuperAdminController::class, 'banners'])->name('superadmin.banners.index');
    Route::get('/banners/search-destinations', [AdminBannerController::class, 'searchDestinations'])->name('superadmin.banners.search-destinations');
    Route::post('/banners/reorder', [AdminBannerController::class, 'reorder'])->name('superadmin.banners.reorder');
    Route::post('/banners', [AdminBannerController::class, 'store'])->name('superadmin.banners.store');
    Route::put('/banners/{id}', [AdminBannerController::class, 'update'])->name('superadmin.banners.update');
    Route::delete('/banners/{id}', [AdminBannerController::class, 'destroy'])->name('superadmin.banners.destroy');
    Route::patch('/banners/{id}/toggle', [AdminBannerController::class, 'toggleActive'])->name('superadmin.banners.toggle');
    Route::patch('/banners/{id}/approve', [AdminBannerController::class, 'approve'])->name('superadmin.banners.approve');
    Route::patch('/banners/{id}/reject', [AdminBannerController::class, 'reject'])->name('superadmin.banners.reject');

    // Archive Vault
    Route::get('/archives', [SuperAdminController::class, 'archives'])->name('superadmin.archives');
    Route::post('/archives/{id}/restore', [SuperAdminController::class, 'restoreArchive'])->name('superadmin.archives.restore');
    Route::delete('/archives/{id}', [SuperAdminController::class, 'purgeArchive'])->name('superadmin.archives.purge');

    // Developer & System Tools
    Route::get('/maintenance', [SuperAdminController::class, 'maintenance'])->name('superadmin.maintenance');
    Route::post('/maintenance/toggle', [SuperAdminController::class, 'toggleMaintenance'])->name('superadmin.maintenance.toggle');
    Route::post('/maintenance/clear-cache', [SuperAdminController::class, 'clearCache'])->name('superadmin.maintenance.clear-cache');
    Route::get('/audit-logs', [SuperAdminController::class, 'auditLogs'])->name('superadmin.audit-logs');
    Route::get('/error-logs', [SuperAdminController::class, 'errorLogs'])->name('superadmin.error-logs');
    Route::post('/error-logs/clear', [SuperAdminController::class, 'clearErrorLogs'])->name('superadmin.error-logs.clear');
    Route::get('/platform', [SuperAdminController::class, 'platform'])->name('superadmin.platform');
});

// ─── Storage & Upload Fallback Routes ──────────────────────────────────────────
// Serves uploaded files directly if storage symlink is missing or broken on Hostinger
Route::get('/storage/{path}', function ($path) {
    $cleanPath = ltrim(str_replace('\\', '/', $path), '/');

    $candidates = [
        storage_path('app/public/' . $cleanPath),
        storage_path('app/public/profiles/' . $cleanPath),
        storage_path('app/public/products/' . $cleanPath),
        public_path('storage/' . $cleanPath),
        public_path('storage/profiles/' . $cleanPath),
        public_path('uploads/' . $cleanPath),
        public_path('uploads/products/' . $cleanPath),
        public_path('uploads/profiles/' . $cleanPath),
        base_path('uploads/' . $cleanPath),
    ];

    foreach ($candidates as $filePath) {
        if (file_exists($filePath) && is_file($filePath)) {
            return response()->file($filePath);
        }
    }

    if (str_contains($cleanPath, 'profiles')) {
        $defaultAvatar = public_path('images/default-avatar.png');
        if (file_exists($defaultAvatar)) {
            return response()->file($defaultAvatar);
        }
    }

    $defaultImg = public_path('uploads/products/default.jpg');
    if (file_exists($defaultImg)) {
        return response()->file($defaultImg);
    }

    abort(404);
})->where('path', '.*');

Route::get('/uploads/{path}', function ($path) {
    $cleanPath = ltrim(str_replace('\\', '/', $path), '/');

    $candidates = [
        public_path('uploads/' . $cleanPath),
        storage_path('app/public/' . $cleanPath),
        storage_path('app/public/uploads/' . $cleanPath),
        public_path('storage/' . $cleanPath),
        base_path('uploads/' . $cleanPath),
    ];

    foreach ($candidates as $filePath) {
        if (file_exists($filePath) && is_file($filePath)) {
            return response()->file($filePath);
        }
    }

    $defaultImg = public_path('uploads/products/default.jpg');
    if (file_exists($defaultImg)) {
        return response()->file($defaultImg);
    }

    abort(404);
})->where('path', '.*');

// Web AI Service Routes
Route::prefix('ai')->group(function () {
    Route::post('/stylist/chat', [AiController::class, 'chatStylist'])->name('ai.stylist');
    Route::post('/sizing/recommend', [AiController::class, 'recommendSize'])->name('ai.sizing');
    Route::post('/seller/generate-description', [AiController::class, 'generateSellerListing'])->name('ai.seller.description');
    Route::post('/security/password-check', [AiController::class, 'analyzePassword'])->name('ai.security.password');
    Route::post('/payment-reference/check', [AiController::class, 'checkPaymentReference'])->name('ai.payment.check');
    Route::post('/receipt/verify', [AiController::class, 'verifyReceipt'])->name('ai.receipt.verify');
});

// Web Upload & Report Routes (For Session-Authenticated Users)
Route::middleware(['auth'])->group(function () {
    Route::post('/api/v1/upload', [\App\Http\Controllers\UploadController::class, 'uploadImage']);
    Route::post('/api/v1/reports', [\App\Http\Controllers\ReportController::class, 'createReport']);
    Route::post('/upload', [\App\Http\Controllers\UploadController::class, 'uploadImage']);
    Route::post('/reports', [\App\Http\Controllers\ReportController::class, 'createReport']);
});

