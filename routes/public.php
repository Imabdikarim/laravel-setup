<?php

use App\Http\Controllers\Api\PublicProjectController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API (/api/public/...)
|--------------------------------------------------------------------------
|
| Read-only JSON for the website. Loaded from bootstrap/app.php with the "api"
| middleware group, so there is no session, no cookie and no login.
|
*/

Route::get('projects', [PublicProjectController::class, 'index'])->name('projects.index');