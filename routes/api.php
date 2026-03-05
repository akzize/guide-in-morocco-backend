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

use App\Http\Controllers\CityController;
use App\Http\Controllers\LanguageController;

// Public Routes
Route::get('/cities', [CityController::class, 'index']);
Route::get('/languages', [LanguageController::class, 'index']);
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
        if ($user->user_type === 'guide') {
            $user->load('guide');
        } elseif ($user->user_type === 'client') {
            $user->load('client');
        }

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
    Route::post('/admin/guides/{guide}/decline', [\App\Http\Controllers\AdminGuideController::class, 'decline']);
});
