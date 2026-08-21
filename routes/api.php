<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RewardController;
use App\Http\Controllers\Api\SubscriptionTierController;
use App\Http\Controllers\Api\SubscriptionTierTransactionController;
use App\Http\Controllers\Api\TreasureController;
use App\Http\Controllers\Api\TreasureHuntController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserTierSubscriptionController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/email/verify', [AuthController::class, 'verifyEmail']);
    Route::post('/email/resend', [AuthController::class, 'resendVerificationCode']);

    Route::middleware('verified')->group(function () {
        Route::get('/user', [AuthController::class, 'me']);

        Route::get('/subscription-tiers', [SubscriptionTierController::class, 'active']);
        Route::get('/subscription-tiers/{subscriptionTier}/treasures', [TreasureController::class, 'byTier']);

        Route::post('/subscriptions', [UserTierSubscriptionController::class, 'subscribe']);
        Route::get('/subscriptions/current', [UserTierSubscriptionController::class, 'current']);
        Route::post('/subscriptions/{subscription}/transactions', [SubscriptionTierTransactionController::class, 'store']);
        Route::post('/subscriptions/transactions/verify', [SubscriptionTierTransactionController::class, 'verify']);

        Route::prefix('treasures/{treasure}')->group(function () {
            Route::post('/start-hunt', [TreasureHuntController::class, 'start']);
            Route::post('/find', [TreasureHuntController::class, 'find']);
        });

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

            Route::prefix('admin/treasures')->group(function () {
                Route::get('/', [TreasureController::class, 'index']);
                Route::post('/', [TreasureController::class, 'store']);
                Route::get('/{treasure}', [TreasureController::class, 'show']);
                Route::put('/{treasure}', [TreasureController::class, 'update']);
                Route::delete('/{treasure}', [TreasureController::class, 'destroy']);
                Route::patch('/{treasure}/toggle-status', [TreasureController::class, 'toggleStatus']);
            });

            Route::prefix('admin/users')->group(function () {
                Route::get('/', [UserController::class, 'index']);
                Route::get('/{user}', [UserController::class, 'show']);
                Route::put('/{user}', [UserController::class, 'update']);
                Route::delete('/{user}', [UserController::class, 'destroy']);
                Route::patch('/{user}/toggle-status', [UserController::class, 'toggleStatus']);
            });

            Route::post('admin/rewards', [RewardController::class, 'send']);

            Route::prefix('admin/treasure-hunts')->group(function () {
                Route::get('/', [TreasureHuntController::class, 'index']);
                Route::get('/analytics', [TreasureHuntController::class, 'analytics']);
            });
        });
    });
});
