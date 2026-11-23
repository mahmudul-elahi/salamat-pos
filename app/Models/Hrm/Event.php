<?php

declare(strict_types=1);

namespace App\Models\Hrm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    #[\Override]
    protected $fillable = [
        'event_type', 'title', 'start_datetime', 'end_datetime', 'location', 'description',
    ];

    #[\Override]
    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
    ];

    public function departments()
    {
        return $this->belongsToMany(Department::class, 'event_department');
    }

    public function employees()
    {
        return $this->belongsToMany(User::class, 'event_user');
    }
}
