<?php

declare(strict_types=1);

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    #[\Override]
    protected $fillable = ['name', 'start_date', 'end_date', 'department_id', 'description'];

    #[\Override]
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
