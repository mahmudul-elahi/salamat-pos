<?php

namespace App\Models\Pos\Chat;

use App\Models\Common\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatConversationAssignment extends BaseModel
{
    const UPDATED_AT = null;

    #[\Override]
    protected $table = 'chat_conversation_assignments';

    #[\Override]
    protected $fillable = [
        'conversation_id',
        'agent_id',
        'action',
        'performed_by',
        'note',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
