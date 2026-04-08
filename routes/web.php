<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\SsoController;
use App\Http\Controllers\TaProjectController;
use App\Http\Controllers\TaSupervisionController;
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

Route::middleware(['auth', 'role:mahasiswa,dosen_pembimbing,koordinator_ta,admin_prodi'])->group(function () {
    Route::get('/ta-projects/create', [TaProjectController::class, 'create'])
        ->middleware('role:mahasiswa')
        ->name('ta-projects.create');
    Route::post('/ta-projects', [TaProjectController::class, 'store'])
        ->middleware('role:mahasiswa')
        ->name('ta-projects.store');
    Route::get('/ta-projects/{project}/edit', [TaProjectController::class, 'edit'])
        ->middleware('role:mahasiswa')
        ->name('ta-projects.edit');
    Route::put('/ta-projects/{project}', [TaProjectController::class, 'update'])
        ->middleware('role:mahasiswa')
        ->name('ta-projects.update');
    Route::post('/ta-projects/{project}/submit', [TaProjectController::class, 'submit'])
        ->middleware('role:mahasiswa')
        ->name('ta-projects.submit');
    Route::post('/ta-projects/{project}/review', [TaProjectController::class, 'review'])
        ->middleware('role:dosen_pembimbing,koordinator_ta,admin_prodi')
        ->name('ta-projects.review');
    Route::post('/ta-projects/{project}/supervisions', [TaSupervisionController::class, 'store'])
        ->middleware('role:mahasiswa')
        ->name('ta-supervisions.store');
    Route::post('/ta-supervisions/{supervision}/review', [TaSupervisionController::class, 'review'])
        ->middleware('role:dosen_pembimbing,koordinator_ta,admin_prodi')
        ->name('ta-supervisions.review');
});

// ── Admin routes ──────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin_prodi'])->group(function () {
    Route::get('/',                               [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/users',                          [AdminController::class, 'users'])->name('users');
    Route::put('/users/{user}/roles',             [AdminController::class, 'updateUserRoles'])->name('users.roles');
    Route::get('/templates',                      [AdminController::class, 'templates'])->name('templates');
    Route::post('/templates',                     [AdminController::class, 'storeTemplate'])->name('templates.store');
    Route::put('/templates/{id}',                 [AdminController::class, 'updateTemplate'])->name('templates.update');
    Route::delete('/templates/{id}',              [AdminController::class, 'destroyTemplate'])->name('templates.destroy');
    Route::get('/audit-log',                      [AdminController::class, 'auditLog'])->name('audit-log');
});
