<?php

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