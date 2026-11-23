<?php

declare(strict_types=1);

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    #[\Override]
    protected $fillable = ['title', 'start_date', 'end_date', 'description'];

    #[\Override]
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function departments()
    {
        return $this->belongsToMany(Department::class, 'announcement_department');
    }
}
