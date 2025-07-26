<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\PredictiveDialerController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned the "api" middleware group. Make something great!
|
*/

// Public API routes (no authentication required)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Protected API routes (require Sanctum authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });

    // Predictive Dialer API Routes
    Route::prefix('campaigns')->group(function () {
        Route::post('/{campaign}/start', [PredictiveDialerController::class, 'start']);
        Route::post('/{campaign}/stop', [PredictiveDialerController::class, 'stop']);
        Route::post('/{campaign}/pause', [PredictiveDialerController::class, 'pause']);
        Route::post('/{campaign}/resume', [PredictiveDialerController::class, 'resume']);
        Route::get('/{campaign}/status', [PredictiveDialerController::class, 'status']);
    });
});

// Web-based API routes (for frontend using session authentication)
Route::middleware(['web', 'auth'])->prefix('campaigns')->group(function () {
    Route::post('/{campaign}/start', [PredictiveDialerController::class, 'start']);
    Route::post('/{campaign}/stop', [PredictiveDialerController::class, 'stop']);
    Route::post('/{campaign}/pause', [PredictiveDialerController::class, 'pause']);
    Route::post('/{campaign}/resume', [PredictiveDialerController::class, 'resume']);
    Route::get('/{campaign}/status', [PredictiveDialerController::class, 'status']);
});