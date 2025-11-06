<?php

use Illuminate\Support\Facades\Route;
use Modules\PTS\Http\Controllers\PTSController;

Route::prefix('pts')->group(function () {
    Route::get('/fetch', [PTSController::class, 'fetchAndStorePTSData']);
    Route::get('/data', [PTSController::class, 'getStoredPTSData']);
});