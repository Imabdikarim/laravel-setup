<?php

use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin API (/api/...)
|--------------------------------------------------------------------------
|
| Loaded from bootstrap/app.php with the "web" (session + CSRF), "auth" and
| "admin" middleware, so only a signed-in administrator can call these.
|
| The public website's read-only endpoints are in routes/public.php.
|
*/

Route::apiResource('users', UserController::class)->except('show');
Route::apiResource('projects', ProjectController::class)->except('show');