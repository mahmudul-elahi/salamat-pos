<?php

namespace App\Http\Controllers\Pos\Expense;

use App\Exceptions\GeneralException;
use App\Filters\Landlord\Expense\ExpenseFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Landlord\Expense\ExpenseRequest;
use App\Models\Pos\Expense\Expense;
use App\Models\Pos\Investment\Investment;
use App\Models\Pos\Investment\InvestmentCategory;
use App\Models\Tenant\PaymentMethod\PaymentMethod;
use App\Services\Setting\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class ExpenseController extends Controller
{
    public function __construct(protected ExpenseFilter $filter) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $perPage = request('per_page', 10);

        return Expense::query()
            ->filters($this->filter)
            ->with(['category:id,name,logo', 'paymentMethod:id,name,logo', 'createdBy:id,name', 'tenant' => function ($query) {
                $query->select('id', 'name')
                    ->with('setting', function ($query) {
                        $query->where('name', 'company_logo');
                    });
            }])
            ->orderByDesc('id')
            ->paginate($perPage)
            ->through(fn ($expense) => [
                ...$expense->toArray(),
                'invoice_number' => "EXP-{$expense->id}",
                'images' => $expense->attachments()->pluck('link')->toArray(),
            ])->toArray();
    }

    /**
     * Store a newly created resource in storage.
     *
     * @throws Throwable
     */
    public function store(ExpenseRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();
            $expense = Expense::query()->create(array_merge($request->validated(), [
                'created_by' => auth()->id(),
            ]));

            if ($request->has('attachments')) {
                foreach ($request->attachments as $attachmentUrl) {
                    $expense->attachments()->create([
                        'link' => $attachmentUrl,
                    ]);
                }
            }

            $this->syncExpenseDeposit($expense, $request->payment_method_id);

            DB::commit();

            return successResponse('Expense has been created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('Expense has been created failed', 500, $e);
            }
            throw new GeneralException($e->getMessage(), 500, $e);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Expense $expense)
    {
        $expense->load('attachments');

        return $expense;
    }

    /**
     * @throws Throwable
     * @throws GeneralException
     */
    public function update(ExpenseRequest $request, Expense $expense)
    {
        try {
            DB::beginTransaction();
            $expense->update([
                'tenant_id' => $request->tenant_id ?? null,
                'date' => $request->date,
                'amount' => $request->amount,
                'expense_category_id' => $request->expense_category_id ?? null,
                'payment_method_id' => $request->payment_method_id ?? null,
                'note' => $request->note ?? null,
            ]);

            // Delete attachments marked for removal
            if ($request->has('deletable_attachment_ids')) {
                $expense->attachments()->whereIn('id', $request->deletable_attachment_ids)->delete();
            }

            if ($request->has('attachments') && ! $request->hasFile('attachments')) {
                foreach ($request->attachments as $attachmentUrl) {
                    $expense->attachments()->create([
                        'link' => $attachmentUrl,
                    ]);
                }
            }

            $this->syncExpenseDeposit($expense, $request->payment_method_id);

            DB::commit();

            return successResponse('Expense has been updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('Expense has been updated failed', 500, $e);
            }
            throw new GeneralException($e->getMessage(), 500, $e);
        }
    }

    /**
     * JSON, not a PDF — the frontend renders this into its own voucher
     * layout and prints it via window.print(), the same way SaleDetails.vue
     * handles sale invoices, rather than server-rendering a PDF.
     */
    public function voucherData(Expense $expense)
    {
        $expense->load(['category:id,name', 'paymentMethod:id,name,logo', 'createdBy:id,name', 'tenant:id,name']);

        $tenantSettings = resolve(SettingService::class)->getFormattedSettings('tenant', $expense->tenant_id);

        return response()->json([
            'id' => $expense->id,
            'invoice_number' => 'EXP-'.$expense->id,
            'title' => $expense->title,
            'date' => $expense->date,
            'amount' => (float) $expense->amount,
            'note' => $expense->note,
            'category' => $expense->category?->name,
            'payment_method' => $expense->paymentMethod?->name,
            'payment_method_logo' => $expense->paymentMethod?->logo,
            'created_by' => $expense->createdBy?->name,
            'tenant' => $expense->tenant?->name,
            'tenant_settings' => $tenantSettings,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expense $expense): JsonResponse
    {
        $expense->attachments()->delete();
        Investment::where('expense_id', $expense->id)->delete();
        $expense->delete();

        return successResponse('Expense deleted successfully');
    }

    private function syncExpenseDeposit(Expense $expense, ?int $paymentMethodId): void
    {
        Investment::where('expense_id', $expense->id)->delete();

        if (! $paymentMethodId) {
            return;
        }

        $paymentMethod = PaymentMethod::find($paymentMethodId);
        if (! $paymentMethod?->director_id) {
            return;
        }

        $depositCategory = InvestmentCategory::where('director_id', $paymentMethod->director_id)->first();
        if (! $depositCategory) {
            return;
        }

        Investment::create([
            'user_id' => $paymentMethod->director_id,
            'title' => $expense->title,
            'date' => $expense->date,
            'investment_category_id' => $depositCategory->id,
            'amount' => $expense->amount,
            'note' => $expense->note,
            'tenant_id' => $expense->tenant_id,
            'expense_id' => $expense->id,
        ]);
    }
}
