<?php

namespace App\Models\Ecommerce\Faq;

use App\Helpers\Traits\RevalidatesFrontendTrait;
use App\Models\TenantBaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Faq extends TenantBaseModel
{
    use RevalidatesFrontendTrait;

    protected string $revalidationType = 'faq';

    #[\Override]
    protected $fillable = [
        'tenant_id',
        'faq_category_id',
        'lang',
        'group_id',
        'question',
        'answer',
        'order',
        'status',
        'views',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(FaqCategory::class, 'faq_category_id');
    }
}
