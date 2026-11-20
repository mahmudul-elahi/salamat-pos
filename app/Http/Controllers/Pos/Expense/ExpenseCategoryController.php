<?php

namespace App\Http\Controllers\Pos\Expense;

use App\Filters\Landlord\Common\NameFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Landlord\Expense\ExpenseCategoryRequest;
use App\Models\Pos\Expense\ExpenseCategory;

class ExpenseCategoryController extends Controller
{
    public function __construct(protected NameFilter $filter) {}

    /**
     * Display a listing of the resource.
     */

    public function index()
    {
        return ExpenseCategory::query()
            ->filters($this->filter)
            ->withCount('expenses as total_expense_count')

            ->orderByDesc('id')
            ->paginate(request('per_page', 10));
    }

    /**
     * Store a newly created resource in storage.
     */

    public function store(ExpenseCategoryRequest $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = null;
        ExpenseCategory::create($data);
        return successResponse('Expense Category created successfully');
    }

    /**
     * Display the specified resource.
     */

    public function show(ExpenseCategory $expense_category): ExpenseCategory
    {
        return $expense_category;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ExpenseCategoryRequest $request, ExpenseCategory $expense_category): \Illuminate\Http\JsonResponse
    {
        $expense_category->update($request->validated());

        return successResponse('Expense Category updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */

    public function destroy(ExpenseCategory $expense_category): \Illuminate\Http\JsonResponse
    {
        $expense_category->delete();
        return successResponse('Expense Category deleted successfully');
    }
}
