<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;



Route::get('/auth/login', [AuthController::class, 'login'])->name('login');
Route::post('auth/login', [AuthController::class, 'store'])->name('login.store');
Route::post('auth/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function(){

    # --- Home ---
    Route::get('/',[HomeController::class, 'index'])->name('home');
    # --- Home ---

    # --- Communities ---
    Route::get('/communities', [CommunityController::class, 'index'])->name('communities.index');
    Route::get('/communities/{id}', [CommunityController::class, 'show'])->name('communities.show');
    Route::get('/communities/create', [CommunityController::class, 'create'])->name('communities.create');
    # --- Communities ---
});
