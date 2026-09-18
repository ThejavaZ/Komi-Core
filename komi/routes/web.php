<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Admin login/logout (fuera del middleware auth)
Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.post');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

// Admin protegido (auth + admin)
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {

    // API interna para el panel (JSON)
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/users', [AdminDashboardController::class, 'users'])->name('users');
        Route::get('/posts', [AdminDashboardController::class, 'posts'])->name('posts');
        Route::get('/tags', [AdminDashboardController::class, 'tags'])->name('tags');
    });

    // Catch-all: serve the Vue SPA for any admin route
    Route::get('/{any?}', function () {
        return view('admin.app');
    })->where('any', '.*');
});
