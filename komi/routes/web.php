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
        Route::get('/users/{id}', [AdminDashboardController::class, 'showUser'])->name('users.show');
        Route::put('/users/{id}', [AdminDashboardController::class, 'updateUser'])->name('users.update');
        Route::get('/posts', [AdminDashboardController::class, 'posts'])->name('posts');
        Route::delete('/posts/{id}', [AdminDashboardController::class, 'destroyPost'])->name('posts.destroy');
        Route::get('/tags', [AdminDashboardController::class, 'tags'])->name('tags');
        Route::get('/reports', [AdminDashboardController::class, 'reports'])->name('reports');
        Route::put('/reports/{id}', [AdminDashboardController::class, 'resolveReport'])->name('reports.resolve');
        Route::get('/communities', [AdminDashboardController::class, 'communities'])->name('communities');
        Route::delete('/communities/{id}', [AdminDashboardController::class, 'destroyCommunity'])->name('communities.destroy');

        // Moderation
        Route::get('/moderation', [AdminDashboardController::class, 'moderationQueue'])->name('moderation');
        Route::post('/moderation/{id}/action', [AdminDashboardController::class, 'moderateContent'])->name('moderation.action');

        // Users extra
        Route::post('/users/{id}/warn', [AdminDashboardController::class, 'warnUser'])->name('users.warn');
        Route::post('/users/bulk', [AdminDashboardController::class, 'bulkUsers'])->name('users.bulk');
        Route::post('/posts/bulk', [AdminDashboardController::class, 'bulkPosts'])->name('posts.bulk');

        // Appeals
        Route::get('/appeals', [AdminDashboardController::class, 'appeals'])->name('appeals');
        Route::put('/appeals/{id}', [AdminDashboardController::class, 'resolveAppeal'])->name('appeals.resolve');

        // Admin logs
        Route::get('/logs', [AdminDashboardController::class, 'logs'])->name('logs');

        // Analytics
        Route::get('/analytics', [AdminDashboardController::class, 'analytics'])->name('analytics');

        // System
        Route::get('/system/health', [AdminDashboardController::class, 'systemHealth'])->name('system.health');
        Route::get('/system/errors', [AdminDashboardController::class, 'systemErrors'])->name('system.errors');
    });

    // Catch-all: serve the Vue SPA for any admin route
    Route::get('/{any?}', function () {
        return view('admin.app');
    })->where('any', '.*');
});
