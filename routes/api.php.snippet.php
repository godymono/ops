<?php

use App\Http\Controllers\DashboardStateController;
use Illuminate\Support\Facades\Route;

// Paste these routes into routes/web.php, not routes/api.php,
// because the existing dashboard uses the Laravel session and CSRF token.
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/state', [DashboardStateController::class, 'show'])->name('dashboard.state.show');
    Route::put('/state', [DashboardStateController::class, 'update'])->name('dashboard.state.update');
    Route::get('/dashboard-options', [DashboardStateController::class, 'metadata'])->name('dashboard.options');
});
