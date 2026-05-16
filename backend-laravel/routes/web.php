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

// Auth Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
    Route::get('/register', [WebAuthController::class, 'showRegister'])->name('register');
    Route::get('/seller/register', [WebAuthController::class, 'showSellerRegister'])->name('seller.register');
    Route::post('/seller/register', [WebAuthController::class, 'sellerRegister'])->name('seller.register.submit');
    Route::get('/forgot-password', function() { return view('auth.forgot-password'); })->name('password.request');
    Route::post('/forgot-password', [WebAuthController::class, 'forgotPassword'])->name('password.email');
    Route::post('/login', [WebAuthController::class, 'login']);
    Route::post('/register', [WebAuthController::class, 'register']);
    Route::post('/auth/google', [WebAuthController::class, 'handleGoogleLogin'])->name('auth.google');
});

Route::middleware('auth')->group(function () {
    // Profile
    Route::get('/profile', [WebAuthController::class, 'profile'])->name('profile');
    Route::post('/profile', [WebAuthController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile', [WebAuthController::class, 'updateProfile']);

    // Cart
    Route::get('/cart', [\App\Http\Controllers\CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add', [\App\Http\Controllers\CartController::class, 'add'])->name('cart.add');
    Route::post('/cart/update', [\App\Http\Controllers\CartController::class, 'update'])->name('cart.update');
    Route::post('/cart/remove/{key}', [\App\Http\Controllers\CartController::class, 'remove'])->name('cart.remove');

    // Checkout
    Route::get('/checkout', [\App\Http\Controllers\CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [\App\Http\Controllers\CheckoutController::class, 'store'])->name('checkout.store');

    // Orders
    Route::get('/orders/my-orders', [WebController::class, 'orders'])->name('orders');
    Route::get('/orders/{id}', [WebController::class, 'orderDetail'])->name('orders.show');
    Route::patch('/orders/{id}/cancel', [\App\Http\Controllers\OrderController::class, 'cancelByCustomer'])->name('orders.cancel');
    Route::patch('/orders/{id}/confirm', [\App\Http\Controllers\OrderController::class, 'confirmReceived'])->name('orders.confirm');

    // Wishlist
    Route::get('/wishlist', [\App\Http\Controllers\WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/toggle', [\App\Http\Controllers\WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::delete('/wishlist/{id}', [\App\Http\Controllers\WishlistController::class, 'remove'])->name('wishlist.remove');

    // Notifications
    Route::get('/notifications', [WebController::class, 'notifications'])->name('notifications.index');

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
    
    // Reports
    Route::get('/reports', [\App\Http\Controllers\AdminController::class, 'reports'])->name('admin.reports');
    Route::patch('/reports/{id}/resolve', [\App\Http\Controllers\AdminController::class, 'resolveReport'])->name('admin.reports.resolve');
    Route::delete('/reports/{id}', [\App\Http\Controllers\AdminController::class, 'deleteReport'])->name('admin.reports.delete');
});

// Seller Routes
Route::middleware(['auth', 'seller'])->prefix('seller')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'sellerDashboard'])->name('seller.dashboard');
    Route::get('/profile', [\App\Http\Controllers\DashboardController::class, 'sellerProfile'])->name('seller.profile');
    Route::put('/profile', [\App\Http\Controllers\DashboardController::class, 'updateSellerProfile'])->name('seller.profile.update');
    Route::get('/orders', [\App\Http\Controllers\DashboardController::class, 'sellerOrders'])->name('seller.orders');
    Route::patch('/api/orders/{id}/status', [\App\Http\Controllers\OrderController::class, 'updateOrderStatus']);
    Route::get('/products', [\App\Http\Controllers\ProductManagementController::class, 'index'])->name('seller.products.index');
    Route::get('/products/create', [\App\Http\Controllers\ProductManagementController::class, 'create'])->name('seller.products.create');
    Route::post('/products', [\App\Http\Controllers\ProductManagementController::class, 'store'])->name('seller.products.store');
    Route::get('/products/{id}/edit', [\App\Http\Controllers\ProductManagementController::class, 'edit'])->name('seller.products.edit');
    Route::put('/products/{id}', [\App\Http\Controllers\ProductManagementController::class, 'update'])->name('seller.products.update');
    Route::delete('/products/{id}', [\App\Http\Controllers\ProductManagementController::class, 'destroy'])->name('seller.products.destroy');
});
