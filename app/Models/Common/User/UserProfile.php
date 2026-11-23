<?php

declare(strict_types=1);

namespace App\Models\Common\User;

use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    #[\Override]
    protected $fillable = [
        'user_id',
        'gender',
        'phone_number',
        'phone_country',
        'address',
        'date_of_birth',
        'tax_no',
        'portal_access',
        'company_name',
        'first_name',
        'last_name',
        'status',
        'invitation_token',
    ];

    #[\Override]
    protected $appends = ['full_name'];

    #[\Override]
    protected $casts = [
        'portal_access' => 'boolean',
        'user_id' => 'integer',
    ];

    public function getFullNameAttribute()
    {
        return $this->last_name ? $this->first_name.' '.$this->last_name : $this->first_name;
    }
}
