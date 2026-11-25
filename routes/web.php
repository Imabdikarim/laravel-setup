<?php

use App\Http\Controllers\Auth\SessionController;
use Illuminate\Support\Facades\Route; 
Route::post('/login', [SessionController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('login.store');

Route::post('/logout', [SessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::view('/{path?}', 'app')
    ->where('path', '^(?!api(?:/|$)).*$');