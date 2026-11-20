<?php

namespace App\Http\Controllers\Ecommerce\Slider;

use App\Filters\Landlord\Common\NameFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Landlord\Slider\SliderRequest;
use App\Models\Ecommerce\Slider\Slider;
use Illuminate\Http\JsonResponse;

class SliderController extends Controller
{
    public function __construct(protected NameFilter $filter) {}

    public function index()
    {
        return Slider::query()
            ->filters($this->filter)
            ->with('tenant:id,name')
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SliderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['image'] = $request->input('image');
        Slider::query()->create($data);

        return successResponse('Slider created successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(Slider $slider): Slider
    {
        return $slider;
    }

    /**
     * Update a newly created resource in storage.
     */
    public function update(SliderRequest $request, Slider $slider): JsonResponse
    {
        $slider->update($request->validated());

        return successResponse('Slider updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Slider $slider): JsonResponse
    {
        $slider->delete();

        return successResponse('Slider deleted successfully');
    }

    /**
     * Toggle the active/inactive state of the specified slider.
     */
    public function statusUpdate(Slider $slider): JsonResponse
    {
        $slider->update([
            'is_active' => ! $slider->is_active,
        ]);

        return successResponse('Slider status updated successfully');
    }
}
