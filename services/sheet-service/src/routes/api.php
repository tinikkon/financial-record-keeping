<?php

declare(strict_types=1);

use Finance\Domains\Auth\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('auth/login', [AuthController::class, 'login']);
Route::post('auth/refresh', [AuthController::class, 'refresh']);
Route::post('auth/logout', [AuthController::class, 'logout']);

Route::middleware('access-token')->group(static function (): void {
    Route::get('me', [AuthController::class, 'me']);
});
