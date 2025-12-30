<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\MyBookController;
use App\Http\Controllers\Api\V1\PublicBookController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\TradeController;
use App\Http\Controllers\Api\V1\TradeMessageController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    });

    Route::get('books/search', [PublicBookController::class, 'search']);
    Route::get('books/{book}', [PublicBookController::class, 'show']);

    Route::middleware(['auth:sanctum', 'blocked'])->group(function () {
        Route::get('me', [MeController::class, 'show']);
        Route::put('me', [MeController::class, 'update']);
        Route::post('me/photo', [MeController::class, 'photo']);
        Route::post('me/device-tokens', [MeController::class, 'storeDeviceToken']);
        Route::get('me/notifications', [MeController::class, 'notifications']);
        Route::post('me/notifications/{id}/read', [MeController::class, 'readNotification']);

        Route::get('me/books', [MyBookController::class, 'index']);
        Route::post('me/books', [MyBookController::class, 'store']);
        Route::get('me/books/{book}', [MyBookController::class, 'show']);
        Route::put('me/books/{book}', [MyBookController::class, 'update']);
        Route::delete('me/books/{book}', [MyBookController::class, 'destroy']);
        Route::post('me/books/{book}/availability', [MyBookController::class, 'availability']);

        Route::post('trades', [TradeController::class, 'store']);
        Route::get('trades', [TradeController::class, 'index']);
        Route::get('trades/{trade}', [TradeController::class, 'show']);
        Route::post('trades/{trade}/accept', [TradeController::class, 'accept']);
        Route::post('trades/{trade}/decline', [TradeController::class, 'decline']);
        Route::post('trades/{trade}/cancel', [TradeController::class, 'cancel']);
        Route::post('trades/{trade}/complete', [TradeController::class, 'complete']);
        Route::get('trades/{trade}/messages', [TradeMessageController::class, 'index']);
        Route::post('trades/{trade}/messages', [TradeMessageController::class, 'store']);

        Route::post('trades/{trade}/review', [ReviewController::class, 'store']);
        Route::get('users/{user}/reviews', [ReviewController::class, 'index']);
    });
});
