<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;



Route::get('/auth/login', [AuthController::class, 'login'])->name('login');
Route::post('auth/login', [AuthController::class, 'store'])->name('login.store');
Route::post('auth/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function(){

    # --- Home ---
    Route::get('/',[HomeController::class, 'index'])->name('home');
    # --- Home ---

    # --- Communities ---
    Route::prefix('communities')->group(function(){
        Route::controller(CommunityController::class)->group(function(){
            Route::get('/', 'index')->name('communities.index');
            Route::get('/create', 'create')->name('communities.create');
            Route::get('/{id}/show', 'show')->name('communities.show');

            Route::get('{id}/edit', 'edit')->name('communities.edit');
        });
    });

    Route::prefix('communities-members')->group(function(){
        Route::controller(CommunityController::class)->group(function(){
            Route::get('/', 'index')->name('communities.index');
            Route::get('/create', 'create')->name('communities.create');
            Route::get('/{id}/show', 'show')->name('communities.show');
        });
    });

    Route::prefix('comments')->group(function(){
        Route::controller(CommunityController::class)->group(function(){
            Route::get('/', 'index')->name('communities.index');
            Route::get('/create', 'create')->name('communities.create');
            Route::get('/{id}/show', 'show')->name('communities.show');
        });
    });

    Route::prefix('chats')->group(function(){
        Route::controller(CommunityController::class)->group(function(){
            Route::get('/', 'index')->name('communities.index');
            Route::get('/create', 'create')->name('communities.create');
            Route::get('/{id}/show', 'show')->name('communities.show');
        });
    });
    
    Route::prefix('likes')->group(function(){
        Route::controller(CommunityController::class)->group(function(){
            Route::get('/', 'index')->name('communities.index');
            Route::get('/create', 'create')->name('communities.create');
            Route::get('/{id}/show', 'show')->name('communities.show');
        });
    });

    Route::prefix('messages')->group(function(){
        Route::controller(CommunityController::class)->group(function(){
            Route::get('/', 'index')->name('communities.index');
            Route::get('/create', 'create')->name('communities.create');
            Route::get('/{id}/show', 'show')->name('communities.show');
        });
    });

    Route::prefix('posts')->group(function(){
        Route::controller(CommunityController::class)->group(function(){
            Route::get('/', 'index')->name('communities.index');
            Route::get('/create', 'create')->name('communities.create');
            Route::get('/{id}/show', 'show')->name('communities.show');
        });
    });

    Route::prefix('reports')->group(function(){
        Route::controller(CommunityController::class)->group(function(){
            Route::get('/', 'index')->name('communities.index');
            Route::get('/create', 'create')->name('communities.create');
            Route::get('/{id}/show', 'show')->name('communities.show');
        });
    });

    Route::prefix('users')->group(function(){
        Route::controller(UserController::class)->group(function(){
            Route::get('/', 'index')->name('users.index');
        });
    });
});