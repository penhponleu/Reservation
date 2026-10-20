<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CheckinController;
use App\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Route;

// Public
Route::post('/register', [RegistrationController::class, 'store']);
Route::post('/login', [AuthController::class, 'login']);

// Admin pages: every request checks the token
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/registrations', [RegistrationController::class, 'index']);
    Route::post('/registrations/{registration}/resend', [RegistrationController::class, 'resend']);

    Route::post('/checkin', [CheckinController::class, 'scan']);
    Route::get('/checkin-logs', [CheckinController::class, 'index']);
});
