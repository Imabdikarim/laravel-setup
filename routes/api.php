<?php

use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes
|--------------------------------------------------------------------------
|
| Everything here is served under /api and is loaded from bootstrap/app.php
| with the "web" (session + CSRF), "auth" and "admin" middleware, so only a
| signed-in administrator can call these endpoints from the admin panel.
|
| Add future modules (projects, etc.) in this file.
|
*/

Route::apiResource('users', UserController::class)->except('show');
