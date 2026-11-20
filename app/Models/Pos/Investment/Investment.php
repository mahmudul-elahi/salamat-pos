<?php

namespace App\Models\Pos\Investment;

use App\Models\Pos\Attachment\Attachment;
use App\Models\Pos\Contact\Contact;
use App\Models\Pos\Transaction\Transaction;
use App\Models\TenantBaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Investment extends TenantBaseModel implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'tenant_id',
        'title',
        'date',
        'investment_category_id',
        'user_id',
        'amount',
        'note',
        'contact_id',
        'ledger_id',
        'expense_id',
        'created_by',
    ];

    public function investmentCategory(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(InvestmentCategory::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function createdBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function contact(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id', 'id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'model');
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'model', 'model_type', 'model_id', 'id');
    }

    public function getImagesAttribute(): array
    {
        return $this->getMedia('images')->map(function ($media) {
            return [
                'id' => $media->id,
                'url' => $media->getFullUrl(),
            ];
        })->toArray();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images');
    }
}
