<?php

use Illuminate\Support\Facades\Route;
use Modules\Bar\Http\Controllers\BarController;

Route::get('/bars', [BarController::class, 'index']);
Route::get('/bars/show/{id}', [BarController::class, 'show']);

Route::middleware(['auth:sanctum','role:Admin'])->prefix('bars')->group(function () {
    Route::post('/store', [BarController::class, 'store']);
    Route::delete('/delete/{id}', [BarController::class, 'destroy']);
    Route::post('/update/{id}', [BarController::class, 'update']);
    Route::get('/archived', [BarController::class, 'showArchiveRecords']);
    Route::post('/restore', [BarController::class, 'restoreArchiveRecords']);
});