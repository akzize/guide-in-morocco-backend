<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Models\Booking;
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
            'status' => 'pending',
            'payment_status' => 'pending'
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
