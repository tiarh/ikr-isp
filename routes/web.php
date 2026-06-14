<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('psb.dashboard');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});

// PSB routes
require __DIR__ . '/psb.php';

// API routes
require __DIR__ . '/api.php';

// Filament admin
Route::prefix('admin')->group(function () {
    // Filament handles its own routes via AdminPanelProvider
});
