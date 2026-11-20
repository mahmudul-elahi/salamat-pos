<?php

namespace App\Http\Controllers\Pos\Investent;

use App\Exceptions\GeneralException;
use App\Filters\Pos\Report\DepositReportFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\Investment\InvestmentRequest;
use App\Models\Pos\Investment\Investment;
use App\Models\Pos\Investment\InvestmentCategory;
use Illuminate\Support\Facades\DB;
use Throwable;

class InvestmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $perPage = request('per_page', 10);

        return Investment::query()
            ->filters(new DepositReportFilter)
            ->with(['user:id,name,profile_photo', 'tenant:id,name', 'contact:id,name', 'createdBy:id,name'])
            ->orderByDesc('id')
            ->paginate($perPage)
            ->through(fn ($investment) => [
                ...$investment->toArray(),
                'invoice_number' => "INV-{$investment->id}",
                'images' => $investment->getImagesAttribute(),
            ])->toArray();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws GeneralException
     * @throws Throwable
     */
    public function store(InvestmentRequest $request)
    {
        $investmentCategory = InvestmentCategory::query()
            ->where('id', $request->investment_category_id)
            ->where('tenant_id', $request->tenant_id)
            ->first();

        if (! $investmentCategory) {
            throw new GeneralException('No director found in this investment category to this shop', 422);
        }

        try {
            DB::beginTransaction();

            $request['user_id'] = $investmentCategory->director_id;
            $request['created_by'] = auth()->id();

            $investment = Investment::query()->create($request->all());

            if ($request->has('attachments')) {
                foreach ($request->attachments as $attachmentUrl) {
                    $investment->attachments()->create([
                        'link' => $attachmentUrl,
                    ]);
                }
            }
            DB::commit();

            return successResponse('Investment created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('Failed to create investment', 500, $e);
            }
            throw new GeneralException($e->getMessage(), 500, $e);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Investment $investment)
    {
        $investment->load('attachments');

        return $investment;
    }

    /**
     * Update the specified resource in storage.
     *
     * @throws GeneralException|Throwable
     */
    /**
     * Update the specified resource in storage.
     *
     * @throws GeneralException|Throwable
     */
    public function update(InvestmentRequest $request, Investment $investment)
    {
        $investmentCategory = InvestmentCategory::query()
            ->where('id', $request->investment_category_id)
            ->where('tenant_id', $request->tenant_id)
            ->first();

        if (! $investmentCategory) {
            throw new GeneralException('No director found in this investment category to this shop', 422);
        }

        try {
            DB::beginTransaction();

            $request['user_id'] = $investmentCategory->director_id;

            $investment->update($request->all());

            // Delete attachments marked for removal
            if ($request->has('deletable_attachment_ids')) {
                $investment->attachments()
                    ->whereIn('id', $request->deletable_attachment_ids)
                    ->delete();
            }

            // Add new attachments
            if ($request->has('attachments')) {
                foreach ($request->attachments as $attachmentUrl) {
                    $investment->attachments()->create([
                        'link' => $attachmentUrl,
                    ]);
                }
            }

            DB::commit();

            return successResponse('Investment updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('Failed to update investment', 500, $e);
            }
            throw new GeneralException($e->getMessage(), 500, $e);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Investment $investment)
    {
        $investment->attachments()->delete();
        $investment->delete();

        return successResponse('Investment deleted successfully');
    }
}
