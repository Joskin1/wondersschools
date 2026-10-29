<?php

declare(strict_types=1);

use App\Http\Controllers\Api\DesktopAttendanceSyncController;
use App\Http\Controllers\Api\DesktopBiometricsController;
use App\Http\Controllers\Api\DesktopDeviceAuthController;
use App\Http\Controllers\Api\DesktopRosterController;
use App\Http\Middleware\AuthenticateDesktopDevice;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant API Routes (Desktop Attendance Hardware)
|--------------------------------------------------------------------------
|
| These stateless REST endpoints interface with offline-first desktop attendance
| clients (Wonders.Desktop) running at school entry gates and admin offices.
|
*/

Route::prefix('api/v1/desktop')->group(function () {
    // Hardware Pairing
    Route::post('/pair', [DesktopDeviceAuthController::class, 'pair']);

    // Authenticated Hardware Operations
    Route::middleware(AuthenticateDesktopDevice::class)->group(function () {
        Route::get('/roster', [DesktopRosterController::class, 'roster']);
        Route::post('/biometrics/enroll', [DesktopBiometricsController::class, 'enroll']);
        Route::post('/students/verify', [DesktopBiometricsController::class, 'verifyStudent']);
        Route::post('/attendance/batch', [DesktopAttendanceSyncController::class, 'syncBatch']);
    });
});
