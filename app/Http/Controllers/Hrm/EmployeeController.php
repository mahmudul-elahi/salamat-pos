<?php

namespace App\Http\Controllers\Hrm;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Mail\Hrm\Employee\WelcomeEmployeeMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class EmployeeController extends Controller
{
    public function index()
    {
        return User::query()
            ->where('is_employee', true)
            ->when(
                request('search'),
                fn ($q, $s) => $q
                    ->where('name', 'LIKE', "%{$s}%")
                    ->orWhere('email', 'LIKE', "%{$s}%")
                    ->orWhereHas('employeeProfile', fn ($ep) => $ep->where('employee_id', 'LIKE', "%{$s}%"))
            )
            ->with([
                'employeeProfile',
                'currentDesignation.designation',
                'currentDepartment.department',
                'currentEmployeeStatus.employeeStatus',
                'currentSalary',
                'roles',
            ])
            // Active employees first regardless of when they joined - anyone
            // not 'active' (inactive, invited, etc.) sorts after them, most
            // recently created first within each group.
            ->orderByRaw("status = 'active' DESC")
            ->latest()
            ->paginate(request('per_page', 10))
            ->through(fn (User $employee) => [
                'id' => $employee->id,
                'name' => $employee->name,
                'email' => $employee->email,
                'profile_picture' => $employee->profile_picture,
                'status' => $employee->status,
                'gender' => $employee->employeeProfile?->gender,
                'designation' => $employee->currentDesignation?->designation?->name,
                'department' => $employee->currentDepartment?->department?->name,
                'employee_status' => $employee->currentEmployeeStatus?->employeeStatus?->name,
                'employee_status_color' => $employee->currentEmployeeStatus?->employeeStatus?->color,
                'joining_date' => $employee->employeeProfile?->joining_date?->format('Y-m-d'),
                'salary' => $employee->currentSalary?->amount,
                'role' => $employee->roles->first()?->name,
            ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateRequest($request);

        $plainPassword = Str::password(12, symbols: false);

        try {
            DB::beginTransaction();

            $employee = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($plainPassword),
                'status' => 'active',
                'is_employee' => true,
            ]);

            $employee->employeeProfile()->create([
                'gender' => $validated['gender'],
                'joining_date' => $validated['joining_date'],
                'employee_id' => 'EMP-'.$employee->id,
                'phone_number' => $validated['phone'] ?? null,
            ]);

            $employee->employeeDesignations()->create([
                'designation_id' => $validated['designation_id'],
                'start_date' => $validated['joining_date'],
            ]);

            $employee->employeeDepartments()->create([
                'department_id' => $validated['department_id'],
                'start_date' => $validated['joining_date'],
            ]);

            $employee->employeeStatuses()->create([
                'employee_status_id' => $validated['employee_status_id'],
                'start_date' => $validated['joining_date'],
            ]);

            $employee->employeeSalaries()->create([
                'amount' => $validated['salary'],
                'start_date' => $validated['joining_date'],
            ]);

            if (! empty($validated['tenant_ids'])) {
                $employee->tenants()->sync($validated['tenant_ids']);
            }

            if (! empty($validated['role_id'])) {
                $employee->roles()->sync([$validated['role_id']]);
            }

            DB::commit();

            Mail::to($employee->email)->queue(
                new WelcomeEmployeeMail($employee, $plainPassword)
                    ->onQueue('hrm-employee-invitation')
            );

            return successResponse('Employee created successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('Failed to create employee', 500, $e);
            }
            throw new GeneralException($e->getMessage(), 500, $e);
        }
    }

    public function makeEmployee(Request $request, User $user): JsonResponse
    {
        if ($user->is_employee) {
            throw new GeneralException('This user is already an employee.');
        }

        $validated = $request->validate([
            'gender' => ['required', Rule::in(['male', 'female', 'others'])],
            'designation_id' => ['required', 'exists:designations,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'employee_status_id' => ['required', 'exists:employee_statuses,id'],
            'joining_date' => ['required', 'date'],
            'salary' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            DB::beginTransaction();

            $user->update(['is_employee' => true]);

            $user->employeeProfile()->updateOrCreate([], [
                'gender' => $validated['gender'],
                'joining_date' => $validated['joining_date'],
                'employee_id' => 'EMP-'.$user->id,
                'phone_number' => $user->phone,
            ]);

            $user->employeeDesignations()->create([
                'designation_id' => $validated['designation_id'],
                'start_date' => $validated['joining_date'],
            ]);

            $user->employeeDepartments()->create([
                'department_id' => $validated['department_id'],
                'start_date' => $validated['joining_date'],
            ]);

            $user->employeeStatuses()->create([
                'employee_status_id' => $validated['employee_status_id'],
                'start_date' => $validated['joining_date'],
            ]);

            $user->employeeSalaries()->create([
                'amount' => $validated['salary'],
                'start_date' => $validated['joining_date'],
            ]);

            DB::commit();

            return successResponse('User converted to employee successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('Failed to make employee', 500, $e);
            }
            throw new GeneralException($e->getMessage(), 500, $e);
        }
    }

    public function show(User $employee)
    {
        $employee->loadMissing([
            'employeeProfile',
            'currentDesignation',
            'currentDepartment',
            'currentEmployeeStatus',
            'currentSalary',
            'tenants',
            'roles',
        ]);

        return [
            'id' => $employee->id,
            'name' => $employee->name,
            'email' => $employee->email,
            'phone' => $employee->phone,
            'gender' => $employee->employeeProfile?->gender,
            'designation_id' => $employee->currentDesignation?->designation_id,
            'department_id' => $employee->currentDepartment?->department_id,
            'employee_status_id' => $employee->currentEmployeeStatus?->employee_status_id,
            'joining_date' => $employee->employeeProfile?->joining_date?->format('Y-m-d'),
            'salary' => $employee->currentSalary?->amount,
            'tenant_ids' => $employee->tenants->pluck('id')->toArray(),
            'role_id' => $employee->roles->first()?->id,
        ];
    }

    public function update(Request $request, User $employee)
    {
        $validated = $this->validateRequest($request, $employee);

        try {
            DB::beginTransaction();

            $employee->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);

            $employee->employeeProfile()->updateOrCreate([], [
                'gender' => $validated['gender'],
                'joining_date' => $validated['joining_date'],
            ]);

            $this->updateHistory($employee->currentDesignation, $employee->employeeDesignations(), [
                'designation_id' => $validated['designation_id'],
            ], 'designation_id', $validated['designation_id']);

            $this->updateHistory($employee->currentDepartment, $employee->employeeDepartments(), [
                'department_id' => $validated['department_id'],
            ], 'department_id', $validated['department_id']);

            $this->updateHistory($employee->currentEmployeeStatus, $employee->employeeStatuses(), [
                'employee_status_id' => $validated['employee_status_id'],
            ], 'employee_status_id', $validated['employee_status_id']);

            $this->updateHistory($employee->currentSalary, $employee->employeeSalaries(), [
                'amount' => $validated['salary'],
            ], 'amount', $validated['salary']);

            $employee->tenants()->sync($validated['tenant_ids'] ?? []);
            $employee->roles()->sync($validated['role_id'] ? [$validated['role_id']] : []);

            DB::commit();

            return successResponse('Employee updated successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            if (app()->environment('production')) {
                throw new GeneralException('Failed to update employee', 500, $e);
            }
            throw new GeneralException($e->getMessage(), 500, $e);
        }
    }

    public function toggleStatus(User $employee): JsonResponse
    {
        $newStatus = $employee->status === 'active' ? 'inactive' : 'active';

        $employee->update([
            'status' => $newStatus,
        ]);

        // Only revoke sessions when deactivating - reactivating shouldn't
        // sign the employee out of anything.
        if ($newStatus === 'inactive') {
            $employee->tokens()->delete();
        }

        return successResponse('Employee status updated successfully');
    }

    public function resendEmail(User $employee): JsonResponse
    {
        if (! $employee->is_employee) {
            throw new GeneralException('User is not an employee.');
        }

        $plainPassword = Str::password(12, symbols: false);
        $employee->update(['password' => Hash::make($plainPassword)]);

        Mail::to($employee->email)->queue(
            new WelcomeEmployeeMail($employee, $plainPassword)
                ->onQueue('hrm-employee-invitation')
        );

        return successResponse('Email sent successfully to '.$employee->email);
    }

    public function destroy(User $employee): JsonResponse
    {
        if ($employee->is_admin) {
            throw new GeneralException('Action not allowed');
        }

        if ($employee->id == auth()->id()) {
            throw new GeneralException("Can't delete own account");
        }

        $employee->delete();

        return successResponse('Employee deleted successfully');
    }

    /**
     * Close the current history record (if the value changed) and open a new one.
     */
    private function updateHistory($current, $relation, array $newAttributes, string $compareKey, $newValue): void
    {
        if ($current && (string) $current->{$compareKey} === (string) $newValue) {
            return;
        }

        $current?->update(['end_date' => now()->toDateString()]);

        $relation->create($newAttributes + ['start_date' => now()->toDateString()]);
    }

    private function validateRequest(Request $request, ?User $employee = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($employee?->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'gender' => ['required', Rule::in(['male', 'female', 'others'])],
            'designation_id' => ['required', 'exists:designations,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'employee_status_id' => ['required', 'exists:employee_statuses,id'],
            'joining_date' => ['required', 'date'],
            'salary' => ['required', 'numeric', 'min:0'],
            'tenant_ids' => ['required', 'array', 'min:1'],
            'tenant_ids.*' => ['exists:tenants,id'],
            'role_id' => ['required', 'exists:roles,id'],
        ]);
    }
}
