<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTourRequest;
use App\Http\Requests\UpdateTourRequest;
use App\Models\Tour;
use App\Http\Resources\TourResource;
use Illuminate\Http\Request;

class TourController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Tour::query()->with(['guide.user', 'city', 'tourType', 'difficultyLevel', 'currency'])
            ->where('status', 'published');

        if ($request->has('city_id')) {
            $query->where('city_id', $request->city_id);
        }
        if ($request->has('tour_type_id')) {
            $query->where('tour_type_id', $request->tour_type_id);
        }
        if ($request->has('guide_id')) {
            $query->where('guide_id', $request->guide_id);
        }

        return TourResource::collection($query->paginate(15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTourRequest $request)
    {
        $user = $request->user();
        if ($user->user_type !== 'guide') {
            return response()->json(['message' => 'Only guides can create tours'], 403);
        }

        $tour = Tour::create(array_merge($request->validated(), [
            'guide_id' => $user->guide->id
        ]));

        return new TourResource($tour);
    }

    /**
     * Display the specified resource.
     */
    public function show(Tour $tour)
    {
        $tour->load(['guide.user', 'city', 'tourType', 'difficultyLevel', 'currency', 'stops', 'inclusions']);
        return new TourResource($tour);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTourRequest $request, Tour $tour)
    {
        $user = $request->user();
        if ($user->user_type !== 'guide' || $tour->guide_id !== $user->guide->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $tour->update($request->validated());

        return new TourResource($tour);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tour $tour)
    {
        $user = request()->user();
        if ($user->user_type !== 'guide' || $tour->guide_id !== $user->guide->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $tour->delete();
        return response()->json(null, 204);
    }
}
