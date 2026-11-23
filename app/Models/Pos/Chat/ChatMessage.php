<?php

namespace App\Models\Pos\Chat;

use App\Models\Common\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends BaseModel
{
    #[\Override]
    protected $table = 'chat_messages';

    #[\Override]
    protected $fillable = [
        'conversation_id',
        'direction',
        'external_message_id',
        'type',
        'body',
        'media_id',
        'media_url',
        'media_mime_type',
        'template_name',
        'status',
        'error_code',
        'error_message',
        'sender_user_id',
        'raw_payload',
        'sent_at',
        'delivered_at',
        'read_at',
    ];

    #[\Override]
    protected $casts = [
        'raw_payload' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }
}
