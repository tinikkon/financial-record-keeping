<?php

declare(strict_types=1);

use Finance\Domains\Changelog\Controllers\ChangelogController;
use Finance\Domains\Summaries\Controllers\SummaryController;
use Illuminate\Support\Facades\Route;

Route::middleware('access-token')->group(static function (): void {
    Route::get('sheets/{sheetIdentifier}/history', [ChangelogController::class, 'forSheet']);
    Route::get('sheets/{sheetIdentifier}/cells/{address}/history', [ChangelogController::class, 'forCell']);
    Route::get('sheets/{sheetIdentifier}/state', [SummaryController::class, 'sheetState']);
    Route::get('workbooks/{workbookIdentifier}/summary', [SummaryController::class, 'workbook']);
});
