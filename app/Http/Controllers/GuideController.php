<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateGuideRequest;
use App\Models\Guide;
use App\Models\Booking;
use App\Http\Resources\GuideResource;
use Illuminate\Http\Request;

class GuideController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Guide::query()->with(['user', 'languages', 'specialties', 'cities', 'documents'])
            ->where('certificate_status', 'approved');

        if ($request->has('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }

        return GuideResource::collection($query->paginate(15));
    }

      public function guidePending(Request $request)
    {
        $query = Guide::query()->with(['user', 'languages', 'specialties', 'cities', 'documents'])
            ->where('certificate_status', 'pending');

        if ($request->has('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }

        return GuideResource::collection($query->paginate(15));
    }


     /**
     * Get all bookings for a specific guide
     */
    public function getGuideBookings($id, Request $request)
    {
        $guide = Guide::find($id);

        if (!$guide) {
            return response()->json([
                'message' => 'Guide not found'
            ], 404);
        }

        $query = Booking::with([
            'client' => function($q) {
                $q->with(['user']);
            },
            'tour' => function($q) {
                $q->with(['city', 'tourType', 'difficultyLevel', 'currency']);
            },
            'currency'
        ])->where('guide_id', $id);

        // Apply filters
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_status') && $request->payment_status) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->where('booking_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->where('booking_date', '<=', $request->date_to);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('client.user', function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('tour', function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%");
                });
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'booking_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $bookings = $query->paginate($perPage);

        // Get statistics
        $statistics = [
            'total_bookings' => Booking::where('guide_id', $id)->count(),
            'total_earnings' => Booking::where('guide_id', $id)
                ->where('payment_status', 'completed')
                ->sum('total_price'),
            'completed_bookings' => Booking::where('guide_id', $id)
                ->where('status', 'completed')
                ->where('payment_status', 'completed')
                ->count(),
            'pending_bookings' => Booking::where('guide_id', $id)
                ->where('status', 'pending')
                ->count(),
            'cancelled_bookings' => Booking::where('guide_id', $id)
                ->where('status', 'cancelled')
                ->count(),
            'confirmed_bookings' => Booking::where('guide_id', $id)
                ->where('status', 'confirmed')
                ->count(),
            'total_clients' => Booking::where('guide_id', $id)
                ->distinct('client_id')
                ->count('client_id'),
        ];

        // Format bookings for response
        $formattedBookings = $bookings->map(function($booking) {
            return [
                'id' => $booking->id,
                'booking_date' => $booking->booking_date,
                'number_of_persons' => $booking->number_of_persons,
                'total_price' => $booking->total_price,
                'status' => $booking->status,
                'payment_status' => $booking->payment_status,
                'special_requests' => $booking->special_requests,
                'created_at' => $booking->created_at,
                'completed_at' => $booking->completed_at,
                'cancelled_at' => $booking->cancelled_at,
                'client' => [
                    'id' => $booking->client->id,
                    'first_name' => $booking->client->user->first_name,
                    'last_name' => $booking->client->user->last_name,
                    'email' => $booking->client->user->email,
                    'phone' => $booking->client->user->phone,
                    'profile_image_url' => $booking->client->user->profile_image_url,
                    'nationality' => $booking->client->nationality,
                ],
                'tour' => [
                    'id' => $booking->tour->id,
                    'title' => $booking->tour->title,
                    'duration_formatted' => $booking->tour->duration_formatted,
                    'price' => $booking->tour->price,
                    'featured_image_url' => $booking->tour->featured_image_url,
                    'city' => $booking->tour->city ? [
                        'name' => $booking->tour->city->name,
                        'region' => $booking->tour->city->region
                    ] : null,
                ],
                'currency' => [
                    'code' => $booking->currency->code,
                    'symbol' => $booking->currency->symbol,
                ],
            ];
        });

        return response()->json([
            'data' => $formattedBookings,
            'guide' => [
                'id' => $guide->id,
                'name' => $guide->user->first_name . ' ' . $guide->user->last_name,
                'email' => $guide->user->email,
                'rating' => $guide->rating,
                'total_reviews' => $guide->total_reviews,
            ],
            'statistics' => $statistics,
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
                'from' => $bookings->firstItem(),
                'to' => $bookings->lastItem(),
            ],
            'links' => [
                'first' => $bookings->url(1),
                'last' => $bookings->url($bookings->lastPage()),
                'prev' => $bookings->previousPageUrl(),
                'next' => $bookings->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Get a specific booking details for a guide
     */
    public function getGuideBookingDetails($guideId, $bookingId)
    {
        $guide = Guide::find($guideId);

        if (!$guide) {
            return response()->json([
                'message' => 'Guide not found'
            ], 404);
        }

        $booking = Booking::with([
            'client' => function($q) {
                $q->with(['user']);
            },
            'tour' => function($q) {
                $q->with(['city', 'tourType', 'difficultyLevel', 'currency']);
            },
            'currency',
            'review'
        ])->where('guide_id', $guideId)
          ->where('id', $bookingId)
          ->first();

        if (!$booking) {
            return response()->json([
                'message' => 'Booking not found'
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $booking->id,
                'booking_date' => $booking->booking_date,
                'number_of_persons' => $booking->number_of_persons,
                'total_price' => $booking->total_price,
                'status' => $booking->status,
                'payment_status' => $booking->payment_status,
                'special_requests' => $booking->special_requests,
                'stripe_payment_id' => $booking->stripe_payment_id,
                'refund_reason' => $booking->refund_reason,
                'cancelled_at' => $booking->cancelled_at,
                'completed_at' => $booking->completed_at,
                'created_at' => $booking->created_at,
                'updated_at' => $booking->updated_at,
                'client' => [
                    'id' => $booking->client->id,
                    'first_name' => $booking->client->user->first_name,
                    'last_name' => $booking->client->user->last_name,
                    'email' => $booking->client->user->email,
                    'phone' => $booking->client->user->phone,
                    'profile_image_url' => $booking->client->user->profile_image_url,
                    'nationality' => $booking->client->nationality,
                    'preferred_language' => $booking->client->preferred_language,
                    'total_bookings' => $booking->client->total_bookings,
                ],
                'tour' => [
                    'id' => $booking->tour->id,
                    'title' => $booking->tour->title,
                    'description' => $booking->tour->description,
                    'duration_in_hours' => $booking->tour->duration_in_hours,
                    'duration_formatted' => $booking->tour->duration_formatted,
                    'price' => $booking->tour->price,
                    'max_persons' => $booking->tour->max_persons,
                    'min_persons' => $booking->tour->min_persons,
                    'featured_image_url' => $booking->tour->featured_image_url,
                    'city' => $booking->tour->city ? [
                        'name' => $booking->tour->city->name,
                        'region' => $booking->tour->city->region
                    ] : null,
                    'tour_type' => $booking->tour->tourType ? [
                        'name' => $booking->tour->tourType->name
                    ] : null,
                    'difficulty_level' => $booking->tour->difficultyLevel ? [
                        'name' => $booking->tour->difficultyLevel->name,
                        'level' => $booking->tour->difficultyLevel->level
                    ] : null,
                ],
                'currency' => [
                    'code' => $booking->currency->code,
                    'symbol' => $booking->currency->symbol,
                    'name' => $booking->currency->name,
                ],
                'review' => $booking->review ? [
                    'id' => $booking->review->id,
                    'rating' => $booking->review->rating,
                    'comment' => $booking->review->comment,
                    'created_at' => $booking->review->created_at,
                ] : null,
            ]
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Guide $guide)
    {
        $guide->load(['user', 'languages', 'specialties', 'cities', 'documents', 'availabilities', 'tours' => function ($q) {
            $q->where('status', 'published')->with('city');
        }]);

        return new GuideResource($guide);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGuideRequest $request, Guide $guide)
    {
        $user = $request->user();
        if ($user->user_type !== 'guide' || $guide->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $guide->update($request->validated());

        return new GuideResource($guide->load(['user', 'languages', 'specialties', 'cities', 'documents', 'availabilities', 'tours']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Guide $guide)
    {
        $user = request()->user();
        if ($user->user_type !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $guide->delete();
        return response()->json(null, 204);
    }
}
