<?php

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $fillable = [
        'asset_category_id',
        'asset_name',
        'asset_code',
        'serial_number',
        'purchase_date',
        'purchase_cost',
        'note',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_cost' => 'decimal:2',
    ];

    protected $appends = ['status'];

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function assignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AssetAssignment::class);
    }

    // At most one of these can exist per asset at a time - assign()/returnAsset()
    // in AssetController always close out the previous open row (returned_date
    // set) before a new one is opened, so a plain whereNull is enough here
    // without needing hasOne()->latestOfMany().
    public function currentAssignment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(AssetAssignment::class)->whereNull('returned_date');
    }

    public function getStatusAttribute(): string
    {
        return $this->relationLoaded('currentAssignment')
            ? ($this->getRelation('currentAssignment') ? 'assigned' : 'available')
            : ($this->currentAssignment()->exists() ? 'assigned' : 'available');
    }
}
