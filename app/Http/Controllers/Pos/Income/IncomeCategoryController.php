<?php

namespace App\Http\Controllers\Pos\Income;

use App\Exceptions\GeneralException;
use App\Filters\Landlord\Common\NameFilter;
use App\Http\Controllers\Controller;
use App\Models\Pos\Income\IncomeCategory;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IncomeCategoryController extends Controller
{
    public function __construct(protected NameFilter $filter) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return IncomeCategory::query()
            ->filters($this->filter)
            ->withCount('incomes as total_income_count')
            ->with(['director', 'tenant' => function ($query) {
                $query->select('id', 'name')
                    ->with('setting', function ($query) {
                        $query->where('name', 'company_logo');
                    });
            }])
            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'director_id' => ['required', 'exists:users,id'],
            'tenant_id' => ['required', 'exists:tenants,id'],
        ]);

        $user = User::query()->findOrFail($request->director_id);
        $request->merge(['name' => $user->name]);

        $exists = IncomeCategory::query()
            ->where('director_id', $request->director_id)
            ->where('tenant_id', $request->tenant_id)
            ->exists();

        if ($exists) {
            return errorResponse('This Income Category already exists for this tenant');
        }

        try {
            DB::beginTransaction();
            IncomeCategory::query()->create($request->all());

            if (! $user->tenants()->where('tenant_id', $request->tenant_id)->exists()) {
                $user->tenants()->attach($request->tenant_id);
            }
            DB::commit();

            return successResponse('Income Category created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('Income Category created failed', 500, $e);
            }
            throw new GeneralException($e->getMessage(), 500, $e);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(IncomeCategory $income_category): IncomeCategory
    {
        return $income_category;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, IncomeCategory $income_category): JsonResponse
    {
        $request->validate([
            'director_id' => ['required', 'exists:users,id'],
            'tenant_id' => ['required', 'exists:tenants,id'],
        ]);

        $user = User::query()->findOrFail($request->director_id);
        $request->merge(['name' => $user->name]);

        $exists = IncomeCategory::query()
            ->where('director_id', $request->director_id)
            ->where('tenant_id', $request->tenant_id)
            ->where('id', '!=', $income_category->id) // exclude current record
            ->exists();

        if ($exists) {
            return errorResponse('This Income Category already exists for this tenant');
        }

        try {
            DB::beginTransaction();
            $income_category->update($request->only(['director_id', 'tenant_id', 'name']));

            if (! $user->tenants()->where('tenant_id', $request->tenant_id)->exists()) {
                $user->tenants()->attach($request->tenant_id);
            }
            DB::commit();

            return successResponse('Income Category updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('Income Category updated failed', 500, $e);
            }
            throw new GeneralException($e->getMessage(), 500, $e);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(IncomeCategory $income_category): JsonResponse
    {
        $income_category->delete();

        return successResponse('Income Category deleted successfully');
    }
}
