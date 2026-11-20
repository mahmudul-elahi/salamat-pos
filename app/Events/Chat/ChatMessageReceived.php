<?php

namespace App\Events\Chat;

use App\Models\Pos\Chat\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ChatMessage $message)
    {
    }

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat-conversation.' . $this->message->conversation_id),
            new PrivateChannel('chat-inbox'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.received';
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
        $conversation = $this->message->conversation;

        return [
            'message' => [
                'id' => $this->message->id,
                'conversation_id' => $this->message->conversation_id,
                'direction' => $this->message->direction,
                'type' => $this->message->type,
                'body' => $this->message->body,
                // Without these three, ChatPanel.vue's img/video/audio/document
                // v-if chain (which requires type + media_url) never matches for
                // a live-pushed message - it only rendered after a full page
                // reload re-fetched the message list over HTTP, which does
                // include them.
                'media_id' => $this->message->media_id,
                'media_url' => $this->message->media_url,
                'media_mime_type' => $this->message->media_mime_type,
                'status' => $this->message->status,
                'created_at' => $this->message->created_at?->toDateTimeString(),
            ],
            'conversation' => [
                'id' => $conversation->id,
                'channel' => $conversation->channel,
                'external_id' => $conversation->external_id,
                'profile_name' => $conversation->profile_name,
                'assigned_agent_id' => $conversation->assigned_agent_id,
                'status' => $conversation->status,
                'unread_count' => $conversation->unread_count,
                'last_message_at' => $conversation->last_message_at?->toDateTimeString(),
            ],
        ];
    }
}
