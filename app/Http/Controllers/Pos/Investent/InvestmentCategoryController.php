<?php

namespace App\Http\Controllers\Pos\Investent;

use App\Exceptions\GeneralException;
use App\Filters\Landlord\Common\NameFilter;
use App\Http\Controllers\Controller;
use App\Models\Pos\Investment\InvestmentCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvestmentCategoryController extends Controller
{
    public function __construct(protected NameFilter $filter) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return InvestmentCategory::query()
            ->filters($this->filter)
            ->withCount('investments')
            ->orderByDesc('id')
            ->with(['director', 'tenant' => function ($query) {
                $query->select('id', 'name')
                    ->with('setting', function ($query) {
                        $query->where('name', 'company_logo');
                    });
            }])
            ->paginate(request('per_page', 10));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'director_id' => ['required', 'exists:users,id'],
            'tenant_id'   => ['required', 'exists:tenants,id'],
        ]);

        $user = User::query()->findOrFail($request->director_id);
        $request->merge(['name' => $user->name]);

        $exists = InvestmentCategory::query()
            ->where('director_id', $request->director_id)
            ->where('tenant_id', $request->tenant_id)
            ->exists();

        if ($exists) {
            return errorResponse('This investment category already exists for this tenant');
        }

        try {
            DB::beginTransaction();
            InvestmentCategory::query()->create($request->all());

            if (!$user->tenants()->where('tenant_id', $request->tenant_id)->exists()) {
                $user->tenants()->attach($request->tenant_id);
            }

            DB::commit();
            return successResponse('Investment category created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('Investment category created failed', 500);
            }
            throw new GeneralException($e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(InvestmentCategory $investmentCategory)
    {
        return $investmentCategory;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, InvestmentCategory $investmentCategory)
    {
        $request->validate([
            'director_id' => ['required', 'exists:users,id'],
            'tenant_id'   => ['required', 'exists:tenants,id'],
        ]);

        $user = User::query()->findOrFail($request->director_id);
        $request->merge(['name' => $user->name]);

        $exists = InvestmentCategory::query()
            ->where('director_id', $request->director_id)
            ->where('tenant_id', $request->tenant_id)
            ->where('id', '!=', $investmentCategory->id) // exclude current record
            ->exists();

        if ($exists) {
            return errorResponse('This investment category already exists for this tenant');
        }

        try {
            DB::beginTransaction();
            $investmentCategory->update($request->only(['director_id', 'tenant_id', 'name']));


            if (!$user->tenants()->where('tenant_id', $request->tenant_id)->exists()) {
                $user->tenants()->attach($request->tenant_id);
            }

            DB::commit();
            return successResponse('Investment category updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('Investment category updated failed', 500);
            }
            throw new GeneralException($e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InvestmentCategory $investmentCategory)
    {
        $investmentCategory->delete();

        return successResponse('Investment category deleted successfully');
    }
}
