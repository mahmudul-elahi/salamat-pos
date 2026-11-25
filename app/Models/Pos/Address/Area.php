<?php

namespace App\Models\Pos\Address;

use App\Models\Common\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $name
 * @property string|null $thana
 * @property string|null $postcode
 * @property int|null $new_district_id
 */
class Area extends BaseModel
{
    #[\Override]
    protected $guarded = [];

    #[\Override]
    public $casts = [
        'new_district_id' => 'integer',
    ];

    /**
     * @return BelongsTo<NewDistrict, $this>
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(NewDistrict::class, 'new_district_id');
    }
}
