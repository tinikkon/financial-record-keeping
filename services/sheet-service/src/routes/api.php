<?php

declare(strict_types=1);

use Finance\Domains\Auth\Controllers\AuthController;
use Finance\Domains\Cells\Controllers\CellController;
use Finance\Domains\Sheets\Controllers\SheetController;
use Finance\Domains\Workbooks\Controllers\WorkbookController;
use Illuminate\Support\Facades\Route;

Route::post('auth/login', [AuthController::class, 'login']);
Route::post('auth/refresh', [AuthController::class, 'refresh']);
Route::post('auth/logout', [AuthController::class, 'logout']);

Route::middleware('access-token')->group(static function (): void {
    Route::get('me', [AuthController::class, 'me']);

    Route::get('workbooks', [WorkbookController::class, 'index']);
    Route::post('workbooks', [WorkbookController::class, 'store']);

    Route::get('workbooks/{workbookIdentifier}/sheets', [SheetController::class, 'index']);
    Route::post('workbooks/{workbookIdentifier}/sheets', [SheetController::class, 'store']);
    Route::post('workbooks/{workbookIdentifier}/sheets/order', [SheetController::class, 'reorder']);

    Route::patch('sheets/{sheetIdentifier}', [SheetController::class, 'update']);
    Route::delete('sheets/{sheetIdentifier}', [SheetController::class, 'destroy']);
    Route::post('sheets/{sheetIdentifier}/duplicate', [SheetController::class, 'duplicate']);

    Route::get('sheets/{sheetIdentifier}/cells', [CellController::class, 'show']);
    Route::patch('sheets/{sheetIdentifier}/cells', [CellController::class, 'update']);
    Route::patch('sheets/{sheetIdentifier}/cells/format', [CellController::class, 'format']);
});
