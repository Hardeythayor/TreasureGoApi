<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SubscriptionTierController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/email/verify', [AuthController::class, 'verifyEmail']);
    Route::post('/email/resend', [AuthController::class, 'resendVerificationCode']);

    Route::middleware('verified')->group(function () {
        Route::get('/user', [AuthController::class, 'me']);

        Route::middleware('role:admin')->group(function () {
            Route::get('/admin/ping', fn () => response()->json(['message' => 'pong, admin']));

            Route::prefix('admin/subscription-tiers')->group(function () {
                Route::get('/', [SubscriptionTierController::class, 'index']);
                Route::post('/', [SubscriptionTierController::class, 'store']);
                Route::get('/{subscriptionTier}', [SubscriptionTierController::class, 'show']);
                Route::put('/{subscriptionTier}', [SubscriptionTierController::class, 'update']);
                Route::delete('/{subscriptionTier}', [SubscriptionTierController::class, 'destroy']);
                Route::patch('/{subscriptionTier}/toggle-status', [SubscriptionTierController::class, 'toggleStatus']);
            });
        });
    });
});
