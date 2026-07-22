<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\SuperAdminController;

Route::prefix('v1')->group(function () {
    
    // Health Check
    Route::get('/health', function () {
        return response()->json(['status' => 'OK', 'timestamp' => now()->toIso8601String()]);
    });

    // 1. Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
        Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
        Route::post('/google-login', [AuthController::class, 'googleLogin']);
        Route::post('/logout', [AuthController::class, 'logout'])->middleware('jwt.auth');
    });

    // 2. Categories routes
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'getCategories']);
        Route::post('/', [CategoryController::class, 'createCategory'])->middleware('jwt.auth:admin');
        Route::put('/{id}', [CategoryController::class, 'updateCategory'])->middleware('jwt.auth:admin');
        Route::delete('/{id}', [CategoryController::class, 'deleteCategory'])->middleware('jwt.auth:admin');
    });

    // 3. Address routes
    Route::prefix('addresses')->middleware('jwt.auth')->group(function () {
        Route::get('/', [AddressController::class, 'getAddresses']);
        Route::post('/', [AddressController::class, 'createAddress']);
        Route::put('/{id}', [AddressController::class, 'updateAddress']);
        Route::delete('/{id}', [AddressController::class, 'deleteAddress']);
        Route::patch('/{id}/set-default', [AddressController::class, 'setDefaultAddress']);
    });

    // 4. Product routes
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'getAllProducts']);
        Route::get('/seller', [ProductController::class, 'getSellerProducts'])->middleware('jwt.auth:seller,admin');
        Route::get('/seller-stats', [ProductController::class, 'getSellerStats'])->middleware('jwt.auth:seller,admin');
        Route::get('/stats', [ProductController::class, 'getSellerStats'])->middleware('jwt.auth:seller,admin');
        Route::get('/{id}', [ProductController::class, 'getProductById']);
        Route::post('/', [ProductController::class, 'createProduct'])->middleware('jwt.auth:seller');
        Route::post('/{id}/funnel-event', [ProductController::class, 'trackProductFunnelEvent']);
        Route::put('/{id}', [ProductController::class, 'updateProduct'])->middleware('jwt.auth:seller,admin');
        Route::delete('/{id}', [ProductController::class, 'deleteProduct'])->middleware('jwt.auth:seller,admin');
        Route::post('/{id}/reviews', [ReviewController::class, 'createReview'])->middleware('jwt.auth:customer');
    });

    // 5. Order routes
    Route::prefix('orders')->middleware('jwt.auth')->group(function () {
        Route::post('/', [OrderController::class, 'createOrder']);
        Route::get('/my-orders', [OrderController::class, 'getOrders']);
        Route::get('/seller', [OrderController::class, 'getOrders'])->middleware('jwt.auth:seller,admin');
        Route::get('/seller-orders', [OrderController::class, 'getOrders'])->middleware('jwt.auth:seller,admin');
        Route::patch('/{id}/cancel', [OrderController::class, 'cancelOrder']);
        Route::patch('/{id}/status', [OrderController::class, 'updateOrderStatus']);
        Route::put('/{id}/status', [OrderController::class, 'updateOrderStatus']);
        Route::patch('/{id}/approve-cancellation', [OrderController::class, 'approveCancellation'])->middleware('jwt.auth:seller,admin');
        Route::patch('/{id}/reject-cancellation', [OrderController::class, 'rejectCancellation'])->middleware('jwt.auth:seller,admin');
        Route::get('/export-report', [OrderController::class, 'exportSellerReport'])->middleware('jwt.auth:seller,admin');
    });

    // 6. Admin routes
    Route::prefix('admin')->middleware('jwt.auth:admin')->group(function () {
        Route::get('/stats', [AdminController::class, 'getGlobalStats']);
        Route::get('/analytics', [AdminController::class, 'getAdminAnalytics']);
        Route::get('/pending-sellers', [AdminController::class, 'getPendingSellers']);
        Route::get('/customers', [AdminController::class, 'getCustomers']);
        Route::delete('/customers/{id}', [AdminController::class, 'deleteCustomer']);
        Route::put('/customers/{id}/toggle-status', [AdminController::class, 'toggleCustomerStatus']);
        Route::put('/customers/{id}/freeze', [AdminController::class, 'freezeUser']);
        Route::get('/sellers', [AdminController::class, 'getSellers']);
        Route::delete('/sellers/{id}', [AdminController::class, 'deleteCustomer']);
        Route::put('/sellers/{id}/toggle-status', [AdminController::class, 'toggleCustomerStatus']);
        Route::put('/sellers/{id}/freeze', [AdminController::class, 'freezeUser']);
        Route::put('/verify-seller/{id}', [AdminController::class, 'verifySeller']);
        Route::put('/reject-seller/{id}', [AdminController::class, 'rejectSeller']);
        
        Route::get('/pending-products', [AdminController::class, 'getPendingProducts']);
        Route::put('/approve-product/{id}', [AdminController::class, 'approveProduct']);
        Route::put('/reject-product/{id}', [AdminController::class, 'rejectProduct']);

        Route::get('/settings', [AdminController::class, 'getSettings']);
        Route::put('/settings', [AdminController::class, 'updateSettings']);
        Route::post('/purge-cache', [AdminController::class, 'purgeCache']);
        Route::post('/broadcast', [AdminController::class, 'sendBroadcast']);
        Route::get('/export-global-report', [AdminController::class, 'exportGlobalReport']);
    });

    // 6.5. Super Admin routes
    Route::prefix('super-admin')->middleware('jwt.auth:super_admin')->group(function () {
        Route::get('/seller-performance', [AdminController::class, 'getSellerPerformance']);
        Route::get('/commissions', [SuperAdminController::class, 'getCommissionRules']);
        Route::put('/commissions', [SuperAdminController::class, 'updateCommissionRules']);
        Route::get('/badges', [SuperAdminController::class, 'getBadges']);
        Route::post('/badges/toggle', [SuperAdminController::class, 'toggleArtisanBadge']);
        Route::get('/audit-logs', [SuperAdminController::class, 'getAuditLogs']);
        Route::get('/system-health', [SuperAdminController::class, 'getSystemHealth']);
        Route::get('/backup-db', [SuperAdminController::class, 'downloadDatabaseBackup']);
    });

    // Public Settings (Under admin in Node routes but public)
    Route::get('/admin/public-settings', [AdminController::class, 'getPublicSettings']);

    // 7. Analytics routes
    Route::prefix('analytics')->middleware('jwt.auth:seller,admin')->group(function () {
        Route::get('/seller', [AnalyticsController::class, 'getSellerAnalytics']);
        Route::get('/prescriptive', [AnalyticsController::class, 'getPrescriptiveAnalytics']);
        Route::get('/export', [AnalyticsController::class, 'exportSellerAnalytics']);
    });

    // 8. Chat routes
    Route::prefix('chat')->middleware('jwt.auth')->group(function () {
        Route::get('/threads', [ChatController::class, 'getConversations']);
        Route::get('/conversations', [ChatController::class, 'getConversations']);
        Route::get('/conversation/{otherUserId}', [ChatController::class, 'getConversation']);
        Route::get('/messages/{otherUserId}', [ChatController::class, 'getConversation']);
        Route::get('/{otherUserId}', [ChatController::class, 'getConversation']);
        Route::delete('/conversation/{otherUserId}', [ChatController::class, 'deleteConversation']);
        Route::put('/read/{otherUserId}', [ChatController::class, 'markAsRead']);
        Route::post('/send', [ChatController::class, 'sendMessage']);
        Route::post('/', [ChatController::class, 'sendMessage']);
    });

    // 9. Notification routes
    Route::prefix('notifications')->middleware('jwt.auth')->group(function () {
        Route::get('/', [NotificationController::class, 'getMyNotifications']);
        Route::get('/unread-count', [NotificationController::class, 'getUnreadCount']);
        Route::put('/read-all', [NotificationController::class, 'markAllRead']);
        Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
    });

    // 10. Refund routes
    Route::prefix('refunds')->middleware('jwt.auth')->group(function () {
        Route::post('/request', [RefundController::class, 'createRefundRequest']);
        Route::get('/customer', [RefundController::class, 'getCustomerRefundRequests']);
        Route::get('/seller', [RefundController::class, 'getSellerRefundRequests'])->middleware('jwt.auth:seller');
        Route::put('/{id}/status', [RefundController::class, 'updateRefundStatus'])->middleware('jwt.auth:seller');
    });

    // 11. Report routes
    Route::prefix('reports')->middleware('jwt.auth')->group(function () {
        Route::post('/request', [ReportController::class, 'createReport']);
        Route::get('/my-reports', [ReportController::class, 'getMyReports']);
        Route::get('/admin/all', [ReportController::class, 'getAllReportsAdmin'])->middleware('jwt.auth:admin');
        Route::put('/admin/{id}/resolve', [ReportController::class, 'resolveReport'])->middleware('jwt.auth:admin');
    });

    // 12. Review routes
    Route::prefix('reviews')->group(function () {
        Route::post('/', [ReviewController::class, 'createReview'])->middleware('jwt.auth:customer');
        Route::get('/product/{productId}', [ReviewController::class, 'getProductReviews']);
        Route::get('/seller/{sellerId}', [ReviewController::class, 'getSellerReviews']);
    });

    // 13. Upload route
    Route::post('/upload', [UploadController::class, 'upload'])->middleware('jwt.auth');

    // 14. User routes
    Route::prefix('users')->middleware('jwt.auth')->group(function () {
        Route::get('/profile', [UserController::class, 'getProfile']);
        Route::put('/profile', [UserController::class, 'updateProfile']);
        Route::get('/addresses', [UserController::class, 'getAddresses']);
        Route::post('/addresses', [UserController::class, 'createAddress']);
        Route::put('/addresses/{id}', [UserController::class, 'updateAddress']);
        Route::delete('/addresses/{id}', [UserController::class, 'deleteAddress']);
        Route::patch('/addresses/{id}/set-default', [UserController::class, 'setDefaultAddress']);
        Route::patch('/fcm-token', [UserController::class, 'updateFcmToken']);
        Route::put('/change-password', [UserController::class, 'changePassword']);
        Route::get('/stats', [UserController::class, 'getCustomerStats']);
        
        // Shop Details & Follow
        Route::get('/seller/{id}', [UserController::class, 'getSellerInfo'])->withoutMiddleware('jwt.auth');
        Route::post('/seller/{id}/follow', [UserController::class, 'toggleFollow']);
    });

    // 15. Super Admin routes
    Route::prefix('super-admin')->middleware('jwt.auth:super_admin')->group(function () {
        Route::get('/commissions', [SuperAdminController::class, 'getCommissionRules']);
        Route::put('/commissions', [SuperAdminController::class, 'updateCommissionRules']);
        Route::get('/badges', [SuperAdminController::class, 'getBadges']);
        Route::post('/badges/toggle', [SuperAdminController::class, 'toggleArtisanBadge']);
        Route::get('/audit-logs', [SuperAdminController::class, 'getAuditLogs']);
        Route::get('/system-health', [SuperAdminController::class, 'getSystemHealth']);
        Route::get('/backup-db', [SuperAdminController::class, 'downloadDatabaseBackup']);
    });
});
