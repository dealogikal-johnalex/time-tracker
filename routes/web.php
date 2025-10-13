<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\TimeLogController as AdminTimeLogController;
use App\Http\Controllers\TimeLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;

// Route::get('/', function () {
//     return view('welcome');
// })->middleware('auth');

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {

    // Dashboard
    // Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Employee self-service
    Route::post('/log-time/{action}', [TimeLogController::class, 'store']);
    Route::get('/time-logs', [TimeLogController::class, 'getLogs'])->name('time.logs');

    // Admin & HR routes
    Route::middleware('role:admin|hr|superadmin')->group(function () {
        Route::get('/admin/time-logs', [AdminTimeLogController::class, 'index'])->name('admin.time-logs');
        Route::get('/admin/time-logs/export', [AdminTimeLogController::class, 'export'])->name('admin.time-logs.export');
    });
});
    
require __DIR__.'/auth.php';
