<?php

namespace App\Http\Controllers\Ecommerce\Review;

use App\Filters\Ecommerce\Review\ReviewFilter;
use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Review\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Review::query()
            ->filters(new ReviewFilter)
            ->with(['product:id,product_name,product_image', 'contact:id,name'])
            ->orderByDesc('id')
            ->paginate(\request('per_page', 10));
    }

    /**
     * Store a newly created resource in storage.
     *
     * Admin-authored reviews only — not tied to a real customer or order,
     * and never shown on the customer-facing storefront (see
     * Product::reviews() in the web-api repo, which excludes
     * is_admin_review = true from every public review list/count/rating).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string'],
        ]);

        Review::query()->create([
            ...$validated,
            'contact_id' => null,
            'order_detail_id' => null,
            'is_admin_review' => true,
            'status' => 1,
        ]);

        return successResponse('Review added successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Review $review)
    {
        return $review->load(['product:id,product_name', 'contact:id,name']);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Review $review)
    {
        $request->validate([
            'status' => ['required', 'in:1,0'],
        ]);

        $review->update([
            'status' => $request->status,
        ]);

        return successResponse('Review status updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Review $review)
    {
        $review->delete();

        return successResponse('Review deleted successfully');
    }
}
