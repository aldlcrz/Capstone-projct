<?php

use App\Http\Controllers\WebController;
use App\Http\Controllers\WebAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [WebController::class, 'index'])->name('home');
Route::get('/products/{id}', [WebController::class, 'productDetails'])->name('products.show');
Route::get('/shops/{id}', [WebController::class, 'sellerShop'])->name('shops.show');

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
    Route::get('/cart', [\App\Http\Controllers\CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add', [\App\Http\Controllers\CartController::class, 'add'])->name('cart.add');
    Route::post('/cart/update', [\App\Http\Controllers\CartController::class, 'update'])->name('cart.update');
    Route::post('/cart/remove-selected', [\App\Http\Controllers\CartController::class, 'removeSelected'])->name('cart.remove-selected');
    Route::post('/cart/clear', [\App\Http\Controllers\CartController::class, 'clear'])->name('cart.clear');
    Route::post('/cart/remove/{key}', [\App\Http\Controllers\CartController::class, 'remove'])->name('cart.remove')->where('key', '.*');

    // Checkout
    Route::get('/checkout', [\App\Http\Controllers\CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [\App\Http\Controllers\CheckoutController::class, 'store'])->name('checkout.store');
    Route::post('/checkout/selected', [\App\Http\Controllers\CheckoutController::class, 'fromSelected'])->name('checkout.selected');

    // Orders
    Route::get('/orders/my-orders', [WebController::class, 'orders'])->name('orders');
    Route::get('/orders/{id}', [WebController::class, 'orderDetail'])->name('orders.show');
    Route::patch('/orders/{id}/confirm', [\App\Http\Controllers\OrderController::class, 'confirmReceived'])->name('orders.confirm');


    // Notifications
    Route::get('/notifications', [WebController::class, 'notifications'])->name('notifications.index');
    Route::post('/notifications/read-all', [WebController::class, 'readAllNotifications'])->name('notifications.read-all');

    // Wishlist
    Route::get('/wishlist', [\App\Http\Controllers\WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/toggle', [\App\Http\Controllers\WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::get('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'readAndRedirect'])->name('notifications.read-and-redirect');

    Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');

    // Address Management
    Route::get('/api/addresses', [\App\Http\Controllers\AddressController::class, 'index']);
    Route::post('/api/addresses', [\App\Http\Controllers\AddressController::class, 'store']);
    Route::put('/api/addresses/{id}', [\App\Http\Controllers\AddressController::class, 'update']);
    Route::delete('/api/addresses/{id}', [\App\Http\Controllers\AddressController::class, 'destroy']);
    Route::patch('/api/addresses/{id}/set-default', [\App\Http\Controllers\AddressController::class, 'setDefault']);

    // Chat
    Route::get('/api/chat/conversations', [\App\Http\Controllers\ChatController::class, 'getConversations']);
    Route::get('/api/chat/conversation/{otherUserId}', [\App\Http\Controllers\ChatController::class, 'getConversation']);
    Route::post('/api/chat/message', [\App\Http\Controllers\ChatController::class, 'sendMessage']);
    Route::delete('/api/chat/conversation/{otherUserId}', [\App\Http\Controllers\ChatController::class, 'destroy']);

    // Reviews
    Route::post('/api/reviews', [\App\Http\Controllers\ReviewController::class, 'store']);
    Route::get('/api/reviews/seller/{sellerId}', [\App\Http\Controllers\ReviewController::class, 'getSellerReviews']);

    // Categories
    Route::get('/api/categories', [\App\Http\Controllers\CategoryController::class, 'index']);

    // Refunds & Returns
    Route::post('/api/refunds', [\App\Http\Controllers\RefundController::class, 'store']);
    Route::get('/api/refunds/customer', [\App\Http\Controllers\RefundController::class, 'customerIndex']);
    Route::post('/api/returns', [\App\Http\Controllers\ReturnRequestController::class, 'store']);
    Route::get('/api/returns', [\App\Http\Controllers\ReturnRequestController::class, 'index']);
});

// Admin Routes
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/export-global-report', [\App\Http\Controllers\AdminController::class, 'exportGlobalReport'])->name('admin.export');
    Route::get('/users', [\App\Http\Controllers\AdminController::class, 'users'])->name('admin.users');
    Route::patch('/users/{id}/ban', [\App\Http\Controllers\AdminController::class, 'banUser'])->name('admin.users.ban');
    Route::patch('/users/{id}/unban', [\App\Http\Controllers\AdminController::class, 'unbanUser'])->name('admin.users.unban');
    Route::delete('/users/{id}', [\App\Http\Controllers\AdminController::class, 'deleteUser'])->name('admin.users.delete');
    Route::get('/sellers', [\App\Http\Controllers\AdminController::class, 'sellers'])->name('admin.sellers');
    Route::patch('/sellers/{id}/verify', [\App\Http\Controllers\AdminController::class, 'verifySellerWeb'])->name('admin.sellers.verify');
    Route::patch('/sellers/{id}/suspend', [\App\Http\Controllers\AdminController::class, 'suspendSeller'])->name('admin.sellers.suspend');
    Route::get('/products', [\App\Http\Controllers\AdminController::class, 'products'])->name('admin.products');
    Route::patch('/products/{id}/approve', [\App\Http\Controllers\AdminController::class, 'approveProductWeb'])->name('admin.products.approve');
    Route::patch('/products/{id}/reject', [\App\Http\Controllers\AdminController::class, 'rejectProductWeb'])->name('admin.products.reject');

    // Categories
    Route::get('/categories', [\App\Http\Controllers\AdminCategoryController::class, 'index'])->name('admin.categories.index');
    Route::post('/categories', [\App\Http\Controllers\AdminCategoryController::class, 'store'])->name('admin.categories.store');
    Route::put('/categories/{id}', [\App\Http\Controllers\AdminCategoryController::class, 'update'])->name('admin.categories.update');
    Route::delete('/categories/{id}', [\App\Http\Controllers\AdminCategoryController::class, 'destroy'])->name('admin.categories.destroy');
    Route::get('/categories/{id}', function() { return redirect()->route('admin.categories.index'); });
    Route::post('/categories/initialize', [\App\Http\Controllers\AdminCategoryController::class, 'initializeDefaults'])->name('admin.categories.initialize');

    // Hero Banners
    Route::get('/banners', [\App\Http\Controllers\AdminBannerController::class, 'index'])->name('admin.banners.index');
    Route::post('/banners', [\App\Http\Controllers\AdminBannerController::class, 'store'])->name('admin.banners.store');
    Route::put('/banners/{id}', [\App\Http\Controllers\AdminBannerController::class, 'update'])->name('admin.banners.update');
    Route::delete('/banners/{id}', [\App\Http\Controllers\AdminBannerController::class, 'destroy'])->name('admin.banners.destroy');
    Route::patch('/banners/{id}/toggle', [\App\Http\Controllers\AdminBannerController::class, 'toggleActive'])->name('admin.banners.toggle');
    Route::patch('/banners/{id}/approve', [\App\Http\Controllers\AdminBannerController::class, 'approve'])->name('admin.banners.approve');
    Route::patch('/banners/{id}/reject', [\App\Http\Controllers\AdminBannerController::class, 'reject'])->name('admin.banners.reject');
    
    // Reports
    Route::get('/reports', [\App\Http\Controllers\AdminController::class, 'reports'])->name('admin.reports');
    Route::patch('/reports/{id}/resolve', [\App\Http\Controllers\AdminController::class, 'resolveReport'])->name('admin.reports.resolve');
    Route::delete('/reports/{id}', [\App\Http\Controllers\AdminController::class, 'deleteReport'])->name('admin.reports.delete');

    // Admin Notifications
    Route::get('/notifications', [\App\Http\Controllers\AdminController::class, 'notifications'])->name('admin.notifications.index');
    Route::post('/notifications/read-all', [\App\Http\Controllers\AdminController::class, 'readAllNotifications'])->name('admin.notifications.read-all');

    // Subscription Management (Feature Removed)
    // Route::get('/subscriptions', [\App\Http\Controllers\AdminSubscriptionController::class, 'index'])->name('admin.subscriptions.index');
    // Route::patch('/subscriptions/{id}/approve', [\App\Http\Controllers\AdminSubscriptionController::class, 'approve'])->name('admin.subscriptions.approve');
    // Route::patch('/subscriptions/{id}/reject', [\App\Http\Controllers\AdminSubscriptionController::class, 'reject'])->name('admin.subscriptions.reject');
    // Route::post('/subscriptions/settings', [\App\Http\Controllers\AdminSubscriptionController::class, 'updateSettings'])->name('admin.subscriptions.settings.update');

    // Settings Pages
    Route::get('/settings',    [\App\Http\Controllers\AdminSettingsController::class, 'index'])->name('admin.settings');
    Route::post('/settings',   [\App\Http\Controllers\AdminSettingsController::class, 'update'])->name('admin.settings.update');
    Route::get('/maintenance', [\App\Http\Controllers\AdminSettingsController::class, 'maintenance'])->name('admin.maintenance');
    Route::post('/maintenance/toggle', [\App\Http\Controllers\AdminSettingsController::class, 'toggleMaintenance'])->name('admin.maintenance.toggle');
    Route::get('/audit-logs',  [\App\Http\Controllers\AdminSettingsController::class, 'auditLogs'])->name('admin.audit-logs');
    Route::get('/email-logs',  [\App\Http\Controllers\AdminController::class, 'emailLogs'])->name('admin.email-logs');
    Route::get('/platform',    [\App\Http\Controllers\AdminSettingsController::class, 'platform'])->name('admin.platform');
});

// Seller Routes
Route::middleware(['auth', 'seller'])->prefix('seller')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'sellerDashboard'])->name('seller.dashboard');
    Route::get('/export-report', [\App\Http\Controllers\DashboardController::class, 'exportSellerReport'])->name('seller.export');
    Route::get('/profile', [\App\Http\Controllers\DashboardController::class, 'sellerProfile'])->name('seller.profile');
    Route::put('/profile', [\App\Http\Controllers\DashboardController::class, 'updateSellerProfile'])->name('seller.profile.update');
    Route::get('/orders', [\App\Http\Controllers\DashboardController::class, 'sellerOrders'])->name('seller.orders');
    Route::get('/customers', [\App\Http\Controllers\DashboardController::class, 'sellerCustomers'])->name('seller.customers');
    Route::get('/commission', [\App\Http\Controllers\DashboardController::class, 'sellerCommission'])->name('seller.commission');
    Route::post('/commission', [\App\Http\Controllers\DashboardController::class, 'submitCommissionPayment'])->name('seller.commission.submit');
    Route::patch('/api/orders/{id}/status', [\App\Http\Controllers\OrderController::class, 'updateOrderStatus']);
    Route::get('/messages', [\App\Http\Controllers\ChatController::class, 'sellerChatView'])->name('seller.messages');
    Route::get('/products', [\App\Http\Controllers\ProductManagementController::class, 'index'])->name('seller.products.index');
    Route::get('/products/create', [\App\Http\Controllers\ProductManagementController::class, 'create'])->name('seller.products.create');
    Route::post('/products', [\App\Http\Controllers\ProductManagementController::class, 'store'])->name('seller.products.store');
    Route::get('/products/{id}/edit', [\App\Http\Controllers\ProductManagementController::class, 'edit'])->name('seller.products.edit');
    Route::put('/products/{id}', [\App\Http\Controllers\ProductManagementController::class, 'update'])->name('seller.products.update');
    Route::delete('/products/{id}', [\App\Http\Controllers\ProductManagementController::class, 'destroy'])->name('seller.products.destroy');

    // Seller Notifications
    Route::get('/notifications', [\App\Http\Controllers\DashboardController::class, 'notifications'])->name('seller.notifications.index');
    Route::post('/notifications/read-all', [\App\Http\Controllers\DashboardController::class, 'readAllNotifications'])->name('seller.notifications.read-all');

    // Subscription Upgrade (Disabled)
    // Route::get('/subscription', [\App\Http\Controllers\SellerSubscriptionController::class, 'index'])->name('seller.subscription.index');
    // Route::post('/subscription/subscribe', [\App\Http\Controllers\SellerSubscriptionController::class, 'subscribe'])->name('seller.subscription.subscribe');

    // Seller Hero Banners (Disabled)
    // Route::get('/banners', [\App\Http\Controllers\SellerBannerController::class, 'index'])->name('seller.banners.index');
    // Route::post('/banners', [\App\Http\Controllers\SellerBannerController::class, 'store'])->name('seller.banners.store');
    // Route::delete('/banners/{id}', [\App\Http\Controllers\SellerBannerController::class, 'destroy'])->name('seller.banners.destroy');
});

// ─── Super Admin Routes ────────────────────────────────────────────────────
Route::get('/superadmin/login',  [\App\Http\Controllers\SuperAdminController::class, 'showLogin'])->name('superadmin.login');
Route::post('/superadmin/login', [\App\Http\Controllers\SuperAdminController::class, 'login'])->name('superadmin.login.submit');
Route::post('/superadmin/logout',[\App\Http\Controllers\SuperAdminController::class, 'logout'])->name('superadmin.logout');

Route::middleware(['auth', 'superadmin'])->prefix('superadmin')->group(function () {
    Route::get('/dashboard',    [\App\Http\Controllers\SuperAdminController::class, 'dashboard'])->name('superadmin.dashboard');
    Route::get('/commissions',  [\App\Http\Controllers\SuperAdminController::class, 'commissions'])->name('superadmin.commissions');
    Route::get('/payment-settings', [\App\Http\Controllers\SuperAdminController::class, 'paymentSettings'])->name('superadmin.payment-settings');
    Route::post('/payment-settings', [\App\Http\Controllers\SuperAdminController::class, 'updatePaymentSettings'])->name('superadmin.payment-settings.update');
    Route::post('/commission-rate', [\App\Http\Controllers\SuperAdminController::class, 'updateCommissionRate'])->name('superadmin.commission-rate');
    Route::patch('/commissions/{sellerId}/mark-paid', [\App\Http\Controllers\SuperAdminController::class, 'markPaid'])->name('superadmin.commissions.mark-paid');
    Route::patch('/shops/{id}/freeze',   [\App\Http\Controllers\SuperAdminController::class, 'freezeShop'])->name('superadmin.shops.freeze');
    Route::patch('/shops/{id}/unfreeze', [\App\Http\Controllers\SuperAdminController::class, 'unfreezeShop'])->name('superadmin.shops.unfreeze');
});

// ─── Storage & Upload Fallback Routes ──────────────────────────────────────────
// Serves uploaded files directly if storage symlink is missing or broken on Hostinger
Route::get('/storage/{path}', function ($path) {
    $cleanPath = ltrim(str_replace('\\', '/', $path), '/');

    $candidates = [
        storage_path('app/public/' . $cleanPath),
        storage_path('app/public/products/' . $cleanPath),
        public_path('storage/' . $cleanPath),
        public_path('uploads/' . $cleanPath),
        public_path('uploads/products/' . $cleanPath),
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


