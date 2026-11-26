<?php

use App\Http\Controllers\Auth\SessionController;
use Illuminate\Support\Facades\Route;

// Browser routes only. JSON endpoints live in routes/api.php.

Route::post('/login', [SessionController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('login.store');

Route::post('/logout', [SessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// Everything else is handled by the Vue app (paths starting with /api are excluded).
Route::view('/{path?}', 'app')
    ->where('path', '^(?!api(?:/|$)).*$');
