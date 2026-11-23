<?php

declare(strict_types=1);

namespace App\Models\Pos\Description;

use Illuminate\Database\Eloquent\Model;

class LongDescription extends Model
{
    #[\Override]
    protected $table = 'long_descriptions';

    #[\Override]
    protected $fillable = [
        'tenant_id',
        'description',
        'model_type',
        'model_id',
    ];

    public function model()
    {
        return $this->morphTo();
    }
}
