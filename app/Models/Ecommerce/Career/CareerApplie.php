<?php

namespace App\Models\Ecommerce\Career;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CareerApplie extends Model
{
    use SoftDeletes;

    protected $table = 'career_applies';
    public function career()
    {
        return $this->belongsTo(Career::class, 'career_id', 'id');
    }
}
