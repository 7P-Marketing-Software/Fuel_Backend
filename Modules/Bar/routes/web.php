<?php

use Illuminate\Support\Facades\Route;
use Modules\Bar\Http\Controllers\BarController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('bars', BarController::class)->names('bar');
});
