<?php

namespace App\Models\Pos\Income;

use App\Models\Pos\Attachment\Attachment;
use App\Models\Pos\Contact\Contact;
use App\Models\Pos\Transaction\Transaction;
use App\Models\TenantBaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Income extends TenantBaseModel implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'title',
        'date',
        'amount',
        'note',
        'status',
        'user_id',
        'income_category_id',
        'tenant_id',
        'contact_id',
        'ledger_id',
        'created_by',
    ];

    public $casts = [
        'income_category_id' => 'integer',
        'amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id', 'id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(IncomeCategory::class, 'income_category_id');
    }

    public function getTransactionAmountAttribute()
    {
        return $this->payments()->sum('amount');
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'model', 'model_type', 'model_id', 'id');
    }

    public function getImagesAttribute(): array
    {
        return $this->getMedia('images')->map(fn ($media) => [
            'id' => $media->id,
            'url' => $media->getFullUrl(),
        ])->toArray();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'model');
    }
}
