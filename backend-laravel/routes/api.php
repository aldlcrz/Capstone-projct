<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UploadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    
    // Auth Routes
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/google-login', [AuthController::class, 'googleLogin']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
        
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/profile', [AuthController::class, 'getProfile']);
        });
    });

    // Product Routes
    Route::get('/products', [ProductController::class, 'getAllProducts']);
    Route::get('/products/{id}', [ProductController::class, 'getProductById']);
    Route::post('/products/{id}/funnel-event', [ProductController::class, 'trackProductFunnelEvent']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/seller/products', [ProductController::class, 'getSellerProducts']);
        Route::post('/products', [ProductController::class, 'createProduct']);
        Route::put('/products/{id}', [ProductController::class, 'updateProduct']);
        Route::delete('/products/{id}', [ProductController::class, 'deleteProduct']);
        Route::get('/seller/stats', [ProductController::class, 'getSellerStats']);
    });

    // Order Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/orders', [OrderController::class, 'getMyOrders']);
        Route::get('/seller/orders', [OrderController::class, 'getSellerOrders']);
        Route::post('/orders', [OrderController::class, 'createOrder']);
        Route::patch('/orders/{id}/status', [OrderController::class, 'updateOrderStatus']);
        Route::post('/orders/{id}/cancel', [OrderController::class, 'cancelOrder']);
        Route::post('/orders/{id}/approve-cancel', [OrderController::class, 'approveCancellation']);
        Route::post('/orders/{id}/reject-cancel', [OrderController::class, 'rejectCancellation']);
        Route::get('/seller/report/export', [OrderController::class, 'exportSellerReport']);
    });

    // Address Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/addresses', [AddressController::class, 'getAddresses']);
        Route::post('/addresses', [AddressController::class, 'createAddress']);
        Route::put('/addresses/{id}', [AddressController::class, 'updateAddress']);
        Route::delete('/addresses/{id}', [AddressController::class, 'deleteAddress']);
        Route::patch('/addresses/{id}/set-default', [AddressController::class, 'setDefaultAddress']);
    });

    // Category Routes
    Route::get('/categories', [CategoryController::class, 'getCategories']);

    // Chat Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/chat/conversations', [ChatController::class, 'getConversations']);
        Route::get('/chat/conversation/{otherUserId}', [ChatController::class, 'getConversation']);
        Route::post('/chat/message', [ChatController::class, 'sendMessage']);
        Route::patch('/chat/read/{otherUserId}', [ChatController::class, 'markAsRead']);
    });

    // User Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user/profile', [UserController::class, 'getProfile']);
        Route::put('/user/profile', [UserController::class, 'updateProfile']);
        Route::post('/user/change-password', [UserController::class, 'changePassword']);
        Route::get('/user/seller/{id}', [UserController::class, 'getSellerInfo']);
        Route::post('/user/follow/{id}', [UserController::class, 'toggleFollow']);
        Route::get('/user/stats', [UserController::class, 'getCustomerStats']);
    });

    // Analytics Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/analytics/seller', [AnalyticsController::class, 'getSellerAnalytics']);
        Route::get('/dashboard/summary', [DashboardController::class, 'getSellerDashboardSummary']);
    });

    // Review Routes
    Route::get('/reviews/product/{productId}', [ReviewController::class, 'getProductReviews']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/reviews', [ReviewController::class, 'createReview']);
        Route::get('/reviews/seller', [ReviewController::class, 'getSellerReviews']);
    });

    // Admin Routes
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/admin/stats', [AdminController::class, 'getGlobalStats']);
        Route::get('/admin/users', [AdminController::class, 'getAllUsers']);
        Route::patch('/admin/users/{id}/verify', [AdminController::class, 'verifySeller']);
        Route::post('/admin/users/{id}/reject', [AdminController::class, 'rejectSeller']);
        Route::post('/admin/users/{id}/block', [AdminController::class, 'blockUser']);
        Route::post('/admin/users/{id}/freeze', [AdminController::class, 'freezeUser']);
        Route::post('/admin/users/{id}/unfreeze', [AdminController::class, 'unfreezeUser']);
        
        Route::get('/admin/products/pending', [AdminController::class, 'getPendingProducts']);
        Route::patch('/admin/products/{id}/approve', [AdminController::class, 'approveProduct']);
        Route::post('/admin/products/{id}/reject', [AdminController::class, 'rejectProduct']);
        
        Route::get('/admin/settings', [AdminController::class, 'getSettings']);
        Route::post('/admin/settings', [AdminController::class, 'updateSettings']);
        Route::get('/admin/report/export', [AdminController::class, 'exportGlobalReport']);
    });

    // Upload Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/upload', [UploadController::class, 'uploadImage']);
    });

    // Report Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/reports', [ReportController::class, 'createReport']);
    });

    // Refund Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/refunds', [RefundController::class, 'createRefundRequest']);
        Route::get('/refunds/seller', [RefundController::class, 'getSellerRefundRequests']);
        Route::patch('/refunds/{id}/status', [RefundController::class, 'updateRefundStatus']);
    });

});
