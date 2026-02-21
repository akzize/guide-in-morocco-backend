<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Models\Review;
use App\Models\Tour;
use App\Models\Guide;
use App\Http\Resources\ReviewResource;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Get reviews for a specific tour.
     */
    public function tourReviews(Tour $tour)
    {
        $reviews = Review::with(['client.user'])->where('tour_id', $tour->id)->where('status', 'approved')->paginate(15);
        return ReviewResource::collection($reviews);
    }

    /**
     * Get reviews for a specific guide.
     */
    public function guideReviews(Guide $guide)
    {
        $reviews = Review::with(['client.user', 'tour'])->where('guide_id', $guide->id)->where('status', 'approved')->paginate(15);
        return ReviewResource::collection($reviews);
    }

    /**
     * Store a newly created review.
     */
    public function store(StoreReviewRequest $request)
    {
        $user = $request->user();
        if ($user->user_type !== 'client') {
            return response()->json(['message' => 'Only clients can leave reviews'], 403);
        }

        $review = Review::create(array_merge($request->validated(), [
            'client_id' => $user->client->id,
            'status' => 'pending'
        ]));

        return new ReviewResource($review->load(['client.user', 'guide.user', 'tour']));
    }

    /**
     * Update the specified review.
     */
    public function update(UpdateReviewRequest $request, Review $review)
    {
        $user = $request->user();
        if ($user->user_type !== 'client' || $review->client_id !== $user->client->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $review->update($request->validated());

        return new ReviewResource($review->load(['client.user', 'guide.user', 'tour']));
    }

    /**
     * Remove the specified review.
     */
    public function destroy(Review $review)
    {
        $user = request()->user();
        if ($user->user_type !== 'client' || $review->client_id !== $user->client->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $review->delete();
        return response()->json(null, 204);
    }
}
