<?php

namespace App\Models\Hrm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeneficiaryValue extends Model
{
    /**
     * `model_name` / `model_id` identify who this value belongs to:
     * - the org-wide default (see BeneficiaryValueController::DEFAULT_MODEL_*)
     * - or, in the future, a specific employee override
     *   (model_name = App\Models\User::class, model_id = the user's id).
     */
    protected $fillable = [
        'model_name',
        'model_id',
        'beneficiary_id',
        'amount',
        'is_percentage',
    ];

    protected $casts = [
        'amount' => 'float',
        'is_percentage' => 'boolean',
    ];

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class);
    }
}
