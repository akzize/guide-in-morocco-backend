<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGuideRequest;
use App\Http\Requests\UpdateGuideRequest;
use App\Models\Guide;
use App\Http\Resources\GuideResource;
use Illuminate\Http\Request;

class GuideController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Guide::query()->with(['user', 'languages', 'specialties'])
            ->where('certificate_status', 'approved');

        if ($request->has('location')) {
            $query->where('location', 'like', '%' . $request->location . '%');
        }

        return GuideResource::collection($query->paginate(15));
    }

    /**
     * Display the specified resource.
     */
    public function show(Guide $guide)
    {
        $guide->load(['user', 'languages', 'specialties', 'availabilities', 'tours' => function($q) {
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

        return new GuideResource($guide->load(['user']));
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
