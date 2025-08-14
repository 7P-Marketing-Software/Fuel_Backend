<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;
use Modules\Auth\Http\Controllers\RoleController;
use Modules\Auth\Http\Controllers\UserController;
use Illuminate\Support\Facades\Artisan;

Route::prefix('auth/')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forget-password', [AuthController::class, 'forgetPassword']);
    Route::post('check-otp', [AuthController::class, 'checkPhoneOTPForgetPassword']);
    Route::post('resend-otp', [AuthController::class, 'resendOtp']);
    Route::post('google', [AuthController::class, 'redirectToGoogle']);
    Route::get('google/callback', [AuthController::class, 'handleGoogleCallback']);
});

Route::middleware('auth:sanctum')->group(function () {

    // AuthController
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::post('auth/reset-password', [AuthController::class, 'resetPassword']);

    // UserController
    Route::get('user/profile', [UserController::class, 'showProfile']);
    Route::post('user/profile/update', [UserController::class, 'updateProfile']);
    Route::post('user/change-password', [UserController::class, 'changePassword']);
    Route::delete('user/delete-account', [UserController::class, 'deleteUser']);
});


Route::middleware(['auth:sanctum', 'role:Admin'])->group(function () {

    Route::get('/users', [UserController::class, 'getAllUsers']);

    // Roles
    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::get('/roles/{id}', [RoleController::class, 'show']);
    Route::post('/roles/{id}', [RoleController::class, 'update']);
    Route::delete('/roles/{id}', [RoleController::class, 'destroy']);

    // Permissions
    Route::get('/permissions', [RoleController::class, 'getPermissions']);
    Route::post('/permissions', [RoleController::class, 'storePermission']);
    Route::post('/roles/{id}/assign-permissions', [RoleController::class, 'assignPermissions']);
    Route::get('/get-user-permissions/{userId}', [RoleController::class, 'getUserPermissions']);

    Route::post('/assign-permissions-to-user/{userId}', [RoleController::class, 'assignPermissionToUser']);

});


Route::get('run-seeder',function(){
    Artisan::call('db:seed', [
             '--class' => 'Database\\Seeders\\AdminSeeder'
     ]);
     return response()->json(['message' => 'Seeder run successfully']);
 });
