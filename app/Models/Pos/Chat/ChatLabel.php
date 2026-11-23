<?php

namespace App\Models\Pos\Chat;

use App\Models\Common\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ChatLabel extends BaseModel
{
    #[\Override]
    protected $table = 'chat_labels';

    #[\Override]
    protected $fillable = [
        'name',
        'color',
    ];

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(ChatConversation::class, 'chat_conversation_label', 'label_id', 'conversation_id');
    }
}
