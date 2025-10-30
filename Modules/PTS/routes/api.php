<?php

use Illuminate\Support\Facades\Route;
use Modules\PTS\Http\Controllers\PTSController;

Route::prefix('pts')->group(function () {
    // PTS-2 Controller Communication
    Route::post('/jsonPTS', [PTSController::class, 'handlePost']);
    Route::get('/jsonPTS', [PTSController::class, 'handleWebSocketHandshake']);

    // Monitoring APIs
    Route::get('/logs', [PTSController::class, 'getLogs']);
    Route::get('/sessions', [PTSController::class, 'getSessions']);
    Route::get('/measurements', [PTSController::class, 'getMeasurements']);
});
