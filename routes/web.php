<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardRecordController;
use App\Http\Controllers\DashboardStateController;
use App\Http\Controllers\RcsaReportController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/state', [DashboardStateController::class, 'show'])->name('dashboard.state.show');
    Route::put('/state', [DashboardStateController::class, 'update'])->name('dashboard.state.update');
    Route::get('/dashboard-options', [DashboardStateController::class, 'metadata'])->name('dashboard.options');
    Route::post('/dashboard/reset', [DashboardStateController::class, 'reset'])
        ->middleware('role:hq')
        ->name('dashboard.reset');

    Route::post('/dashboard/records/{module}', [DashboardRecordController::class, 'store'])->name('dashboard.records.store');
    Route::put('/dashboard/records/{module}/{id}', [DashboardRecordController::class, 'update'])->name('dashboard.records.update');
    Route::patch('/dashboard/records/{module}/{id}/status', [DashboardRecordController::class, 'updateStatus'])->name('dashboard.records.status');
    Route::delete('/dashboard/records/{module}/{id}', [DashboardRecordController::class, 'destroy'])->name('dashboard.records.destroy');

    Route::get('/rcsa/reports', [RcsaReportController::class, 'index'])->name('rcsa.reports.index');
    Route::post('/rcsa/reports/periods', [RcsaReportController::class, 'storePeriod'])
        ->middleware('role:hq')
        ->name('rcsa.reports.periods.store');
    Route::get('/rcsa/reports/periods/{period}', [RcsaReportController::class, 'show'])->name('rcsa.reports.periods.show');
    Route::get('/rcsa/reports/periods/{period}/checklist', [RcsaReportController::class, 'checklist'])->name('rcsa.reports.checklist');
    Route::post('/rcsa/reports/periods/{period}/submit', [RcsaReportController::class, 'submit'])->name('rcsa.reports.submit');
    Route::post('/rcsa/reports/periods/{period}/unlock', [RcsaReportController::class, 'unlock'])
        ->name('rcsa.reports.unlock');
    Route::post('/rcsa/reports/periods/{period}/extend', [RcsaReportController::class, 'extend'])
        ->middleware('role:hq')
        ->name('rcsa.reports.extend');
    Route::get('/rcsa/reports/branch-history', [RcsaReportController::class, 'branchHistory'])->name('rcsa.reports.branch-history');
    Route::get('/rcsa/reports/submissions/{submission}', [RcsaReportController::class, 'submission'])->name('rcsa.reports.submission');
    Route::get('/rcsa/workspace/{period}', [RcsaReportController::class, 'workspace'])->name('rcsa.workspace');
});
