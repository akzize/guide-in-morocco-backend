<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTourInclusionRequest;
use App\Http\Requests\UpdateTourInclusionRequest;
use App\Models\Tour;
use App\Models\TourInclusion;
use App\Http\Resources\TourInclusionResource;
use Illuminate\Http\Request;

class TourInclusionController extends Controller
{
    /**
     * Display a listing of inclusions for a specific tour.
     */
    public function index(Request $request, Tour $tour)
    {
        $user = $request->user();

        // Check authorization
        if ($user->user_type !== 'guide' || $tour->guide_id !== $user->guide->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $inclusions = $tour->inclusions()->orderBy('order_sequence')->get();

        return TourInclusionResource::collection($inclusions);
    }

    /**
     * Store a newly created inclusion in storage.
     */
    public function store(StoreTourInclusionRequest $request, Tour $tour)
    {
        $user = $request->user();

        // Check authorization
        if ($user->user_type !== 'guide' || $tour->guide_id !== $user->guide->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validated();

        // If multiple inclusions are being created at once
        if (isset($data['inclusions']) && is_array($data['inclusions'])) {
            $createdInclusions = [];
            foreach ($data['inclusions'] as $index => $inclusionData) {
                $inclusionData['order_sequence'] = $inclusionData['order_sequence'] ?? ($index + 1);
                $inclusionData['tour_id'] = $tour->id;
                $createdInclusions[] = TourInclusion::create($inclusionData);
            }
            return TourInclusionResource::collection($createdInclusions);
        }

        // Single inclusion creation
        $data['tour_id'] = $tour->id;
        $data['order_sequence'] = $data['order_sequence'] ?? $tour->inclusions()->count() + 1;

        $inclusion = TourInclusion::create($data);

        return new TourInclusionResource($inclusion);
    }

    /**
     * Display the specified inclusion.
     */
    public function show(Request $request, Tour $tour, TourInclusion $tourInclusion)
    {
        $user = $request->user();

        // Check if inclusion belongs to the tour
        if ($tourInclusion->tour_id !== $tour->id) {
            return response()->json(['message' => 'Inclusion not found in this tour'], 404);
        }

        // Check authorization
        if ($user->user_type !== 'guide' || $tour->guide_id !== $user->guide->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return new TourInclusionResource($tourInclusion);
    }

    /**
     * Update the specified inclusion in storage.
     */
    public function update(UpdateTourInclusionRequest $request, Tour $tour, TourInclusion $tourInclusion)
    {
        $user = $request->user();

        // Check if inclusion belongs to the tour
        if ($tourInclusion->tour_id !== $tour->id) {
            return response()->json(['message' => 'Inclusion not found in this tour'], 404);
        }

        // Check authorization
        if ($user->user_type !== 'guide' || $tour->guide_id !== $user->guide->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $tourInclusion->update($request->validated());

        return new TourInclusionResource($tourInclusion);
    }

    /**
     * Remove the specified inclusion from storage.
     */
    public function destroy(Request $request, Tour $tour, TourInclusion $tourInclusion)
    {
        $user = $request->user();

        // Check if inclusion belongs to the tour
        if ($tourInclusion->tour_id !== $tour->id) {
            return response()->json(['message' => 'Inclusion not found in this tour'], 404);
        }

        // Check authorization
        if ($user->user_type !== 'guide' || $tour->guide_id !== $user->guide->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $deletedOrderSequence = $tourInclusion->order_sequence;
        $tourInclusion->delete();

        // Reorder remaining inclusions
        TourInclusion::where('tour_id', $tour->id)
            ->where('order_sequence', '>', $deletedOrderSequence)
            ->decrement('order_sequence');

        return response()->json(null, 204);
    }

    /**
     * Reorder inclusions for a tour.
     */
    public function reorder(Request $request, Tour $tour)
    {
        $user = $request->user();

        // Check authorization
        if ($user->user_type !== 'guide' || $tour->guide_id !== $user->guide->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'inclusions' => 'required|array',
            'inclusions.*.id' => 'required|exists:tour_inclusions,id',
            'inclusions.*.order_sequence' => 'required|integer|min:1',
        ]);

        foreach ($request->inclusions as $inclusionData) {
            TourInclusion::where('id', $inclusionData['id'])
                ->where('tour_id', $tour->id)
                ->update(['order_sequence' => $inclusionData['order_sequence']]);
        }

        return response()->json(['message' => 'Inclusions reordered successfully']);
    }
}
