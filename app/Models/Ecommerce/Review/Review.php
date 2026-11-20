<?php

namespace App\Models\Ecommerce\Review;

use App\Helpers\Traits\RevalidatesFrontendTrait;
use App\Models\Common\BaseModel;
use App\Models\Pos\Contact\Contact;
use App\Models\Pos\Product\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends BaseModel
{
    use SoftDeletes, RevalidatesFrontendTrait;

    protected $revalidationType = 'review';

    protected $revalidationSlugField = 'product.slug';

    protected $fillable = [
        'product_id',
        'contact_id',
        'order_detail_id',
        'rating',
        'comment',
        'status',
        'is_admin_review',
        'customer_name',
    ];

    public $casts = [
        'rating' => 'integer',
        'is_admin_review' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id', 'id');
    }
}
