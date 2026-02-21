<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\TourController;
use App\Http\Controllers\GuideController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\LookupController;

Route::post('/register', [\App\Http\Controllers\AuthController::class, 'register']);
Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login']);

// Public Routes
Route::get('/lookups', [LookupController::class, 'index']);
Route::get('/tours', [TourController::class, 'index']);
Route::get('/tours/{tour}', [TourController::class, 'show']);
Route::get('/guides', [GuideController::class, 'index']);
Route::get('/guides/{guide}', [GuideController::class, 'show']);
Route::get('/reviews/tours/{tour}', [ReviewController::class, 'tourReviews']);
Route::get('/reviews/guides/{guide}', [ReviewController::class, 'guideReviews']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        $user = $request->user();
        $user->load($user->user_type === 'guide' ? 'guide' : ($user->user_type === 'client' ? 'client' : 'admin_user'));
        return current($user);
    });

    // Guide Protected Routes
    Route::apiResource('tours', TourController::class)->except(['index', 'show']);
    
    // Booking Protected Routes
    Route::apiResource('bookings', BookingController::class);
    
    // Review Protected Routes
    Route::apiResource('reviews', ReviewController::class)->except(['index', 'show']);

    // Admin Routes
    Route::post('/admin/guides/{guide}/activate', [\App\Http\Controllers\AdminGuideController::class, 'activate']);
});
