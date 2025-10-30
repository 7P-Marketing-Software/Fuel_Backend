<?php

use Illuminate\Support\Facades\Route;
use Modules\PTS\Http\Controllers\PTSController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('pts', PTSController::class)->names('pts');
});
