<?php

namespace App\Jobs\Hrm\Payrun;

use App\Models\Hrm\Beneficiary;
use App\Models\Hrm\BeneficiaryValue;
use App\Models\Hrm\Payrun;
use App\Models\Hrm\PayrunType;
use App\Models\User;
use App\Services\Hrm\Payrun\PayrunCalculationService;
use Illuminate\Support\Collection;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Generates one employee's payslip for a payrun. Dispatched in a batch (one
 * job per employee) so the frontend can poll a progress percentage.
 */
class GeneratePayslipJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $payrunId,
        public int $employeeId,
        public ?int $payrunTypeId = null,
        public ?array $payrunConfig = null,
        public ?array $beneficiaryValuesData = null,
    ) {
    }

    public function handle(PayrunCalculationService $service): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $payrun = Payrun::findOrFail($this->payrunId);
        $payrunType = $this->payrunTypeId
            ? PayrunType::findOrFail($this->payrunTypeId)
            : new PayrunType($this->payrunConfig ?? []);

        $employee = User::query()
            ->with(['currentDepartment.department.workShifts.schedules', 'workShifts.schedules', 'currentSalary'])
            ->findOrFail($this->employeeId);

        $beneficiaryValues = $this->beneficiaryValuesData !== null
            ? $this->buildBeneficiaryCollection($this->beneficiaryValuesData)
            : $service->defaultBeneficiaryValues();

        $result = $service->calculateForEmployee(
            $employee,
            $payrun->start_date,
            $payrun->end_date,
            $payrunType,
            $beneficiaryValues
        );

        // null means no attendance for this employee in an attendance-based
        // payrun — skip payslip generation entirely.
        if ($result === null) {
            return;
        }

        DB::transaction(function () use ($payrun, $employee, $payrunType, $result) {
            $payslip = $payrun->payslips()->create([
                'employee_id' => $employee->id,
                'department_id' => $employee->currentDepartment?->department_id,
                'start_date' => $payrun->start_date,
                'end_date' => $payrun->end_date,
                'basic_salary' => $result['basic_salary'],
                'net_salary' => $result['net_salary'],
                'period' => $payrunType->payrun_period,
                'consider_overtime' => $payrunType->consider_overtime,
                'consider_type' => $payrunType->consider_type,
            ]);

            $payslip->items()->createMany($result['items']);
        });
    }

    private function buildBeneficiaryCollection(array $data): Collection
    {
        $ids = collect($data)->pluck('beneficiary_id')->filter()->unique()->values()->all();
        $beneficiaryMap = Beneficiary::query()->whereIn('id', $ids)->get()->keyBy('id');

        return collect($data)
            ->filter(fn($row) => isset($row['beneficiary_id']) && $beneficiaryMap->has($row['beneficiary_id']))
            ->map(function ($row) use ($beneficiaryMap) {
                $bv = new BeneficiaryValue([
                    'beneficiary_id' => $row['beneficiary_id'],
                    'amount'         => $row['amount'],
                    'is_percentage'  => $row['is_percentage'] ?? false,
                ]);
                $bv->setRelation('beneficiary', $beneficiaryMap->get($row['beneficiary_id']));
                return $bv;
            })
            ->values();
    }

    public function failed(Throwable $exception): void
    {
        Log::error("GeneratePayslipJob failed for payrun #{$this->payrunId}, employee #{$this->employeeId}: {$exception->getMessage()}");
    }
}
