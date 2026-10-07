<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CotisationController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\PublicationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // --- Public ---
    Route::get('publications', [PublicationController::class, 'index']);
    Route::get('publications/{slug}', [PublicationController::class, 'show']);

    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    Route::post('webhooks/payments', PaymentWebhookController::class); // authentifié par signature

    // --- Connecté ---
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);

        // --- Membres actifs ---
        Route::middleware('active')->group(function () {
            Route::get('publications/{publication}/comments', [CommentController::class, 'index']);
            Route::post('publications/{publication}/comments', [CommentController::class, 'store']);
            Route::delete('comments/{comment}', [CommentController::class, 'destroy']);

            Route::get('cotisations', [CotisationController::class, 'index']);
            Route::post('cotisations', [CotisationController::class, 'store']);

            // --- Administrateur ---
            Route::middleware('admin')->prefix('admin')->group(function () {
                Route::get('stats', Admin\StatsController::class);

                Route::get('members', [Admin\MemberController::class, 'index']);
                Route::post('members/{user}/approve', [Admin\MemberController::class, 'approve']);
                Route::post('members/{user}/suspend', [Admin\MemberController::class, 'suspend']);

                Route::apiResource('publications', Admin\PublicationController::class);

                Route::post('comments/{comment}/hide', [Admin\CommentController::class, 'hide']);
                Route::post('comments/{comment}/unhide', [Admin\CommentController::class, 'unhide']);

                Route::get('cotisations', [Admin\CotisationController::class, 'index']);
                Route::get('cotisations/overdue', [Admin\CotisationController::class, 'overdue']);
                Route::post('cotisations', [Admin\CotisationController::class, 'recordOffline']);
            });
        });
    });
});
