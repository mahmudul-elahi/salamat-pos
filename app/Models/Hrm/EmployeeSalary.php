<?php

namespace App\Models\Hrm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSalary extends Model
{
    #[\Override]
    protected $fillable = [
        'employee_id',
        'amount',
        'start_date',
        'end_date',
    ];

    #[\Override]
    protected $casts = [
        'amount' => 'float',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }
}
