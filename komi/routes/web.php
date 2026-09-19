<?php

use App\Http\Controllers\Admin\Admin2FAController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminAutoModController;
use App\Http\Controllers\Admin\AdminConfigController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminExportController;
use App\Http\Controllers\Admin\AdminModerationController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Admin\AdminPostController;
use App\Http\Controllers\Admin\AdminSearchController;
use App\Http\Controllers\Admin\AdminSessionController;
use App\Http\Controllers\Admin\AdminTagController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Admin login/logout (fuera del middleware auth)
Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.post');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

// 2FA verification (during login flow)
Route::get('/admin/2fa', [AdminAuthController::class, 'show2FALogin'])->name('admin.2fa.show');

// Admin protegido (auth + admin)
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {

    // API interna para el panel (JSON)
    Route::prefix('api')->name('api.')->group(function () {
        // Dashboard
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Users
        Route::get('/users', [AdminDashboardController::class, 'users'])->name('users');
        Route::get('/users/{id}', [AdminDashboardController::class, 'showUser'])->name('users.show');
        Route::put('/users/{id}', [AdminDashboardController::class, 'updateUser'])->name('users.update');
        Route::post('/users/{id}/warn', [AdminDashboardController::class, 'warnUser'])->name('users.warn');
        Route::post('/users/bulk', [AdminDashboardController::class, 'bulkUsers'])->name('users.bulk');

        // Posts (dashboard defaults)
        Route::get('/posts', [AdminDashboardController::class, 'posts'])->name('posts');
        Route::delete('/posts/{id}', [AdminDashboardController::class, 'destroyPost'])->name('posts.destroy');
        Route::post('/posts/bulk', [AdminDashboardController::class, 'bulkPosts'])->name('posts.bulk');

        // Posts management (extended)
        Route::put('/posts/{id}', [AdminPostController::class, 'update'])->name('posts.update');
        Route::get('/posts/trashed', [AdminPostController::class, 'trashed'])->name('posts.trashed');
        Route::post('/posts/{id}/restore', [AdminPostController::class, 'restore'])->name('posts.restore');

        // Tags (read from dashboard)
        Route::get('/tags', [AdminDashboardController::class, 'tags'])->name('tags');

        // Tags CRUD
        Route::post('/tags', [AdminTagController::class, 'store'])->name('tags.store');
        Route::put('/tags/{id}', [AdminTagController::class, 'update'])->name('tags.update');
        Route::delete('/tags/{id}', [AdminTagController::class, 'destroy'])->name('tags.destroy');

        // Reports
        Route::get('/reports', [AdminDashboardController::class, 'reports'])->name('reports');
        Route::put('/reports/{id}', [AdminDashboardController::class, 'resolveReport'])->name('reports.resolve');

        // Communities
        Route::get('/communities', [AdminDashboardController::class, 'communities'])->name('communities');
        Route::delete('/communities/{id}', [AdminDashboardController::class, 'destroyCommunity'])->name('communities.destroy');

        // Moderation (basic from dashboard)
        Route::get('/moderation', [AdminDashboardController::class, 'moderationQueue'])->name('moderation');
        Route::post('/moderation/{id}/action', [AdminDashboardController::class, 'moderateContent'])->name('moderation.action');

        // Moderation (extended)
        Route::get('/moderation/search', [AdminModerationController::class, 'index'])->name('moderation.search');
        Route::get('/moderation/user/{userId}', [AdminModerationController::class, 'showUserHistory'])->name('moderation.user-history');
        Route::post('/users/{id}/shadowban', [AdminModerationController::class, 'toggleShadowban'])->name('users.shadowban');
        Route::post('/users/{id}/temp-ban', [AdminModerationController::class, 'tempBan'])->name('users.temp-ban');
        Route::post('/posts/{id}/restore', [AdminModerationController::class, 'restorePost'])->name('posts.restore-moderation');

        // Auto-mod
        Route::get('/auto-mod', [AdminAutoModController::class, 'index'])->name('auto-mod.index');
        Route::post('/auto-mod', [AdminAutoModController::class, 'store'])->name('auto-mod.store');
        Route::put('/auto-mod/{id}', [AdminAutoModController::class, 'update'])->name('auto-mod.update');
        Route::delete('/auto-mod/{id}', [AdminAutoModController::class, 'destroy'])->name('auto-mod.destroy');

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

        // System - Jobs queue
        Route::get('/system/jobs', [AdminDashboardController::class, 'jobs'])->name('system.jobs');
        Route::post('/system/jobs/{id}/retry', [AdminDashboardController::class, 'retryJob'])->name('system.jobs.retry');
        Route::delete('/system/jobs/{id}', [AdminDashboardController::class, 'deleteJob'])->name('system.jobs.delete');
        Route::post('/system/jobs/clear', [AdminDashboardController::class, 'clearJobs'])->name('system.jobs.clear');

        // System - Cache management
        Route::post('/system/cache/clear', [AdminDashboardController::class, 'clearCache'])->name('system.cache.clear');

        // 2FA
        Route::get('/2fa/status', [Admin2FAController::class, 'status'])->name('2fa.status');
        Route::get('/2fa/setup', [Admin2FAController::class, 'setup'])->name('2fa.setup');
        Route::post('/2fa/enable', [Admin2FAController::class, 'enable'])->name('2fa.enable');
        Route::post('/2fa/disable', [Admin2FAController::class, 'disable'])->name('2fa.disable');
        Route::post('/2fa/verify', [Admin2FAController::class, 'verify'])->name('2fa.verify');

        // Export
        Route::get('/export/users', [AdminExportController::class, 'users'])->name('export.users');
        Route::get('/export/posts', [AdminExportController::class, 'posts'])->name('export.posts');
        Route::get('/export/reports', [AdminExportController::class, 'reports'])->name('export.reports');
        Route::get('/export/users/{id}', [AdminExportController::class, 'singleUser'])->name('export.users.single');

        // Configuration
        Route::get('/config', [AdminConfigController::class, 'index'])->name('config.index');
        Route::put('/config', [AdminConfigController::class, 'update'])->name('config.update');
        Route::get('/config/rate-limits', [AdminConfigController::class, 'rateLimits'])->name('config.rate-limits');
        Route::put('/config/rate-limits', [AdminConfigController::class, 'updateRateLimits'])->name('config.rate-limits.update');

        // Sessions
        Route::get('/sessions', [AdminSessionController::class, 'index'])->name('sessions.index');
        Route::delete('/sessions/{id}', [AdminSessionController::class, 'destroy'])->name('sessions.destroy');

        // Global search
        Route::get('/search', [AdminSearchController::class, 'search'])->name('search');

        // Notifications
        Route::get('/notifications/unread', [AdminNotificationController::class, 'unread'])->name('notifications.unread');
    });

    // Catch-all: serve the Vue SPA for any admin route
    Route::get('/{any?}', function () {
        return view('admin.app');
    })->where('any', '.*');
});
