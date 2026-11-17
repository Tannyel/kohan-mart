<?php

use App\Http\Controllers\Api\StockCheckController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

// Public Storefront & Info Pages
Route::get('/', [StoreController::class, 'home'])->name('home');
Route::get('/catalog', [StoreController::class, 'index'])->name('store.index');
Route::get('/about', [StoreController::class, 'about'])->name('pages.about');
Route::get('/contact', [StoreController::class, 'contact'])->name('pages.contact');
Route::post('/contact', [StoreController::class, 'sendContact'])->name('pages.contact.send');

// Shareable Public Courier Tracking Page (ShareLink Simulator)
Route::get('/track-delivery/{order:order_number}', [DeliveryController::class, 'track'])->name('delivery.track');
Route::get('/api/track-delivery/{order:order_number}/status', [DeliveryController::class, 'statusJson'])->name('api.delivery.status');
Route::post('/track-delivery/{order:order_number}/advance', [DeliveryController::class, 'advanceStage'])->name('delivery.advance');

// Guest Authentication & Email OTP Verification
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/verify-otp', [AuthController::class, 'showOtpForm'])->name('otp.notice');
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->name('otp.verify');
    Route::post('/verify-otp/resend', [AuthController::class, 'resendOtp'])->name('otp.resend');
});

// Authenticated Customer & Staff Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Customer Profile, Loyalty Wallet & Points Catalog
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/rewards', [ProfileController::class, 'rewards'])->name('profile.rewards');
    Route::post('/rewards/redeem/{product}', [ProfileController::class, 'redeemProduct'])->name('profile.rewards.redeem');

    // Shopping Basket (AJAX + Standard)
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add/{product}', [CartController::class, 'add'])->name('cart.add');
    Route::patch('/cart/update/{product}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/remove/{product}', [CartController::class, 'remove'])->name('cart.remove');

    // Live Stock & Delivery Fee Quotation APIs
    Route::get('/api/cart/validate-stock', [StockCheckController::class, 'validateCart'])->name('api.cart.validate-stock');
    Route::post('/api/delivery/quote', [DeliveryController::class, 'quote'])->name('api.delivery.quote');

    // Checkout, Customer Reservations & Official Receipt
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/my-orders', [StoreController::class, 'myOrders'])->name('store.orders');
    Route::get('/my-orders/{order}/receipt', [CheckoutController::class, 'receipt'])->name('orders.receipt');
    Route::patch('/my-orders/{order}/cancel', [CheckoutController::class, 'cancel'])->name('orders.cancel');

    // Admin / Staff Dashboard & Operations
    Route::prefix('staff')->name('staff.')->group(function () {
        Route::get('/dashboard', [StaffController::class, 'dashboard'])->name('dashboard');
        Route::patch('/orders/{order}', [StaffController::class, 'updateOrder'])->name('orders.update');
        Route::patch('/orders/{order}/confirm-return', [StaffController::class, 'confirmStockReturn'])->name('orders.confirm-return');
        Route::post('/products', [StaffController::class, 'storeProduct'])->name('products.store');
        Route::patch('/products/{product}', [StaffController::class, 'updateProduct'])->name('products.update');
        Route::delete('/products/{product}', [StaffController::class, 'destroyProduct'])->name('products.destroy');
        Route::post('/settings', [StaffController::class, 'updateSettings'])->name('settings.update');
        Route::post('/users', [StaffController::class, 'storeStaff'])->name('users.store');
        Route::delete('/users/{user}', [StaffController::class, 'destroyStaff'])->name('users.destroy');
        Route::delete('/logs', [StaffController::class, 'clearLogs'])->name('logs.clear');

        // Inside your Route::middleware(['auth', ...])->prefix('staff')->name('staff.')->group(function () {
    Route::get('/analytics', [\App\Http\Controllers\StaffAnalyticsController::class, 'index'])->name('analytics');
    Route::post('/analytics/ai-summary', [\App\Http\Controllers\StaffAnalyticsController::class, 'generateAiSummary'])->name('analytics.ai-summary');
// });
    });
});