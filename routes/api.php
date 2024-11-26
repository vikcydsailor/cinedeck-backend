<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\WatchlistController;

use App\Http\Controllers\UserPreferenceController;
use App\Http\Controllers\WatchHistoryController;
use App\Http\Controllers\RecommendationController;


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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/profile', [AuthController::class, 'profile'])->middleware('auth:sanctum');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::middleware('auth:sanctum')->put('/change-password', [ProfileController::class, 'changePassword']);
Route::middleware('auth:sanctum')->put('/edit-profile', [ProfileController::class, 'editProfile']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/movies/{id}/favorite', [MovieController::class, 'toggleFavorite']);
    Route::get('/movies/{id}/favorite-status', [MovieController::class, 'isFavorite']);
    Route::get('/movies/{id}/status', [MovieController::class, 'getStatus']); // For fetching status
    Route::post('/movies/{id}/status', [MovieController::class, 'updateStatus']);
    Route::post('/watchlists', [WatchlistController::class, 'store']);
    Route::get('/favorites', [MovieController::class, 'getFavorites']);
    Route::post('/movies/{id}/watch-history', [MovieController::class, 'storeWatchHistory']);

});

Route::get('/watchlists', [WatchlistController::class, 'index']);
Route::get('/watchlist/{id}', [WatchlistController::class, 'show']);

Route::get('/recommendations/{userId}/{type}/{category}', [RecommendationController::class, 'getRecommendations']);




