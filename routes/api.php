<?php

use App\Http\Controllers\AppsController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\ScraperController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SocialiteController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('auth')->group(function() {
    Route::get('redirect', [SocialiteController::class, 'redirect']);
    Route::get('callback', [SocialiteController::class, 'callback']);
});

Route::prefix('user')->middleware(['loggedin'])->group(function() {
    Route::get('me', [UserController::class, 'me']);
    Route::get('logout', [UserController::class, 'logout']);
});

Route::prefix('store')->middleware(['loggedin'])->group(function() {
    Route::post('search', [ScraperController::class, 'scrape']);
});

Route::prefix('apps')->group(function() {
    Route::post('search', [AppsController::class, 'search']);
});

Route::prefix('reports')->middleware(['loggedin'])->group(function() {
    Route::post('create', [ReportsController::class, 'store']);
});