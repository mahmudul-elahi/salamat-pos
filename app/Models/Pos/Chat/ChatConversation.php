<?php

namespace App\Models\Pos\Chat;

use App\Models\Common\BaseModel;
use App\Models\Pos\Contact\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChatConversation extends BaseModel
{
    protected $table = 'chat_conversations';

    protected $fillable = [
        'channel',
        'contact_id',
        'tenant_id',
        'external_id',
        'profile_name',
        'assigned_agent_id',
        'assigned_team_id',
        'status',
        'priority',
        'last_message_at',
        'last_customer_message_at',
        'unread_count',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'last_customer_message_at' => 'datetime',
        'unread_count' => 'integer',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }

    public function assignedTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'assigned_team_id');
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(ChatLabel::class, 'chat_conversation_label', 'conversation_id', 'label_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ChatConversationAssignment::class, 'conversation_id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(ChatMessage::class, 'conversation_id')->latestOfMany();
    }
}
