<?php

namespace App\Models\Hrm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeProfile extends Model
{
    #[\Override]
    protected $fillable = [
        'user_id',
        'gender',
        'employee_id',
        'joining_date',
        'phone_number',
        'phone_country',
        'married_status',
        'religion',
        'date_of_birth',
        'place_of_birth',
        'tin_no',
        'nid_no',
        'address',
        'residential_address',
        'permanent_address',
        'job_level',
        'blood_type',
    ];

    #[\Override]
    protected $casts = [
        'joining_date' => 'date',
        'date_of_birth' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
