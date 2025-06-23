<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PredictiveDialerController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/campaigns/{campaign}/start', [PredictiveDialerController::class, 'start']);
    Route::post('/campaigns/{campaign}/stop', [PredictiveDialerController::class, 'stop']);
    Route::post('/campaigns/{campaign}/pause', [PredictiveDialerController::class, 'pause']);
    Route::post('/campaigns/{campaign}/resume', [PredictiveDialerController::class, 'resume']);
    Route::get('/campaigns/{campaign}/status', [PredictiveDialerController::class, 'status']);
});