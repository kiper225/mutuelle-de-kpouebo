<?php

use Illuminate\Support\Facades\Route;

// Le backend est une API : le site se trouve dans le frontend.
Route::get('/', fn () => response()->json([
    'app' => config('app.name'),
    'api' => url('/api/v1'),
    'status' => 'ok',
]));