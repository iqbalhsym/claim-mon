<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ClaimRecordController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 1. Rute Autentikasi (Publik)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/refresh-captcha', [AuthController::class, 'refreshCaptcha'])->name('captcha.refresh');

// 2. Rute yang butuh LOGIN
Route::middleware(['auth'])->group(function () {

    // --- DASHBOARD ---
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // --- DATA KLAIM ---
    Route::get('claim-records', [ClaimRecordController::class, 'index'])->name('claim-records.index');
    Route::post('claim-records/import', [ClaimRecordController::class, 'import'])->name('claim-records.import');
    Route::delete('claim-records/truncate', [ClaimRecordController::class, 'truncate'])->name('claim-records.truncate');
    Route::get('dpjp-report', [ClaimRecordController::class, 'dpjpReport'])->name('claim-records.dpjp');

    // --- MANAJEMEN AKUN (Hanya Administrator) ---
    Route::middleware(['role:administrator'])->prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::put('/{id}', [UserController::class, 'updateRole'])->name('updateRole');
        Route::delete('/{id}', [UserController::class, 'destroy'])->name('destroy');
    });
});