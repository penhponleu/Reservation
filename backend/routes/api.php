<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CheckinController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Public
Route::post('/register', [RegistrationController::class, 'store']);
Route::post('/login', [AuthController::class, 'login']);
// Scan page opens without login; limit guesses of QR codes
Route::post('/checkin', [CheckinController::class, 'scan'])->middleware('throttle:60,1');

// Admin pages: every request checks the token
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me', [AuthController::class, 'updateMe']); // Settings page
    Route::post('/logout', [AuthController::class, 'logout']);

    // Tickets page (registered guests)
    Route::get('/registrations', [RegistrationController::class, 'index']);
    Route::put('/registrations/{registration}', [RegistrationController::class, 'update']);
    Route::delete('/registrations/{registration}', [RegistrationController::class, 'destroy']);
    Route::post('/registrations/{registration}/resend', [RegistrationController::class, 'resend']);
    Route::get('/checkin-logs', [CheckinController::class, 'index']);

    // Users page (staff / admin logins)
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
});
