<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\PatientGeographyController;

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

    // --- MEDICAL RECORDS ---
    Route::get('medical-records/export', [MedicalRecordController::class, 'export'])->name('medical-records.export');
    Route::post('medical-records/import', [MedicalRecordController::class, 'import'])->name('medical-records.import');
    Route::get('medical-records/afya-lookup', [MedicalRecordController::class, 'afyaLookup'])->name('medical-records.afya-lookup');
    Route::delete('medical-records/truncate', [MedicalRecordController::class, 'truncate'])->name('medical-records.truncate');
    Route::resource('medical-records', MedicalRecordController::class);

    // --- MASTER DATA: Search (semua user login bisa akses untuk autocomplete) ---
    Route::get('master-data/search', [MasterDataController::class, 'search'])->name('master-data.search');

    // --- MASTER DATA: CRUD (Administrator & Editor) ---
    Route::middleware(['role:administrator,editor'])->group(function () {
        Route::get('master-data', [MasterDataController::class, 'index'])->name('master-data.index');
        Route::get('master-data/export', [MasterDataController::class, 'export'])->name('master-data.export');
        Route::post('master-data/import', [MasterDataController::class, 'import'])->name('master-data.import');
        Route::post('master-data', [MasterDataController::class, 'store'])->name('master-data.store');
        Route::put('master-data/{masterDatum}', [MasterDataController::class, 'update'])->name('master-data.update');
        Route::delete('master-data/{masterDatum}', [MasterDataController::class, 'destroy'])->name('master-data.destroy');
    });

    // --- GEOGRAFI PASIEN ---
    Route::prefix('patient-geography')->name('patient-geography.')->group(function () {
        Route::get('/',              [PatientGeographyController::class, 'index'])->name('index');
        Route::get('/api-data',      [PatientGeographyController::class, 'apiData'])->name('api-data');
        Route::get('/filter-kota',   [PatientGeographyController::class, 'filterKota'])->name('filter-kota');
        Route::get('/export',        [PatientGeographyController::class, 'export'])->name('export');
        Route::post('/import',       [PatientGeographyController::class, 'import'])->name('import');
        Route::post('/import-master',[PatientGeographyController::class, 'importMaster'])->name('import-master');
        Route::delete('/truncate',   [PatientGeographyController::class, 'truncate'])->name('truncate');
    });

    // --- MANAJEMEN AKUN (Hanya Administrator) ---
    Route::middleware(['role:administrator'])->prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::put('/{id}', [UserController::class, 'updateRole'])->name('updateRole');
        Route::delete('/{id}', [UserController::class, 'destroy'])->name('destroy');
    });
});