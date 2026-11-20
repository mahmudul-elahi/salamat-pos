<?php

namespace App\Http\Controllers\Ecommerce\Reels;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\Reels\ReelsVideo;
use Illuminate\Http\Request;

class ReelsVideoController extends Controller
{
    public function index()
    {
        return ReelsVideo::query()
            ->with('product:id,product_name')
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'video_link' => ['required', 'string'],
            'status' => ['required', 'boolean'],
        ]);

        ReelsVideo::query()->create($validated);

        return successResponse('Reels videos created successfully');
    }

    public function show(ReelsVideo $reelsVideo)
    {
        return $reelsVideo;
    }

    public function update(Request $request, ReelsVideo $reelsVideo)
    {
        $validated = $request->validate([
            'product_id' => 'sometimes|exists:products,id',
            'video_link' => 'sometimes|string',
            'status' => 'sometimes|boolean',
        ]);

        $reelsVideo->update($validated);
        return successResponse('Reels video updated successfully');
    }

    public function destroy(ReelsVideo $reelsVideo)
    {
        $reelsVideo->delete();
        return successResponse('Reels video deleted successfully');
    }
}
