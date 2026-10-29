<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (Central Domain)
|--------------------------------------------------------------------------
| Routes here are accessible on the central domain (wonders.test).
| All school-facing routes are in routes/tenant.php — they require
| a tenant domain and InitializeTenancyByDomain middleware.
|
*/

// Central device pairing fallback for desktop clients connecting to main domain
Route::post('/api/v1/desktop/pair', [\App\Http\Controllers\Api\DesktopDeviceAuthController::class, 'pair']);
