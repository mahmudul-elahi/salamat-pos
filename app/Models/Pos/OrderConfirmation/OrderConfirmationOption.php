<?php

namespace App\Models\Pos\OrderConfirmation;

use App\Models\Common\BaseModel;
use Illuminate\Database\Eloquent\Builder;

class OrderConfirmationOption extends BaseModel
{
    public const TYPE_ADVANCE_MEDIA = 'advance_media';

    public const TYPE_DELIVERY_STATUS = 'delivery_status';

    public const TYPE_DELIVERY_BY = 'delivery_by';

    public const TYPES = [
        self::TYPE_ADVANCE_MEDIA,
        self::TYPE_DELIVERY_STATUS,
        self::TYPE_DELIVERY_BY,
    ];

    #[\Override]
    protected $fillable = [
        'type',
        'name',
        'color',
        'sort_order',
        'status',
    ];

    #[\Override]
    protected $casts = [
        'status' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeOfType(Builder $query, ?string $type): Builder
    {
        return $query->when($type, fn ($q, $type) => $q->where('type', $type));
    }
}
