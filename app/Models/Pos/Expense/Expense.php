<?php

namespace App\Models\Pos\Expense;

use App\Models\Pos\Attachment\Attachment;
use App\Models\Pos\Transaction\Transaction;
use App\Models\Tenant\PaymentMethod\PaymentMethod;
use App\Models\TenantBaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Expense extends TenantBaseModel implements HasMedia
{
    use InteractsWithMedia;

    // Fillable fields
    #[\Override]
    protected $fillable = [
        'date',
        'title',
        'expense_category_id',
        'amount',
        'payment_method_id',
        'note',
        'status',
        'tenant_id',
        'created_by',
        'payment_method_id',
    ];

    #[\Override]
    protected $casts = [
        'amount' => 'float',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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

    public function getDueAmountAttribute()
    {
        return Transaction::query()
            ->where('model_id', $this->id)
            ->where('model_type', self::class)
            ->sum('amount');
    }

    public function getTransactionAmountAttribute()
    {
        return $this->payments()->sum('amount');
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Transaction::class, 'model', 'model_type', 'model_id', 'id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'model');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'id');
    }
}
