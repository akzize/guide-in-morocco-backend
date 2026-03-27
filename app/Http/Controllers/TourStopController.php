<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTourStopRequest;
use App\Http\Requests\UpdateTourStopRequest;
use App\Models\Tour;
use App\Models\TourStop;
use App\Http\Resources\TourStopResource;
use Illuminate\Http\Request;

class TourStopController extends Controller
{
    /**
     * Display a listing of stops for a specific tour.
     */
    public function index(Request $request, Tour $tour)
    {
        $user = $request->user();

        // Check authorization
        if ($user->user_type !== 'guide' || $tour->guide_id !== $user->guide->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $stops = $tour->stops()->orderBy('order_sequence')->get();

        return TourStopResource::collection($stops);
    }

    /**
     * Store a newly created stop in storage.
     */
    public function store(StoreTourStopRequest $request, Tour $tour)
    {
        $user = $request->user();

        // Check authorization
        if ($user->user_type !== 'guide' || $tour->guide_id !== $user->guide->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validated();

        // If multiple stops are being created at once
        if (isset($data['stops']) && is_array($data['stops'])) {
            $createdStops = [];
            foreach ($data['stops'] as $index => $stopData) {
                $stopData['order_sequence'] = $stopData['order_sequence'] ?? ($index + 1);
                $stopData['tour_id'] = $tour->id;
                $createdStops[] = TourStop::create($stopData);
            }
            return TourStopResource::collection($createdStops);
        }

        // Single stop creation
        $data['tour_id'] = $tour->id;
        $data['order_sequence'] = $data['order_sequence'] ?? $tour->stops()->count() + 1;

        // Handle location coordinates if provided
        if (isset($data['location_coordinates']) && is_array($data['location_coordinates'])) {
            $data['location_coordinates'] = json_encode($data['location_coordinates']);
        }

        $stop = TourStop::create($data);

        return new TourStopResource($stop);
    }

    /**
     * Display the specified stop.
     */
    public function show(Request $request, Tour $tour, TourStop $tourStop)
    {
        $user = $request->user();

        // Check if stop belongs to the tour
        if ($tourStop->tour_id !== $tour->id) {
            return response()->json(['message' => 'Stop not found in this tour'], 404);
        }

        // Check authorization
        if ($user->user_type !== 'guide' || $tour->guide_id !== $user->guide->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return new TourStopResource($tourStop);
    }

    /**
     * Update the specified stop in storage.
     */
    public function update(UpdateTourStopRequest $request, Tour $tour, TourStop $tourStop)
    {
        $user = $request->user();

        // Check if stop belongs to the tour
        if ($tourStop->tour_id !== $tour->id) {
            return response()->json(['message' => 'Stop not found in this tour'], 404);
        }

        // Check authorization
        if ($user->user_type !== 'guide' || $tour->guide_id !== $user->guide->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validated();

        // Handle location coordinates if provided
        if (isset($data['location_coordinates']) && is_array($data['location_coordinates'])) {
            $data['location_coordinates'] = json_encode($data['location_coordinates']);
        }

        $tourStop->update($data);

        return new TourStopResource($tourStop);
    }

    /**
     * Remove the specified stop from storage.
     */
    public function destroy(Request $request, Tour $tour, TourStop $tourStop)
    {
        $user = $request->user();

        // Check if stop belongs to the tour
        if ($tourStop->tour_id !== $tour->id) {
            return response()->json(['message' => 'Stop not found in this tour'], 404);
        }

        // Check authorization
        if ($user->user_type !== 'guide' || $tour->guide_id !== $user->guide->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $deletedOrderSequence = $tourStop->order_sequence;
        $tourStop->delete();

        // Reorder remaining stops
        TourStop::where('tour_id', $tour->id)
            ->where('order_sequence', '>', $deletedOrderSequence)
            ->decrement('order_sequence');

        return response()->json(null, 204);
    }

    /**
     * Reorder stops for a tour.
     */
    public function reorder(Request $request, Tour $tour)
    {
        $user = $request->user();

        // Check authorization
        if ($user->user_type !== 'guide' || $tour->guide_id !== $user->guide->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'stops' => 'required|array',
            'stops.*.id' => 'required|exists:tour_stops,id',
            'stops.*.order_sequence' => 'required|integer|min:1',
        ]);

        foreach ($request->stops as $stopData) {
            TourStop::where('id', $stopData['id'])
                ->where('tour_id', $tour->id)
                ->update(['order_sequence' => $stopData['order_sequence']]);
        }

        return response()->json(['message' => 'Stops reordered successfully']);
    }
}
