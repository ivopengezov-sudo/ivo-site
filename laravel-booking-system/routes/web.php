<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public booking flow
|--------------------------------------------------------------------------
*/
Route::get('/', [BookingController::class, 'index'])->name('bookings.index');
Route::get('/services/{service}/book', [BookingController::class, 'create'])->name('bookings.create');
Route::post('/services/{service}/book', [BookingController::class, 'store'])->name('bookings.store');
Route::get('/bookings/{booking}/confirmation', [BookingController::class, 'confirmation'])->name('bookings.confirmation');

/*
|--------------------------------------------------------------------------
| Stripe webhook (no CSRF, no auth — verified via Stripe-Signature header)
|--------------------------------------------------------------------------
*/
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])
    ->name('stripe.webhook')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

/*
|--------------------------------------------------------------------------
| Staff dashboard
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/bookings', [DashboardController::class, 'index'])->name('bookings.index');
    Route::patch('/bookings/{booking}/status', [DashboardController::class, 'updateStatus'])->name('bookings.status');
});
