<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AppsController;
use App\Http\Controllers\DevsController;
use App\Http\Controllers\MiscController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SlackController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\ScraperController;
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
    Route::get('{id}', [AppsController::class, 'show']);
    Route::post('search', [AppsController::class, 'search']);
});

Route::prefix('reports')->middleware(['loggedin'])->group(function() {
    Route::get('mine', [ReportsController::class, 'mine']);
    Route::post('create', [ReportsController::class, 'store']);
});

Route::prefix('misc')->group(function() {
    Route::get('home', [MiscController::class, 'getMainPageItems']);
});

Route::prefix('devs')->group(function() {
    Route::get('{id}', [DevsController::class, 'show']);
});

Route::prefix('slack')->middleware(['slackrequest'])->group(function() {
    Route::post('interaction', [SlackController::class, 'interact']);
});