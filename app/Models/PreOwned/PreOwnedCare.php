<?php

namespace App\Models\PreOwned;

use App\Models\Pos\Care\Care;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PreOwnedCare extends Model
{
    use SoftDeletes;

    #[\Override]
    protected $fillable = [
        'pre_owned_id',
        'care_id',
    ];

    public function preOwned(): BelongsTo
    {
        return $this->belongsTo(PreOwned::class);
    }

    public function care(): BelongsTo
    {
        return $this->belongsTo(Care::class);
    }
}
