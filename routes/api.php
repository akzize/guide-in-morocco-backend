<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\TourController;
use App\Http\Controllers\TourStopController;
use App\Http\Controllers\TourInclusionController;
use App\Http\Controllers\GuideController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\LookupController;
use App\Http\Controllers\GuideBookingController;

Route::post('/register', [\App\Http\Controllers\AuthController::class, 'register']);
Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login']);

use App\Http\Controllers\CityController;
use App\Http\Controllers\LanguageController;

// Public Routes
Route::get('/cities', [CityController::class, 'index']);
Route::get('/languages', [LanguageController::class, 'index']);
Route::get('/lookups', [LookupController::class, 'index']);

// Public tours routes
Route::get('/tours', [TourController::class, 'index']);
Route::get('/tours/{tour}', [TourController::class, 'show']);

Route::get('/reviews/tours/{tour}', [ReviewController::class, 'tourReviews']);
Route::get('/reviews/guides/{guide}', [ReviewController::class, 'guideReviews']);

// Guide tours routes
Route::prefix('guide')->middleware('auth:sanctum')->group(function () {
    Route::get('/tours', [TourController::class, 'guideTours']);
    Route::get('/tours/{tour}', [TourController::class, 'show']);
    Route::post('/tours', [TourController::class, 'store']);
    Route::put('/tours/{tour}', [TourController::class, 'update']);
    Route::delete('/tours/{tour}', [TourController::class, 'destroy']);

    // Stops
    Route::get('/tours/{tour}/stops', [TourStopController::class, 'index']);
    Route::post('/tours/{tour}/stops', [TourStopController::class, 'store']);
    Route::put('/tours/{tour}/stops/{stop}', [TourStopController::class, 'update']);
    Route::delete('/tours/{tour}/stops/{stop}', [TourStopController::class, 'destroy']);

        // Inclusions
    Route::get('/tours/{tour}/inclusions', [TourInclusionController::class, 'index']);
    Route::post('/tours/{tour}/inclusions', [TourInclusionController::class, 'store']);
    Route::put('/tours/{tour}/inclusions/{inclusion}', [TourInclusionController::class, 'update']);
    Route::delete('/tours/{tour}/inclusions/{inclusion}', [TourInclusionController::class, 'destroy']);

    // Guide booking routes

    Route::get('/completed-bookings', [GuideBookingController::class, 'index']);
    Route::get('/completed-bookings/{id}', [GuideBookingController::class, 'show']);
    Route::get('/booking-statistics', [GuideBookingController::class, 'statistics']);
    Route::get('/export-bookings', [GuideBookingController::class, 'export']);

});


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
    Route::get('/guides', [GuideController::class, 'index']);
    Route::get('/guides/pending', [GuideController::class, 'guidePending']);
    Route::get('/guides/{guide}', [GuideController::class, 'show']);

    // Admin routes for guide bookings
    Route::get('/admin/guides/{id}/bookings', [GuideController::class, 'getGuideBookings']);
    Route::get('/admin/guides/{guideId}/bookings/{bookingId}', [GuideController::class, 'getGuideBookingDetails']);

    // client Protected Routes
    Route::get('/clients', [\App\Http\Controllers\ClientController::class, 'index']);
    Route::get('/clients/{client}', [\App\Http\Controllers\ClientController::class, 'show']);

    // Route::apiResource('tours', TourController::class)->except(['index', 'show']);

    // Booking Protected Routes
    Route::apiResource('bookings', BookingController::class);

    // Client booking routes
    Route::prefix('client')->group(function () {
        Route::get('/bookings', [BookingController::class, 'clientBooking']);
        Route::get('/bookings/{id}', [BookingController::class, 'ClientBookingDetails']);
        Route::post('/bookings/{id}/cancel', [BookingController::class, 'cancel']);
    });

    // Review Protected Routes
    Route::apiResource('reviews', ReviewController::class)->except(['index', 'show']);

    // Admin Routes
    Route::post('/admin/guides/{guide}/activate', [\App\Http\Controllers\AdminGuideController::class, 'activate']);
    Route::post('/admin/guides/{guide}/decline', [\App\Http\Controllers\AdminGuideController::class, 'decline']);
    Route::post('/admin/guides/{guide}/toggle-status', [\App\Http\Controllers\AdminGuideController::class, 'toggleStatus']);
    Route::post('/admin/clients/{client}/toggle-status', [\App\Http\Controllers\AdminUserController::class, 'toggleStatus']);
});
