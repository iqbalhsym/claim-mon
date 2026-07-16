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
    Route::redirect('/', '/dashboard/ranap')->name('dashboard');
    Route::get('dashboard/ranap', [DashboardController::class, 'indexRanap'])->name('dashboard.ranap');
    Route::get('dashboard/rajal', [DashboardController::class, 'indexRajal'])->name('dashboard.rajal');
    Route::get('dashboard/export/{jenis_rawat}', [DashboardController::class, 'exportExcel'])->name('dashboard.export');

    // --- DATA KLAIM ---
    Route::get('claim-records/ranap', [ClaimRecordController::class, 'indexRanap'])->name('claim-records.ranap');
    Route::get('claim-records/rajal', [ClaimRecordController::class, 'indexRajal'])->name('claim-records.rajal');
    Route::get('claim-records/export/{jenis_rawat}', [ClaimRecordController::class, 'export'])->name('claim-records.export');
    Route::post('claim-records/import', [ClaimRecordController::class, 'import'])->name('claim-records.import');
    Route::delete('claim-records/truncate/{jenis_rawat}', [ClaimRecordController::class, 'truncate'])->name('claim-records.truncate');
    Route::get('claim-records/{id}', [ClaimRecordController::class, 'show'])->name('claim-records.show');
    Route::get('dpjp-report/ranap', [ClaimRecordController::class, 'dpjpReportRanap'])->name('claim-records.dpjp.ranap');
    Route::get('dpjp-report/rajal', [ClaimRecordController::class, 'dpjpReportRajal'])->name('claim-records.dpjp.rajal');
    Route::get('dpjp-report/export/{jenis_rawat}', [ClaimRecordController::class, 'exportDpjp'])->name('claim-records.dpjp.export');
    Route::get('dpjp-report/ksm/{jenis_rawat}/{ksm}', [ClaimRecordController::class, 'ksmReport'])->name('claim-records.dpjp.ksm')->where('ksm', '.*');

    // --- MANAJEMEN AKUN (Hanya Administrator) ---
    Route::middleware(['role:administrator'])->prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::put('/{id}', [UserController::class, 'updateRole'])->name('updateRole');
        Route::delete('/{id}', [UserController::class, 'destroy'])->name('destroy');
    });
});