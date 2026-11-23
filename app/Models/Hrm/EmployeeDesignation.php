<?php

namespace App\Models\Hrm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDesignation extends Model
{
    #[\Override]
    protected $fillable = [
        'employee_id',
        'designation_id',
        'start_date',
        'end_date',
    ];

    #[\Override]
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }
}
