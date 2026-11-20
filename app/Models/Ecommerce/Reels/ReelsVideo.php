<?php

declare(strict_types=1);

namespace App\Models\Ecommerce\Reels;

use App\Helpers\Traits\RevalidatesFrontendTrait;
use App\Models\Pos\Product\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReelsVideo extends Model
{
    use HasFactory, RevalidatesFrontendTrait;

    protected string $revalidationType = 'reel-video';

    protected $fillable = [
        'product_id',
        'video_link',
        'status',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
