<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Models\Booking;
use App\Models\Client;
use App\Http\Resources\BookingResource;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    /**
     * Display a listing of bookings for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Booking::query()->with(['client.user', 'guide.user', 'tour', 'currency', 'review']);

        if ($user->user_type === 'client') {
            $query->where('client_id', $user->client->id);
        } elseif ($user->user_type === 'guide') {
            $query->where('guide_id', $user->guide->id);
        }

        return BookingResource::collection($query->orderBy('booking_date', 'desc')->paginate(15));
    }

     /**
     * Get the authenticated client's bookings
     */
    public function clientBooking(Request $request)
    {
        $user = $request->user();
        // ✅ get client from user_id
        $client = Client::where('user_id', $user->id)->firstOrFail();

        if (!$client) {
            return response()->json([
                'message' => 'Client profile not found'
            ], 404);
        }

        $query = Booking::with([
            'tour' => function($q) {
                $q->with([
                    'city',
                    'tourType',
                    'difficultyLevel',
                    'currency'
                ]);
            },
            'guide' => function($q) {
                $q->with(['user', 'cities']);
            },
            'currency'
        ])->where('client_id', $client->id);

        \Log::info('Client id: ' . $client->id);
        \Log::info('Bookings query: ' . $query->toSql());

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

        // Sorting
        $sortBy = $request->get('sort_by', 'booking_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 10);
        $bookings = $query->paginate($perPage);

        // Add statistics
        $statistics = [
            'total_bookings' => $query->count(),
            'total_spent' => $query->sum('total_price'),
            'completed_bookings' => Booking::where('client_id', $client->id)
                ->where('status', 'completed')
                ->count(),
            'upcoming_bookings' => Booking::where('client_id', $client->id)
                ->where('booking_date', '>=', now())
                ->where('status', 'confirmed')
                ->count(),
            'cancelled_bookings' => Booking::where('client_id', $client->id)
                ->where('status', 'cancelled')
                ->count(),
        ];

        return response()->json([
            'data' => $bookings->items(),
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
            'statistics' => $statistics
        ]);
    }


     /**
     * Get a specific booking details
     */
    public function ClientBookingDetails(Request $request, $id)
    {
        $user = $request->user();
        // ✅ get client from user_id
        $client = Client::where('user_id', $user->id)->firstOrFail();

        if (!$client) {
            return response()->json([
                'message' => 'Client profile not found'
            ], 404);
        }

        $booking = Booking::with([
            'tour' => function($q) {
                $q->with([
                    'city',
                    'tourType',
                    'difficultyLevel',
                    'currency',
                    'guide' => function($q) {
                        $q->with(['user', 'cities']);
                    }
                ]);
            },
            'guide' => function($q) {
                $q->with(['user', 'cities', 'languages']);
            },
            'currency',
            'review'
        ])->where('client_id', $client->id)
          ->where('id', $id)
          ->first();

        if (!$booking) {
            return response()->json([
                'message' => 'Booking not found'
            ], 404);
        }

        // Add additional booking details
        $bookingDetails = [
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
            'tour' => $booking->tour ? [
                'id' => $booking->tour->id,
                'title' => $booking->tour->title,
                'description' => $booking->tour->description,
                'duration_in_hours' => $booking->tour->duration_in_hours,
                'duration_formatted' => $booking->tour->duration_formatted,
                'price' => $booking->tour->price,
                'max_persons' => $booking->tour->max_persons,
                'min_persons' => $booking->tour->min_persons,
                'featured_image_url' => $booking->tour->featured_image_url,
                'average_rating' => $booking->tour->average_rating,
                'total_reviews' => $booking->tour->total_reviews,
                'city' => $booking->tour->city ? [
                    'id' => $booking->tour->city->id,
                    'name' => $booking->tour->city->name,
                    'region' => $booking->tour->city->region
                ] : null,
                'tour_type' => $booking->tour->tourType ? [
                    'id' => $booking->tour->tourType->id,
                    'name' => $booking->tour->tourType->name,
                    'slug' => $booking->tour->tourType->slug
                ] : null,
                'difficulty_level' => $booking->tour->difficultyLevel ? [
                    'id' => $booking->tour->difficultyLevel->id,
                    'name' => $booking->tour->difficultyLevel->name,
                    'level' => $booking->tour->difficultyLevel->level
                ] : null,
                'currency' => $booking->tour->currency ? [
                    'code' => $booking->tour->currency->code,
                    'symbol' => $booking->tour->currency->symbol,
                    'name' => $booking->tour->currency->name
                ] : null,
                'guide' => $booking->tour->guide ? [
                    'id' => $booking->tour->guide->id,
                    'user' => $booking->tour->guide->user ? [
                        'first_name' => $booking->tour->guide->user->first_name,
                        'last_name' => $booking->tour->guide->user->last_name,
                        'email' => $booking->tour->guide->user->email,
                        'phone' => $booking->tour->guide->user->phone,
                        'profile_image_url' => $booking->tour->guide->user->profile_image_url
                    ] : null
                ] : null
            ] : null,
            'guide' => $booking->guide ? [
                'id' => $booking->guide->id,
                'years_experience' => $booking->guide->years_experience,
                'rating' => $booking->guide->rating,
                'total_reviews' => $booking->guide->total_reviews,
                'hourly_rate_from' => $booking->guide->hourly_rate_from,
                'daily_rate' => $booking->guide->daily_rate,
                'user' => $booking->guide->user ? [
                    'first_name' => $booking->guide->user->first_name,
                    'last_name' => $booking->guide->user->last_name,
                    'email' => $booking->guide->user->email,
                    'phone' => $booking->guide->user->phone,
                    'profile_image_url' => $booking->guide->user->profile_image_url
                ] : null,
                'main_city' => $booking->guide->location ? [
                    'name' => $booking->guide->location,
                ] : null,
                'languages' => $booking->guide->languages->map(function($language) {
                    return [
                        'name' => $language->name,
                        'code' => $language->code,
                        'proficiency_level' => $language->pivot->proficiency_level
                    ];
                })
            ] : null,
            'currency' => $booking->currency ? [
                'code' => $booking->currency->code,
                'symbol' => $booking->currency->symbol,
                'name' => $booking->currency->name,
                'exchange_rate' => $booking->currency->exchange_rate
            ] : null,
            'review' => $booking->review ? [
                'id' => $booking->review->id,
                'rating' => $booking->review->rating,
                'comment' => $booking->review->comment,
                'created_at' => $booking->review->created_at
            ] : null,
            'can_cancel' => $this->canCancelBooking($booking),
            'can_review' => $this->canReviewBooking($booking),
            'timeline' => $this->getBookingTimeline($booking)
        ];

        return response()->json([
            'data' => $bookingDetails
        ]);
    }


     /**
     * Cancel a booking
     */
    public function cancel(Request $request, $id)
    {
        $client = $request->user();

        if (!$client) {
            return response()->json([
                'message' => 'Client profile not found'
            ], 404);
        }

        $booking = Booking::where('client_id', $client->id)
            ->where('id', $id)
            ->first();

        if (!$booking) {
            return response()->json([
                'message' => 'Booking not found'
            ], 404);
        }

        if (!$this->canCancelBooking($booking)) {
            return response()->json([
                'message' => 'This booking cannot be cancelled'
            ], 400);
        }

        $booking->status = 'cancelled';
        $booking->cancelled_at = now();

        if ($request->has('refund_reason')) {
            $booking->refund_reason = $request->refund_reason;
        }

        $booking->save();

        return response()->json([
            'message' => 'Booking cancelled successfully',
            'data' => $booking
        ]);
    }

     /**
     * Check if booking can be cancelled
     */
    private function canCancelBooking($booking)
    {
        // Can only cancel pending or confirmed bookings
        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            return false;
        }

        // Can cancel at least 24 hours before booking date
        $bookingDate = \Carbon\Carbon::parse($booking->booking_date);
        $now = \Carbon\Carbon::now();

        return $now->diffInHours($bookingDate, false) >= 24;
    }

    /**
     * Check if booking can be reviewed
     */
    private function canReviewBooking($booking)
    {
        // Can only review completed bookings that don't have a review yet
        return $booking->status === 'completed' && !$booking->review;
    }

    /**
     * Get booking timeline
     */
    private function getBookingTimeline($booking)
    {
        $timeline = [
            [
                'status' => 'Booking Created',
                'date' => $booking->created_at,
                'icon' => 'calendar-plus',
                'color' => 'blue'
            ]
        ];

        if ($booking->status === 'confirmed') {
            $timeline[] = [
                'status' => 'Booking Confirmed',
                'date' => $booking->updated_at,
                'icon' => 'check-circle',
                'color' => 'green'
            ];
        }

        if ($booking->status === 'completed') {
            $timeline[] = [
                'status' => 'Tour Completed',
                'date' => $booking->completed_at ?? $booking->updated_at,
                'icon' => 'trophy',
                'color' => 'gold'
            ];
        }

        if ($booking->status === 'cancelled') {
            $timeline[] = [
                'status' => 'Booking Cancelled',
                'date' => $booking->cancelled_at,
                'icon' => 'x-circle',
                'color' => 'red'
            ];
        }

        return $timeline;
    }


    /**
     * Store a newly created reservation.
     */
    public function store(StoreBookingRequest $request)
    {
        $user = $request->user();
        if ($user->user_type !== 'client') {
            return response()->json(['message' => 'Only clients can book tours'], 403);
        }

        $booking = Booking::create(array_merge($request->validated(), [
            'client_id' => $user->client->id,
            'status' => 'completed',
            'payment_status' => 'completed'
        ]));

        return new BookingResource($booking->load(['guide.user', 'tour', 'currency']));
    }

    /**
     * Display the specified booking.
     */
    public function show(Booking $booking)
    {
        $user = request()->user();
        // Check authorization
        if ($user->user_type === 'client' && $booking->client_id !== $user->client->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ($user->user_type === 'guide' && $booking->guide_id !== $user->guide->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $booking->load(['client.user', 'guide.user', 'tour.city', 'currency', 'review']);
        return new BookingResource($booking);
    }

    /**
     * Update the booking status (e.g. guide accepts, client cancels).
     */
    public function update(UpdateBookingRequest $request, Booking $booking)
    {
        $user = $request->user();

        // Basic authorization validation
        if ($user->user_type === 'client' && $booking->client_id !== $user->client->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ($user->user_type === 'guide' && $booking->guide_id !== $user->guide->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $booking->update($request->validated());

        return new BookingResource($booking->load(['client.user', 'guide.user', 'tour']));
    }

    /**
     * Remove or fully cancel a booking.
     */
    public function destroy(Booking $booking)
    {
        $user = request()->user();
        if ($user->user_type === 'client' && $booking->client_id !== $user->client->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $booking->delete();
        return response()->json(null, 204);
    }
}
