<?php

namespace App\Models\Ecommerce\ProductVerify;

use App\Models\Common\BaseModel;
use App\Models\Pos\Address\Area;
use App\Models\Pos\Address\NewDistrict;
use App\Models\Pos\Product\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVerify extends BaseModel
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'product_id',
        'barcode_number',
        'shope_name',
        'invoice_attachment',
        'postcode',
        'address',
        'status',
        'district_id',
        'upazila_id',
        'union_id',
        'area_id',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    protected $appends = ['full_address'];

    public function district(): BelongsTo
    {
        return $this->belongsTo(NewDistrict::class, 'new_district_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function getFullAddressAttribute()
    {
        $street = $this->address;
        $area = $this->area->name ?? '';
        $thana = $this->area->thana ?? '';
        $postcode = $this->area->postcode ?? '';
        $district = $this->area->district->name ?? '';

        return trim("$street, $area, $thana, $postcode, $district", ' ,');
    }
}
