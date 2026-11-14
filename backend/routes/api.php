<?php

use App\Http\Controllers\Api\RecommendationController;
use App\Http\Controllers\Api\CityController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DestinationController;

// Public routes (tanpa login)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']); 

// Public City Routes
Route::get('/cities', [CityController::class, 'index']);
Route::get('/cities/{id}', [CityController::class, 'show']); 

Route::get('/destinations', [DestinationController::class, 'index']);
Route::get('/destinations/{id}', [DestinationController::class, 'show']); 

// Recommendation Route
Route::post('/recommendations', [RecommendationController::class, 'recommend']);

// Protected routes (wajib login)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);

    // Endpoint bawaan Laravel, tetap dipertahankan
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
