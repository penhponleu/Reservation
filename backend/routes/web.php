<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

// Open backend URL → backend is running + can it reach the database?
Route::get('/', function () {
    try {
        DB::connection()->getPdo();
        $db = Schema::hasTable('registrations') ? 'ok' : 'connected, tables not created yet';
    } catch (Throwable $e) {
        $db = 'error: '.$e->getMessage(); // e.g. wrong DB_HOST / DB_PASSWORD
    }

    return response()->json([
        'status'   => 'ok',
        'app'      => config('app.name'),
        'database' => $db,
    ]);
})->withoutMiddleware('web'); // no session → works even before the sessions table exists
