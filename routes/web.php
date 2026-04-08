<?php

use App\Http\Controllers\SsoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [SsoController::class, 'redirect'])->name('login');

Route::prefix('auth/sso')->name('sso.')->group(function () {
    Route::get('/redirect', [SsoController::class, 'redirect'])->name('redirect');
    Route::get('/callback', [SsoController::class, 'callback'])->name('callback');
    Route::post('/refresh-token', [SsoController::class, 'refreshToken'])->name('refresh-token');
    Route::post('/logout-trigger', [SsoController::class, 'triggerBackchannelLogout'])
        ->middleware(['auth', 'role:koordinator_ta,admin_prodi'])
        ->name('logout-trigger');
    Route::post('/logout-notification', [SsoController::class, 'receiveBackchannelLogout'])
        ->name('logout-notification');
});

Route::post('/logout', [SsoController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/dashboard', [SsoController::class, 'dashboard'])
    ->middleware(['auth', 'role:mahasiswa,dosen_pembimbing,koordinator_ta,admin_prodi'])
    ->name('dashboard');
