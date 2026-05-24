<?php

use App\Http\Controllers\Api\AnemometerController;
use App\Http\Controllers\Api\AnemometerReadingController;
use App\Http\Controllers\Api\AuthTokenController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\ReadingController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — parity with Django config/api_router.py
|--------------------------------------------------------------------------
|
| Omitted from Django parity (not in scope):
|   - ?anemometer= filter on /api/readings
|
*/

Route::post('auth-token', AuthTokenController::class);

Route::middleware('auth:sanctum')->group(function () {
    // users
    Route::get('users/me', [UserController::class, 'me']);
    Route::get('users', [UserController::class, 'index']);
    Route::get('users/{username}', [UserController::class, 'show']);
    Route::patch('users/{username}', [UserController::class, 'update']);

    // anemometers (custom action registered BEFORE the resource so
    // `/anemometers/recent-readings` isn't caught by the {id} wildcard).
    Route::get('anemometers/recent-readings', [AnemometerController::class, 'recentReadings']);
    Route::apiResource('anemometers', AnemometerController::class);

    // nested readings under anemometer
    Route::get('anemometers/{anemometer}/readings', [AnemometerReadingController::class, 'index']);
    Route::get('anemometers/{anemometer}/readings/{reading}', [AnemometerReadingController::class, 'show']);

    // readings (export before resource so it doesn't hit the {reading} wildcard)
    Route::get('readings/export', [ExportController::class, 'export']);
    Route::apiResource('readings', ReadingController::class);
});
