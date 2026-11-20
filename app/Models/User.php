<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Filters\BaseFilter;
use App\Models\Hrm\Attendance;
use App\Models\Hrm\EmployeeContact;
use App\Models\Hrm\EmployeeDepartment;
use App\Models\Hrm\EmployeeDesignation;
use App\Models\Hrm\EmployeeDocument;
use App\Models\Hrm\EmployeeProfile;
use App\Models\Hrm\EmployeeSalary;
use App\Models\Hrm\EmployeeStatusHistory;
use App\Models\Hrm\LeaveRequest;
use App\Models\Hrm\Payslip;
use App\Models\Hrm\WorkShift;
use App\Models\Role\Role;
use App\Models\Tenant\Tenant;
use App\Models\Traits\User\UserMethodTrait;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
// use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Authenticatable implements HasMedia
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens,
        HasFactory,
        InteractsWithMedia,
        Notifiable,
        SoftDeletes,
        UserMethodTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'title',
        'password',
        'is_admin',
        'phone',
        'profile_photo',
        'role',
        'status',
        'is_employee',
        'timezone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_employee' => 'boolean',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id');
    }

    public function role()
    {
        return $this->roles()->toOne();
    }

    public function employeeProfile(): HasOne
    {
        return $this->hasOne(EmployeeProfile::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(EmployeeContact::class, 'user_id');
    }

    public function employeeDesignations(): HasMany
    {
        return $this->hasMany(EmployeeDesignation::class, 'employee_id');
    }

    public function currentDesignation(): HasOne
    {
        return $this->hasOne(EmployeeDesignation::class, 'employee_id')
            ->whereNull('end_date')
            ->latestOfMany('start_date');
    }

    public function employeeDepartments(): HasMany
    {
        return $this->hasMany(EmployeeDepartment::class, 'employee_id');
    }

    public function currentDepartment(): HasOne
    {
        return $this->hasOne(EmployeeDepartment::class, 'employee_id')
            ->whereNull('end_date')
            ->latestOfMany('start_date');
    }

    public function employeeStatuses(): HasMany
    {
        return $this->hasMany(EmployeeStatusHistory::class, 'employee_id');
    }

    public function currentEmployeeStatus(): HasOne
    {
        return $this->hasOne(EmployeeStatusHistory::class, 'employee_id')
            ->whereNull('end_date')
            ->latestOfMany('start_date');
    }

    public function employeeSalaries(): HasMany
    {
        return $this->hasMany(EmployeeSalary::class, 'employee_id');
    }

    public function currentSalary(): HasOne
    {
        return $this->hasOne(EmployeeSalary::class, 'employee_id')
            ->whereNull('end_date')
            ->latestOfMany('start_date');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class, 'employee_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class, 'employee_id');
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(
            Tenant::class,
            'tenant_user',
            'user_id',
            'tenant_id'
        );
    }

    public function tenant()
    {
        return $this->tenants()->toOne();
    }

    public function workShifts(): BelongsToMany
    {
        return $this->belongsToMany(WorkShift::class, 'work_shift_user');
    }

    public function scopeFilters(Builder $query, BaseFilter $filter): Builder
    {
        return $filter->apply($query);
    }

    public function getProfilePictureAttribute(): ?string
    {
        $profilePicture = $this->getFirstMedia('profile_picture');

        return $profilePicture?->getFullUrl();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('profile_picture')
            ->singleFile();
    }
}
