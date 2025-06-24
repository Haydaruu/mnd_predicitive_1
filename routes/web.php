<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Events\TetsEvent;
use App\Http\Controllers\RoleDashboardController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CampaignTemplateController;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CallReportController;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth', 'verified','role:Agent'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

Route::middleware(['auth', 'verified', 'role:SuperAdmin,Admin'])->group(function () {
    // Campaign Management
    Route::get('/campaign', [CampaignController::class, 'index'])->name('campaign');
    Route::get('/campaign/upload', [CampaignController::class, 'showUploadForm'])->name('campaign.upload.form');
    Route::post('/upload', [CampaignController::class, 'upload'])->name('campaign.upload');
    Route::get('/campaign/{campaign}', [CampaignController::class, 'show'])->name('campaign.show');
    Route::delete('/campaign/{campaign}', [CampaignController::class, 'destroy'])->name('campaign.destroy');
    
    // Campaign Nasbahs (Customer Data)
    Route::get('/campaign/{campaign}/nasbahs', [CampaignController::class, 'nasbahs'])->name('campaign.nasbahs');
    Route::delete('/campaign/{campaign}/nasbahs/{nasbah}', [CampaignController::class, 'destroyNasbah'])->name('campaign.nasbahs.destroy');
    Route::get('/campaign/{campaign}/nasbahs/export', [CampaignController::class, 'exportNasbahs'])->name('campaign.nasbahs.export');
    
    // Campaign Templates
    Route::get('/campaign/template/download', [CampaignTemplateController::class, 'downloadTemplate'])->name('campaign.template.download');
    
    // Reports
    Route::get('/reports/dashboard', [CallReportController::class, 'dashboard'])->name('reports.dashboard');
    Route::get('/reports/call-reports', [CallReportController::class, 'index'])->name('reports.call-reports');
    Route::post('/reports/generate', [CallReportController::class, 'generate'])->name('reports.generate');
    Route::get('/reports/campaign/{campaign}', [CallReportController::class, 'campaignReport'])->name('reports.campaign');
    
    // Excel Export Routes
    Route::get('/reports/export/calls', [CallReportController::class, 'exportCallReports'])->name('reports.export.calls');
    Route::get('/reports/export/campaign/{campaign}', [CallReportController::class, 'exportCampaignSummary'])->name('reports.export.campaign');
});

Route::middleware(['auth', 'verified', 'role:SuperAdmin'])->group(function () {
    Route::get('/SuperAdminDashboard', [RoleDashboardController::class, 'superAdmin'])->name('SuperAdmin'); 
    
    // User Management
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
});

Route::middleware(['auth', 'verified', 'role:Admin'])->group(function () {
    Route::get('/AdminDashboard', [AdminDashboardController::class, 'index'])->name('Admin');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';