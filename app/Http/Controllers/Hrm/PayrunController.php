<?php

namespace App\Http\Controllers\Hrm;

use App\Http\Controllers\Controller;
use App\Jobs\Hrm\Payrun\GeneratePayslipJob;
use App\Models\Hrm\Payrun;
use App\Services\Hrm\Payrun\PayrunCalculationService;
use Illuminate\Bus\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;

class PayrunController extends Controller
{
    public function __construct(protected PayrunCalculationService $service) {}

    public function index()
    {
        return Payrun::query()
            ->withCount('payslips')
            ->orderByDesc('id')
            ->paginate(request('per_page', 12))
            ->through(fn (Payrun $payrun) => [
                'id' => $payrun->id,
                'uuid' => substr($payrun->uuid, 0, 8),
                'name' => $payrun->name,
                'start_date' => $payrun->start_date->format('Y-m-d'),
                'end_date' => $payrun->end_date->format('Y-m-d'),
                'date_range' => $payrun->start_date->format('j M').' - '.$payrun->end_date->format('j M Y'),
                'total_employees' => $payrun->total_employees,
                'payslips_count' => $payrun->payslips_count,
                'included_percent' => $payrun->total_employees > 0
                    ? (int) round($payrun->payslips_count / $payrun->total_employees * 100)
                    : 0,
                'payrun_period' => $payrun->data['payrun_period'] ?? null,
                'consider_type' => $payrun->data['consider_type'] ?? null,
                'method' => $payrun->data['method'] ?? null,
                'type' => $payrun->type,
                'status' => $payrun->status,
                'generated_on' => $payrun->created_at->format('d-m-Y'),
            ]);
    }

    public function show(Payrun $payrun)
    {
        return $payrun->loadCount('payslips');
    }

    /**
     * Preview of what running the default payrun right now would do —
     * shown in the "Default Payrun" confirmation modal.
     */
    public function defaultPreview()
    {
        $payrunType = $this->service->defaultPayrunType();
        [$start, $end] = $this->service->lastPeriodFor($payrunType);
        $employees = $this->service->eligibleEmployees();
        $beneficiaryValues = $this->service->defaultBeneficiaryValues();

        $mapValue = fn ($value) => [
            'name' => $value->beneficiary->name,
            'amount' => $value->amount,
            'is_percentage' => (bool) $value->is_percentage,
        ];

        $allowances = $beneficiaryValues->filter(fn ($v) => $v->beneficiary->type === 'allowance');
        $deductions = $beneficiaryValues->filter(fn ($v) => $v->beneficiary->type === 'deduction');

        return [
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'employees_count' => $employees->count(),
            'payrun_period' => $payrunType->payrun_period,
            'consider_type' => $payrunType->consider_type,
            'consider_overtime' => $payrunType->consider_overtime,
            'allowances' => $allowances->map($mapValue)->values(),
            'deductions' => $deductions->map($mapValue)->values(),
            'total_allowance_percent' => $allowances->where('is_percentage', true)->sum('amount'),
            'total_deduction_percent' => $deductions->where('is_percentage', true)->sum('amount'),
        ];
    }

    /**
     * Run the default payrun for the last completed period: dispatch one
     * queued job per eligible employee, batched so progress can be polled.
     */
    public function runDefault(Request $request)
    {
        $request->validate(['note' => ['nullable', 'string']]);

        $payrunType = $this->service->defaultPayrunType();
        if (! $payrunType->exists) {
            $payrunType->save();
        }

        [$start, $end] = $this->service->lastPeriodFor($payrunType);
        $employees = $this->service->eligibleEmployees();

        $payrun = Payrun::query()->create([
            'name' => 'Default Payrun - '.$start->format('M Y'),
            'uuid' => (string) Str::uuid(),
            'start_date' => $start,
            'end_date' => $end,
            'total_employees' => $employees->count(),
            'data' => [
                'payrun_period' => $payrunType->payrun_period,
                'consider_type' => $payrunType->consider_type,
                'method' => $payrunType->consider_overtime ? 'default - with_overtime' : 'default',
                'note' => $request->input('note'),
            ],
            'status' => Payrun::STATUS_PROCESSING,
            'type' => Payrun::TYPE_SETTINGS,
        ]);

        if ($employees->isEmpty()) {
            $payrun->update(['status' => Payrun::STATUS_GENERATED]);

            return successResponse('Payrun generated (no eligible employees)', ['payrun_id' => $payrun->id]);
        }

        $jobs = $employees->map(
            fn ($employee) => new GeneratePayslipJob($payrun->id, $employee->id, $payrunType->id)
        )->all();

        $batch = Bus::batch($jobs)
            ->name("payrun-{$payrun->id}")
            ->finally(function (Batch $batch) use ($payrun) {
                $payrun->update([
                    'status' => $batch->failedJobs > 0 ? Payrun::STATUS_FAILED : Payrun::STATUS_GENERATED,
                ]);
            })
            ->dispatch();

        $payrun->update(['batch_id' => $batch->id]);

        return successResponse('Payrun generation started', ['payrun_id' => $payrun->id, 'batch_id' => $batch->id]);
    }

    /**
     * Run a one-off manual payrun for specific departments / employees.
     */
    public function runManual(Request $request)
    {
        $validated = $request->validate([
            'department_ids' => ['required', 'array', 'min:1'],
            'department_ids.*' => ['exists:departments,id'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['exists:users,id'],
            'payrun_period' => ['required', 'in:monthly,customized'],
            'month' => ['required_if:payrun_period,monthly', 'nullable', 'integer', 'min:1', 'max:12'],
            'year' => ['required_if:payrun_period,monthly', 'nullable', 'integer', 'min:2000'],
            'start_date' => ['required_if:payrun_period,customized', 'nullable', 'date'],
            'end_date' => ['required_if:payrun_period,customized', 'nullable', 'date', 'after_or_equal:start_date'],
            'consider_type' => ['required', 'in:hour,daily_log,none'],
            'consider_overtime' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string'],
            'beneficiary_values' => ['nullable', 'array'],
            'beneficiary_values.*.beneficiary_id' => ['required', 'exists:beneficiaries,id'],
            'beneficiary_values.*.amount' => ['required', 'numeric', 'min:0'],
            'beneficiary_values.*.is_percentage' => ['nullable', 'boolean'],
        ]);

        if ($validated['payrun_period'] === 'monthly') {
            $start = Carbon::createFromDate($validated['year'], $validated['month'], 1)->startOfMonth()->startOfDay();
            $end = $start->copy()->endOfMonth()->startOfDay();
        } else {
            $start = Carbon::parse($validated['start_date'])->startOfDay();
            $end = Carbon::parse($validated['end_date'])->startOfDay();
        }

        $departmentIds = $validated['department_ids'];
        $employeeIds = $validated['employee_ids'] ?? [];
        $considerOvertime = $validated['consider_type'] !== 'none' && ($validated['consider_overtime'] ?? false);
        $beneficiaryValuesData = $validated['beneficiary_values'] ?? [];

        $employees = $this->service->eligibleEmployeesForManual($departmentIds, $employeeIds);

        $payrunConfig = [
            'payrun_period' => $validated['payrun_period'],
            'consider_type' => $validated['consider_type'],
            'consider_overtime' => $considerOvertime,
        ];

        $payrun = Payrun::query()->create([
            'name' => 'Manual Payrun - '.$start->format('M Y'),
            'uuid' => (string) Str::uuid(),
            'start_date' => $start,
            'end_date' => $end,
            'total_employees' => $employees->count(),
            'data' => [
                'payrun_period' => $validated['payrun_period'],
                'consider_type' => $validated['consider_type'],
                'method' => 'manual'.($considerOvertime ? ' - with_overtime' : ''),
                'note' => $validated['note'] ?? null,
            ],
            'status' => Payrun::STATUS_PROCESSING,
            'type' => Payrun::TYPE_MANUAL,
        ]);

        if ($employees->isEmpty()) {
            $payrun->update(['status' => Payrun::STATUS_GENERATED]);

            return successResponse('Payrun generated (no eligible employees)', ['payrun_id' => $payrun->id]);
        }

        $jobs = $employees->map(
            fn ($employee) => new GeneratePayslipJob($payrun->id, $employee->id, null, $payrunConfig, $beneficiaryValuesData)
        )->all();

        $batch = Bus::batch($jobs)
            ->name("payrun-{$payrun->id}")
            ->finally(function (Batch $batch) use ($payrun) {
                $payrun->update([
                    'status' => $batch->failedJobs > 0 ? Payrun::STATUS_FAILED : Payrun::STATUS_GENERATED,
                ]);
            })
            ->dispatch();

        $payrun->update(['batch_id' => $batch->id]);

        return successResponse('Manual payrun generation started', ['payrun_id' => $payrun->id, 'batch_id' => $batch->id]);
    }

    /**
     * Delete a payrun and all its payslips / payslip items (cascade).
     * If the batch is still running it is cancelled first.
     */
    public function destroy(Payrun $payrun)
    {
        if ($payrun->batch_id && $payrun->status === Payrun::STATUS_PROCESSING) {
            $batch = Bus::findBatch($payrun->batch_id);
            $batch?->cancel();
        }

        $payrun->delete();

        return successResponse('Payrun deleted successfully');
    }

    /**
     * Batch progress for the navbar/list progress bar.
     */
    public function progress(Payrun $payrun)
    {
        $batch = $payrun->batch_id ? Bus::findBatch($payrun->batch_id) : null;

        return [
            'status' => $payrun->status,
            'progress' => $batch?->progress() ?? ($payrun->status === Payrun::STATUS_GENERATED ? 100 : 0),
            'total_jobs' => $batch?->totalJobs ?? $payrun->total_employees,
            'pending_jobs' => $batch?->pendingJobs ?? 0,
            'failed_jobs' => $batch?->failedJobs ?? 0,
            'finished' => $batch ? $batch->finished() : in_array($payrun->status, [Payrun::STATUS_GENERATED, Payrun::STATUS_FAILED], true),
        ];
    }
}
