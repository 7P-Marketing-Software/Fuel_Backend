<?php

use Illuminate\Support\Facades\Route;
use Modules\PTS\Http\Controllers\PTSController;

Route::match(['get', 'post'], '/jsonPTS', [PTSController::class, 'handleNativePTS']);
Route::get('/pts-logs', [PTSController::class, 'viewLogs']);
