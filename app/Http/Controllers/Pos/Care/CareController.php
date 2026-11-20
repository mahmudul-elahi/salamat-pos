<?php

namespace App\Http\Controllers\Pos\Care;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\Care\CareRequest;
use App\Models\Pos\Care\Care;
use Throwable;

class CareController extends Controller
{
    public function index()
    {
        return Care::query()
            ->latest()
            ->paginate(request('per_page', 10));
    }

    /**
     * @throws GeneralException|Throwable
     */
    public function store(CareRequest $request)
    {
        try {
            Care::query()->create($request->validated());

            return successResponse('Care created successfully');
        } catch (\Exception $e) {
            if (app()->environment('production')) {
                throw new GeneralException('Care creation failed', 500, $e);
            }
            throw new GeneralException($e->getMessage(), 500, $e);
        }
    }

    public function show(Care $care)
    {
        return $care;
    }

    /**
     * @throws GeneralException|Throwable
     */
    public function update(CareRequest $request, Care $care)
    {
        try {
            $care->update($request->validated());

            return successResponse('Care updated successfully');
        } catch (\Exception $e) {
            if (app()->environment('production')) {
                throw new GeneralException('Care update failed', 500, $e);
            }
            throw new GeneralException($e->getMessage(), 500, $e);
        }
    }

    /**
     * @throws GeneralException|Throwable
     */
    public function destroy(Care $care)
    {
        try {
            $care->delete();

            return successResponse('Care deleted successfully');
        } catch (\Exception $e) {
            if (app()->environment('production')) {
                throw new GeneralException('Care deletion failed', 500, $e);
            }
            throw new GeneralException($e->getMessage(), 500, $e);
        }
    }
}
