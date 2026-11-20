<?php

namespace App\Models\Pos\Attachment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Attachment extends Model
{
    protected $fillable = [
        'link',
    ];

    public function model(): MorphTo
    {
        return $this->morphTo();
    }
}
