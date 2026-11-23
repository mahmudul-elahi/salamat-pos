<?php

namespace App\Models\Pos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    use HasFactory;

    #[\Override]
    protected $fillable = [
        'entry_job_id',
        'name',
        'email',
        'phone',
        'resume',
        'message',
        'status',
    ];

    public function entryJob()
    {
        return $this->belongsTo(EntryJob::class);
    }

    public function getResumeAttribute($value)
    {
        if (! $value) {
            return null;
        }

        return asset($value); // Assuming value is relative path in public or storage accessible via asset()
    }
}
