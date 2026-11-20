<?php

declare(strict_types=1);

namespace App\Events\Chat;

use App\Models\Pos\Chat\ChatConversation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatConversationAssigned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ChatConversation $conversation) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat-inbox'),
            new PrivateChannel('chat-conversation.'.$this->conversation->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'conversation.assigned';
    }

    /**
     * ShouldBroadcast events queue their own broadcast job onto 'default'
     * unless told otherwise here - keep it on the same worker as the rest
     * of the Meta/chat pipeline instead.
     */
    public function broadcastQueue(): string
    {
        return 'meta-message';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'assigned_agent_id' => $this->conversation->assigned_agent_id,
            'status' => $this->conversation->status,
        ];
    }
}
