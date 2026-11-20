<?php

namespace App\Models\Pos\Chat;

use App\Models\Common\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends BaseModel
{
    protected $table = 'teams';

    protected $fillable = [
        'name',
        'description',
        'icon',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_user');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(ChatConversation::class, 'assigned_team_id');
    }
}
