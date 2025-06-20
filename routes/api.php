<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PredictiveDialerController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Campaign control routes - menggunakan web middleware untuk session-based auth
Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/campaigns/{campaign}/start', [PredictiveDialerController::class, 'start'])->name('api.campaign.start');
    Route::post('/campaigns/{campaign}/stop', [PredictiveDialerController::class, 'stop'])->name('api.campaign.stop');
    Route::post('/campaigns/{campaign}/pause', [PredictiveDialerController::class, 'pause'])->name('api.campaign.pause');
    Route::post('/campaigns/{campaign}/resume', [PredictiveDialerController::class, 'resume'])->name('api.campaign.resume');
    Route::get('/campaigns/{campaign}/status', [PredictiveDialerController::class, 'status'])->name('api.campaign.status');
});