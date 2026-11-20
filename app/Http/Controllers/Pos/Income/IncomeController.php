<?php

namespace App\Http\Controllers\Pos\Income;


use App\Exceptions\GeneralException;
use App\Filters\Pos\Report\IncomeFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Landlord\IncomeRequest;
use App\Models\Pos\Income\Income;
use App\Models\Pos\Income\IncomeCategory;
use App\Models\Pos\Investment\Investment;
use Illuminate\Support\Facades\DB;
use Throwable;

class IncomeController extends Controller
{
    public function index()
    {
        $perPage = request('per_page', 10);

        return Income::query()
            ->filters(new IncomeFilter())
            ->with(['user:id,name,profile_photo', 'tenant:id,name', 'contact:id,name', 'createdBy:id,name'])
            ->orderByDesc('id')
            ->paginate($perPage)
            ->through(function ($income) {
                return [
                    ...$income->toArray(),
                    'invoice_number' => "WTH-{$income->id}",
                    'images' => $income->attachments()->pluck('link')->toArray(),
                    'due_amount' => round($income->amount - $income->getTransactionAmountAttribute(), 2),
                ];
            })->toArray();
    }

    /**
     * @throws Throwable
     * @throws GeneralException
     */
    public function store(IncomeRequest $request): \Illuminate\Http\JsonResponse
    {
        $category = IncomeCategory::query()
            ->where('id', $request->income_category_id)
            ->where('tenant_id', $request->tenant_id)
            ->first();

        if (!$category) {
            throw new GeneralException('No director found in this income category to this shop', 422);
        }

        try {
            DB::beginTransaction();

            $request['user_id'] = $category->director_id;
            $request['created_by'] = auth()->id();

            $income = Income::query()->create($request->all());

            if ($request->has('attachments')) {
                foreach ($request->attachments as $attachmentUrl) {
                    $income->attachments()->create([
                        'link' => $attachmentUrl,
                    ]);
                }
            }

            DB::commit();
            return successResponse('Income has been created successfully');
        } catch (\Exception $exception) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('You can not create this Income', 500);
            }
            throw new GeneralException($exception->getMessage(), 500);
        }
    }

    public function show(Income $income)
    {
        $income->load('attachments');
        return $income;
    }

    /**
     * @throws Throwable
     * @throws GeneralException
     */
    public function update(IncomeRequest $request, Income $income): \Illuminate\Http\JsonResponse
    {
        $category = IncomeCategory::query()
            ->where('id', $request->income_category_id)
            ->where('tenant_id', $request->tenant_id)
            ->first();

        if (!$category) {
            throw new GeneralException('No director found in this income category to this shop', 422);
        }

        try {
            DB::beginTransaction();
            $income->update(array_merge($request->validated(), ['user_id' => $category->director_id]));

            if ($request->has('deletable_attachment_ids')) {
                $income->attachments()->whereIn('id', $request->deletable_attachment_ids)->delete();
            }

            if ($request->has('attachments') && !$request->hasFile('attachments')) {
                foreach ($request->attachments as $attachmentUrl) {
                    $income->attachments()->create([
                        'link' => $attachmentUrl,
                    ]);
                }
            }
            DB::commit();
            return successResponse('Income has been updated successfully');
        } catch (\Exception $exception) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('You can not update this Income', 500);
            }
            throw new GeneralException($exception->getMessage(), 500);
        }
    }

    /**
     * @throws Throwable
     * @throws GeneralException
     */
    public function destroy(Income $income): \Illuminate\Http\JsonResponse
    {
        try {
            DB::beginTransaction();
            $income->attachments()->delete();

            $income->delete();
            DB::commit();
            return successResponse('Income deleted successfully');
        } catch (\Exception $exception) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('You can not delete this Income', 500);
            }
            throw new GeneralException($exception->getMessage(), 500);
        }
    }
}
