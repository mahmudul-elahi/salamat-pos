<?php

namespace App\Http\Controllers\Pos\Brand;

use App\Filters\Landlord\Common\NameFilter;
use App\Helpers\Traits\RevalidatesFrontend;
use App\Http\Controllers\Controller;
use App\Http\Requests\Landlord\Brand\BrandRequest;
use App\Models\Landlord\Brand\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class BrandController extends Controller
{

    public function __construct(protected NameFilter $filter) {}

    public function index()
    {
        return Brand::query()
            ->filters($this->filter)
            ->withCount('products')
            ->orderBy('order')
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    /**
     * Store a newly created resource in storage.
     * @throws Throwable
     */

    public function store(BrandRequest $request): \Illuminate\Http\JsonResponse
    {

        Brand::query()->create(array_merge($request->validated(), [
            'slug' => Str::slug($request['name']),
            'order' => (int) Brand::query()->max('order') + 1,
        ]));


        return successResponse('Brand created successfully');
    }

    public function updateOrder(Request $request): \Illuminate\Http\JsonResponse
    {
        $brands = $request->input('brands', []);

        DB::transaction(function () use ($brands) {
            foreach ($brands as $brand) {
                Brand::query()->where('id', $brand['id'])->update(['order' => $brand['order']]);
            }
        });

        return successResponse('Brand order updated successfully');
    }


    /**
     * Display the specified resource.
     */

    public function show(Brand $brand)
    {
        return $brand;
    }


    public function update(BrandRequest $request, Brand $brand): \Illuminate\Http\JsonResponse
    {

        $brand->update(array_merge($request->validated(), [
            'slug' => Str::slug($request['name'])
        ]));
        Log::info('Brand created successfully');


        return successResponse('Brand updated successfully');
    }


    /**
     * Remove the specified resource from storage.
     */

    public function destroy(Brand $brand): \Illuminate\Http\JsonResponse
    {
        $brand->delete();

        return successResponse('Brand deleted successfully');
    }

    public function toggleStatus(Brand $brand): \Illuminate\Http\JsonResponse
    {
        $brand->update([
            'status' => $brand->status == '1' ? '0' : '1',
        ]);


        return successResponse('Brand visibility updated successfully');
    }
}
