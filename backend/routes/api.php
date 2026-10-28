<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CheckinController;
use App\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Route;

// Public
Route::post('/register', [RegistrationController::class, 'store']);
Route::post('/login', [AuthController::class, 'login']);
// Scan page opens without login; limit guesses of QR codes
Route::post('/checkin', [CheckinController::class, 'scan'])->middleware('throttle:60,1');

// Admin pages: every request checks the token
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/registrations', [RegistrationController::class, 'index']);
    Route::put('/registrations/{registration}', [RegistrationController::class, 'update']);
    Route::post('/registrations/{registration}/resend', [RegistrationController::class, 'resend']);
    Route::post('/registrations/{registration}/ticket', [RegistrationController::class, 'ticket']);

    Route::get('/checkin-logs', [CheckinController::class, 'index']);
});
