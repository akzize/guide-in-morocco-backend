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
     * Display a listing of the resource (public).
     */
    public function index(Request $request)
    {
        $query = Tour::query()->with(['guide.user', 'city', 'tourType', 'difficultyLevel', 'currency','primaryImage'])
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
     * Display a listing of tours for the authenticated guide
     */
    public function guideTours(Request $request)
    {
        $user = $request->user();
        if ($user->user_type !== 'guide') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = Tour::query()->with(['guide.user', 'city', 'tourType', 'difficultyLevel', 'currency','primaryImage'])
            ->where('guide_id', $user->guide->id);

        // Filter by status
        if ($request->has('status') && in_array($request->status, ['published', 'draft', 'archived'])) {
            $query->where('status', $request->status);
        }

        // Filter by city
        if ($request->has('city_id')) {
            $query->where('city_id', $request->city_id);
        }

        // Filter by tour type
        if ($request->has('tour_type_id')) {
            $query->where('tour_type_id', $request->tour_type_id);
        }

        // Search by title
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // Order by
        $orderBy = $request->get('order_by', 'created_at');
        $orderDir = $request->get('order_dir', 'desc');
        $query->orderBy($orderBy, $orderDir);

        return TourResource::collection($query->paginate(15));
    }


     /**
     * Display the specified resource.
     */
    public function show(Tour $tour)
    {
        $tour->load(['guide.user', 'city', 'tourType', 'difficultyLevel', 'currency', 'stops', 'inclusions','images']);
        return new TourResource($tour);
    }


    /**
     * Store a newly created resource in storage.
     */
   public function store(StoreTourRequest $request)
{
    $user = $request->user();

    // Check if user is a guide
    if ($user->user_type !== 'guide') {
        return response()->json(['message' => 'Only guides can create tours'], 403);
    }

    // Check if user has a guide profile
    if (!$user->guide) {
        return response()->json(['message' => 'Guide profile not found'], 403);
    }

    // Format duration
    $durationInHours = $request->duration_in_hours;
    $hours = floor($durationInHours);
    $minutes = ($durationInHours - $hours) * 60;
    $durationFormatted = $hours . 'h' . ($minutes > 0 ? ' ' . $minutes . 'min' : '');

    // Create tour
    $tour = Tour::create(array_merge($request->validated(), [
        'guide_id' => $user->guide->id,
        'duration_formatted' => $durationFormatted
    ]));

    // Handle images
    if ($request->has('images') && is_array($request->images)) {
        foreach ($request->images as $index => $image) {
            // Check if image_url exists and is base64
            if (isset($image['image_url']) && !empty($image['image_url'])) {
                $base64Image = $image['image_url'];

                // Check if it's a base64 image
                if (strpos($base64Image, 'data:image') === 0) {
                    // Decode base64
                    $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));
                    $fileName = 'tours/' . uniqid() . '.jpg';

                    // Save file
                    \Storage::disk('public')->put($fileName, $imageData);

                    $tour->images()->create([
                        'image_url' => $fileName,
                        'is_primary' => $image['is_primary'] ?? ($index === 0),
                        'order_sequence' => $index,
                    ]);
                }
            }
        }
    }

    // Handle stops
    if ($request->has('stops') && is_array($request->stops)) {
        foreach ($request->stops as $index => $stop) {
            // Only create stop if it has a name
            if (!empty($stop['name'])) {
                $tour->stops()->create([
                    'stop_name' => $stop['name'],
                    'description' => $stop['description'] ?? null,
                    'order_sequence' => $stop['order_sequence'] ?? $index,
                    'duration_in_minutes' => $stop['estimated_duration_minutes'] ?? null,
                ]);
            }
        }
    }

    // Handle inclusions
    if ($request->has('inclusions') && is_array($request->inclusions)) {
        foreach ($request->inclusions as $index => $inclusion) {
            // Only create inclusion if it has a name
            if (!empty($inclusion['name'])) {
                $tour->inclusions()->create([
                    'inclusion_text' => $inclusion['name'],
                    'order_sequence' => $inclusion['order_sequence'] ?? $index,
                ]);
            }
        }
    }

    // Load all relationships
    $tour->load([
        'guide.user',
        'city',
        'tourType',
        'difficultyLevel',
        'currency',
        'primaryImage',
        'images',
        'stops',
        'inclusions'
    ]);

    return new TourResource($tour);
}



    /**
     * Update the specified resource in storage.
     */
   public function update(UpdateTourRequest $request, Tour $tour)
{
    $user = $request->user();

    // Load the guide relationship if not already loaded
    if (!$user->relationLoaded('guide')) {
        $user->load('guide');
    }

    // Check if user is guide and owns the tour
    if ($user->user_type !== 'guide' || $tour->guide_id !== $user->guide->id) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $data = $request->validated();

    // Update duration_formatted if duration_in_hours is provided
    if (isset($data['duration_in_hours'])) {
        $hours = floor($data['duration_in_hours']);
        $minutes = ($data['duration_in_hours'] - $hours) * 60;
        $data['duration_formatted'] = $hours . 'h' . ($minutes > 0 ? ' ' . $minutes . 'min' : '');
    }

    // Update main tour data
    $tour->update($data);

    // Handle stops - sync with provided data
    if ($request->has('stops') && is_array($request->stops)) {
        // Get existing stop IDs
        $existingStopIds = $tour->stops->pluck('id')->toArray();
        $updatedStopIds = [];

        foreach ($request->stops as $index => $stopData) {
            if (isset($stopData['id']) && in_array($stopData['id'], $existingStopIds)) {
                // Update existing stop
                $stop = $tour->stops()->find($stopData['id']);
                if ($stop) {
                    $stop->update([
                        'stop_name' => $stopData['name'],
                        'description' => $stopData['description'] ?? null,
                        'order_sequence' => $index,
                        'duration_in_minutes' => $stopData['estimated_duration_minutes'] ?? null,
                    ]);
                    $updatedStopIds[] = $stopData['id'];
                }
            } else {
                // Create new stop (only if it has a name)
                if (!empty($stopData['name'])) {
                    $newStop = $tour->stops()->create([
                        'stop_name' => $stopData['name'],
                        'description' => $stopData['description'] ?? null,
                        'order_sequence' => $index,
                        'duration_in_minutes' => $stopData['estimated_duration_minutes'] ?? null,
                    ]);
                    $updatedStopIds[] = $newStop->id;
                }
            }
        }

        // Delete stops that weren't included in the update
        $stopsToDelete = array_diff($existingStopIds, $updatedStopIds);
        if (!empty($stopsToDelete)) {
            $tour->stops()->whereIn('id', $stopsToDelete)->delete();
        }
    }

    // Handle inclusions - sync with provided data
    if ($request->has('inclusions') && is_array($request->inclusions)) {
        $existingInclusionIds = $tour->inclusions->pluck('id')->toArray();
        $updatedInclusionIds = [];

        foreach ($request->inclusions as $index => $inclusionData) {
            if (isset($inclusionData['id']) && in_array($inclusionData['id'], $existingInclusionIds)) {
                // Update existing inclusion
                $inclusion = $tour->inclusions()->find($inclusionData['id']);
                if ($inclusion) {
                    $inclusion->update([
                        'inclusion_text' => $inclusionData['name'],
                        'order_sequence' => $index,
                    ]);
                    $updatedInclusionIds[] = $inclusionData['id'];
                }
            } else {
                // Create new inclusion (only if it has a name)
                if (!empty($inclusionData['name'])) {
                    $newInclusion = $tour->inclusions()->create([
                        'inclusion_text' => $inclusionData['name'],
                        'order_sequence' => $index,
                    ]);
                    $updatedInclusionIds[] = $newInclusion->id;
                }
            }
        }

        // Delete inclusions that weren't included in the update
        $inclusionsToDelete = array_diff($existingInclusionIds, $updatedInclusionIds);
        if (!empty($inclusionsToDelete)) {
            $tour->inclusions()->whereIn('id', $inclusionsToDelete)->delete();
        }
    }

    // Handle images if provided
    if ($request->has('images') && is_array($request->images)) {
        $existingImageIds = $tour->images->pluck('id')->toArray();
        $updatedImageIds = [];

        foreach ($request->images as $index => $imageData) {
            if (isset($imageData['image_url']) && !empty($imageData['image_url'])) {
                // Check if it's a base64 image (new upload)
                if (strpos($imageData['image_url'], 'data:image') === 0) {
                    // Decode base64 and save
                    $base64Image = $imageData['image_url'];
                    $imageDataDecoded = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));
                    $fileName = 'tours/' . uniqid() . '.jpg';
                    \Storage::disk('public')->put($fileName, $imageDataDecoded);

                    // Create new image
                    $newImage = $tour->images()->create([
                        'image_url' => $fileName,
                        'is_primary' => $imageData['is_primary'] ?? ($index === 0),
                        'order_sequence' => $index,
                    ]);
                    $updatedImageIds[] = $newImage->id;
                } else {
                    // Existing image URL (keep as is)
                    if (isset($imageData['id']) && in_array($imageData['id'], $existingImageIds)) {
                        $image = $tour->images()->find($imageData['id']);
                        if ($image) {
                            $image->update([
                                'is_primary' => $imageData['is_primary'] ?? false,
                                'order_sequence' => $index,
                            ]);
                            $updatedImageIds[] = $imageData['id'];
                        }
                    }
                }
            }
        }

        // Delete images that weren't included in the update
        $imagesToDelete = array_diff($existingImageIds, $updatedImageIds);
        if (!empty($imagesToDelete)) {
            foreach ($imagesToDelete as $imageId) {
                $image = $tour->images()->find($imageId);
                if ($image) {
                    // Delete file from storage
                    \Storage::disk('public')->delete($image->image_url);
                    $image->delete();
                }
            }
        }
    }

    // Refresh the tour with all relationships
    $tour->load([
        'guide.user',
        'city',
        'tourType',
        'difficultyLevel',
        'currency',
        'primaryImage',
        'images',
        'stops',
        'inclusions'
    ]);

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
