<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Guide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GuideBookingController extends Controller
{
    /**
     * Get the authenticated guide's completed bookings
     */
    public function index(Request $request)
    {
        $guide = $this->getAuthenticatedGuide();
        if (!$guide) {
            return response()->json([
                'message' => 'Guide profile not found'
            ], 404);
        }

        $query = Booking::with([
            'client' => function($q) {
                $q->with(['user']);
            },
            'tour' => function($q) {
                $q->with([
                    'city',
                    'tourType',
                    'difficultyLevel',
                    'currency'
                ]);
            },
            'currency',
            'review'
        ])->where('guide_id', $guide->id)
          ->where('status', 'completed')
          ->where('payment_status', 'completed');

        // Apply additional filters
        if ($request->has('date_from') && $request->date_from) {
            $query->where('booking_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->where('booking_date', '<=', $request->date_to);
        }

        if ($request->has('tour_id') && $request->tour_id) {
            $query->where('tour_id', $request->tour_id);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('client.user', function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'completed_at');
        $sortOrder = $request->get('sort_order', 'desc');

        if ($sortBy === 'completed_at') {
            $query->orderBy('completed_at', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Pagination
        $perPage = $request->get('per_page', 10);
        $bookings = $query->paginate($perPage);

        // Add statistics
        $statistics = [
            'total_completed_bookings' => Booking::where('guide_id', $guide->id)
                ->where('status', 'completed')
                ->where('payment_status', 'completed')
                ->count(),
            'total_earnings' => Booking::where('guide_id', $guide->id)
                ->where('status', 'completed')
                ->where('payment_status', 'completed')
                ->sum('total_price'),
            'total_clients_served' => Booking::where('guide_id', $guide->id)
                ->where('status', 'completed')
                ->where('payment_status', 'completed')
                ->distinct('client_id')
                ->count('client_id'),
            'average_rating' => $guide->rating ?? 0,
            'total_reviews' => $guide->total_reviews ?? 0,
            'monthly_earnings' => $this->getMonthlyEarnings($guide->id),
            'top_tours' => $this->getTopTours($guide->id),
        ];

        // Add formatted data for each booking
        $formattedBookings = $bookings->map(function($booking) {
            return $this->formatBookingData($booking);
        });

        return response()->json([
            'data' => $formattedBookings,
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
     * Get a specific completed booking details
     */
    public function show($id)
    {
        $guide = $this->getAuthenticatedGuide();

        if (!$guide) {
            return response()->json([
                'message' => 'Guide profile not found'
            ], 404);
        }

        $booking = Booking::with([
            'client' => function($q) {
                $q->with(['user']);
            },
            'tour' => function($q) {
                $q->with([
                    'city',
                    'tourType',
                    'difficultyLevel',
                    'currency'
                ]);
            },
            'currency',
            'review'
        ])->where('guide_id', $guide->id)
          ->where('id', $id)
          ->where('status', 'completed')
          ->where('payment_status', 'completed')
          ->first();

        if (!$booking) {
            return response()->json([
                'message' => 'Completed booking not found'
            ], 404);
        }

        $formattedBooking = $this->formatBookingData($booking, true);

        return response()->json([
            'data' => $formattedBooking
        ]);
    }

    /**
     * Get booking statistics for charts
     */
    public function statistics(Request $request)
    {
        $guide = $this->getAuthenticatedGuide();

        if (!$guide) {
            return response()->json([
                'message' => 'Guide profile not found'
            ], 404);
        }

        $year = $request->get('year', date('Y'));

        $monthlyEarnings = [];
        for ($month = 1; $month <= 12; $month++) {
            $earnings = Booking::where('guide_id', $guide->id)
                ->where('status', 'completed')
                ->where('payment_status', 'completed')
                ->whereYear('completed_at', $year)
                ->whereMonth('completed_at', $month)
                ->sum('total_price');

            $count = Booking::where('guide_id', $guide->id)
                ->where('status', 'completed')
                ->where('payment_status', 'completed')
                ->whereYear('completed_at', $year)
                ->whereMonth('completed_at', $month)
                ->count();

            $monthlyEarnings[] = [
                'month' => $month,
                'month_name' => date('F', mktime(0, 0, 0, $month, 1)),
                'earnings' => $earnings,
                'bookings_count' => $count
            ];
        }

        // Get top clients
        $topClients = Booking::where('guide_id', $guide->id)
            ->where('status', 'completed')
            ->where('payment_status', 'completed')
            ->with('client.user')
            ->select('client_id', \DB::raw('COUNT(*) as total_bookings'), \DB::raw('SUM(total_price) as total_spent'))
            ->groupBy('client_id')
            ->orderBy('total_spent', 'desc')
            ->limit(5)
            ->get()
            ->map(function($item) {
                return [
                    'client_id' => $item->client_id,
                    'client_name' => $item->client->user->first_name . ' ' . $item->client->user->last_name,
                    'total_bookings' => $item->total_bookings,
                    'total_spent' => $item->total_spent
                ];
            });

        return response()->json([
            'monthly_earnings' => $monthlyEarnings,
            'top_clients' => $topClients,
            'total_earnings' => Booking::where('guide_id', $guide->id)
                ->where('status', 'completed')
                ->where('payment_status', 'completed')
                ->sum('total_price'),
            'total_completed_tours' => Booking::where('guide_id', $guide->id)
                ->where('status', 'completed')
                ->where('payment_status', 'completed')
                ->count(),
            'average_booking_value' => Booking::where('guide_id', $guide->id)
                ->where('status', 'completed')
                ->where('payment_status', 'completed')
                ->avg('total_price')
        ]);
    }

    /**
     * Export completed bookings to CSV
     */
    public function export(Request $request)
    {
        $guide = $this->getAuthenticatedGuide();

        if (!$guide) {
            return response()->json([
                'message' => 'Guide profile not found'
            ], 404);
        }

        $bookings = Booking::with([
            'client.user',
            'tour',
            'currency'
        ])->where('guide_id', $guide->id)
          ->where('status', 'completed')
          ->where('payment_status', 'completed')
          ->orderBy('completed_at', 'desc')
          ->get();

        $csvFileName = "completed_bookings_guide_{$guide->id}_" . date('Y-m-d') . ".csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$csvFileName\"",
        ];

        $callback = function() use ($bookings) {
            $file = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($file, [
                'Booking ID',
                'Client Name',
                'Client Email',
                'Tour Title',
                'Booking Date',
                'Completed Date',
                'Number of Persons',
                'Total Price',
                'Currency',
                'Special Requests'
            ]);

            // Add data rows
            foreach ($bookings as $booking) {
                fputcsv($file, [
                    $booking->id,
                    $booking->client->user->first_name . ' ' . $booking->client->user->last_name,
                    $booking->client->user->email,
                    $booking->tour->title,
                    $booking->booking_date,
                    $booking->completed_at,
                    $booking->number_of_persons,
                    $booking->total_price,
                    $booking->currency->code,
                    $booking->special_requests ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get authenticated guide
     */
    private function getAuthenticatedGuide()
    {
        $user = Auth::user();

        if (!$user) {
            return null;
        }

        return Guide::where('user_id', $user->id)->first();
    }

    /**
     * Get monthly earnings for the guide
     */
    private function getMonthlyEarnings($guideId)
    {
        $monthlyData = [];
        $last6Months = collect(range(0, 5))->map(function($i) {
            return now()->subMonths($i)->startOfMonth();
        })->reverse();

        foreach ($last6Months as $month) {
            $earnings = Booking::where('guide_id', $guideId)
                ->where('status', 'completed')
                ->where('payment_status', 'completed')
                ->whereYear('completed_at', $month->year)
                ->whereMonth('completed_at', $month->month)
                ->sum('total_price');

            $monthlyData[] = [
                'month' => $month->format('M Y'),
                'earnings' => $earnings
            ];
        }

        return $monthlyData;
    }

    /**
     * Get top tours for the guide
     */
    private function getTopTours($guideId)
    {
        return Booking::where('guide_id', $guideId)
            ->where('status', 'completed')
            ->where('payment_status', 'completed')
            ->with('tour')
            ->select('tour_id', \DB::raw('COUNT(*) as total_bookings'), \DB::raw('SUM(total_price) as total_earnings'))
            ->groupBy('tour_id')
            ->orderBy('total_bookings', 'desc')
            ->limit(5)
            ->get()
            ->map(function($item) {
                return [
                    'tour_id' => $item->tour_id,
                    'tour_title' => $item->tour->title,
                    'total_bookings' => $item->total_bookings,
                    'total_earnings' => $item->total_earnings
                ];
            });
    }

    /**
     * Format booking data for response
     */
    private function formatBookingData($booking, $detailed = false)
    {
        $formatted = [
            'id' => $booking->id,
            'booking_date' => $booking->booking_date,
            'completed_at' => $booking->completed_at,
            'number_of_persons' => $booking->number_of_persons,
            'total_price' => $booking->total_price,
            'special_requests' => $booking->special_requests,
            'created_at' => $booking->created_at,
            'client' => [
                'id' => $booking->client->id,
                'first_name' => $booking->client->user->first_name,
                'last_name' => $booking->client->user->last_name,
                'email' => $booking->client->user->email,
                'phone' => $booking->client->user->phone,
                'nationality' => $booking->client->nationality,
                'preferred_language' => $booking->client->preferred_language,
                'profile_image_url' => $booking->client->user->profile_image_url,
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
        ];

        if ($detailed && $booking->review) {
            $formatted['review'] = [
                'id' => $booking->review->id,
                'rating' => $booking->review->rating,
                'comment' => $booking->review->comment,
                'created_at' => $booking->review->created_at,
            ];
        } else {
            $formatted['has_review'] = $booking->review !== null;
        }

        return $formatted;
    }
}
