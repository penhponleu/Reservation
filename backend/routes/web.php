<?php

use Illuminate\Support\Facades\Route;

// Open backend URL → backend is running
Route::get('/', function () {
    return response()->json([
        'status' => 'ok',
        'app'    => config('app.name'),
    ]);
});
