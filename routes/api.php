<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\PredictiveDialerController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
});

// Predictive Dialer API Routes - menggunakan web middleware untuk session-based auth
Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/campaigns/{campaign}/start', [PredictiveDialerController::class, 'start']);
    Route::post('/campaigns/{campaign}/stop', [PredictiveDialerController::class, 'stop']);
    Route::post('/campaigns/{campaign}/pause', [PredictiveDialerController::class, 'pause']);
    Route::post('/campaigns/{campaign}/resume', [PredictiveDialerController::class, 'resume']);
    Route::get('/campaigns/{campaign}/status', [PredictiveDialerController::class, 'status']);
});