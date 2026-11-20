<?php

namespace App\Services\Chat;

use App\Events\Chat\ChatConversationAssigned;
use App\Events\Chat\ChatMessageReceived;
use App\Events\Chat\ChatMessageStatusUpdated;
use App\Exceptions\GeneralException;
use App\Jobs\Instagram\SendInstagramMessageJob;
use App\Jobs\Messenger\SendMessengerMessageJob;
use App\Jobs\WhatsApp\FetchWhatsAppInboundMediaJob;
use App\Jobs\WhatsApp\SendWhatsAppMessageJob;
use App\Models\Pos\Chat\ChatConversation;
use App\Models\Pos\Chat\ChatConversationAssignment as AssignmentLog;
use App\Models\Pos\Chat\ChatMessage;
use App\Models\User;

class ChatService
{
    public function findOrCreateConversation(string $channel, string $externalId, ?string $profileName = null, ?string $profilePictureUrl = null): ChatConversation
    {
        $externalId = $channel === 'whatsapp' ? $this->normalizePhone($externalId) : $externalId;

        $conversation = ChatConversation::query()
            ->where('channel', $channel)
            ->where('external_id', $externalId)
            ->first();

        if ($conversation) {
            if ($profileName && $conversation->profile_name !== $profileName) {
                $conversation->update(['profile_name' => $profileName]);
            }

            if ($profilePictureUrl && !$conversation->contact?->profile_picture) {
                $conversation->contact?->update(['profile_picture' => $profilePictureUrl]);
            }

            return $conversation;
        }

        // A new conversation never auto-populates contact_id, on any
        // channel - not even a WhatsApp number that already matches an
        // existing Contact. It stays contact-less until an agent explicitly
        // attaches/creates one from the chat panel (see attachContact()
        // below) - inbound messages alone shouldn't populate the
        // People/Customers list.
        return ChatConversation::query()->create([
            'channel' => $channel,
            'contact_id' => null,
            'external_id' => $externalId,
            'profile_name' => $profileName,
            'status' => 'open',
        ]);
    }

    /**
     * @throws GeneralException
     */
    /**
     * @throws GeneralException
     */
    public function attachContact(ChatConversation $conversation, int $contactId, User $actor): ChatConversation
    {
        $this->authorizeReply($conversation, $actor);

        $conversation->update(['contact_id' => $contactId]);

        return $conversation->fresh('contact');
    }

    public function storeInboundMessage(ChatConversation $conversation, array $payload): ChatMessage
    {
        $type = $payload['type'] ?? 'text';

        $message = ChatMessage::query()->create([
            'conversation_id' => $conversation->id,
            'direction' => 'inbound',
            'external_message_id' => $payload['id'] ?? null,
            'type' => $type,
            'body' => $payload['text']['body'] ?? $payload['button']['text'] ?? $payload['interactive']['button_reply']['title'] ?? null,
            'media_id' => $payload[$type]['id'] ?? null,
            // WhatsApp only ever gives a media *id* (fetched later by
            // FetchWhatsAppInboundMediaJob); Messenger/Instagram attachments
            // arrive with a directly-usable public URL already, no fetch needed.
            'media_url' => $payload['media_url'] ?? null,
            'status' => 'received',
            'raw_payload' => $payload,
        ]);

        $conversation->update([
            'status' => $conversation->status === 'closed' ? 'open' : $conversation->status,
            'last_message_at' => now(),
            'last_customer_message_at' => now(),
            'unread_count' => $conversation->unread_count + 1,
        ]);

        if ($conversation->channel === 'whatsapp'
            && in_array($type, ['image', 'video', 'audio', 'document', 'sticker'], true)
            && $message->media_id) {
            FetchWhatsAppInboundMediaJob::dispatch($message->id)->onQueue('meta-message');
        }

        ChatMessageReceived::dispatch($message->fresh());

        return $message;
    }

    public function updateMessageStatus(string $externalMessageId, string $status, ?array $errors = null): void
    {
        $message = ChatMessage::query()->where('external_message_id', $externalMessageId)->first();

        if (!$message) {
            return;
        }

        $timestampColumn = match ($status) {
            'delivered' => 'delivered_at',
            'read' => 'read_at',
            default => null,
        };

        $message->update([
            'status' => $status,
            'error_code' => $errors[0]['code'] ?? $message->error_code,
            'error_message' => $errors[0]['message'] ?? $message->error_message,
            ...($timestampColumn ? [$timestampColumn => now()] : []),
        ]);

        ChatMessageStatusUpdated::dispatch($message->fresh());
    }

    /**
     * @throws GeneralException
     */
    public function authorizeReply(ChatConversation $conversation, User $user): void
    {
        if ($user->isAppAdmin()) {
            return;
        }

        if ($conversation->assigned_agent_id !== $user->id) {
            throw new GeneralException('You are not assigned to this conversation', 403);
        }
    }

    public function isWithinSessionWindow(ChatConversation $conversation): bool
    {
        return $conversation->last_customer_message_at
            && $conversation->last_customer_message_at->diffInHours(now()) < 24;
    }

    /**
     * @throws GeneralException
     */
    public function sendMessage(ChatConversation $conversation, User $agent, array $data): ChatMessage
    {
        $this->authorizeReply($conversation, $agent);

        $type = $data['type'] ?? 'text';

        if ($type !== 'template' && !$this->isWithinSessionWindow($conversation)) {
            throw new GeneralException(
                $conversation->channel === 'whatsapp'
                    ? 'The 24-hour customer service window is closed. Send a template message to reopen this conversation.'
                    : 'The 24-hour messaging window is closed. The customer needs to message you again before you can reply.',
                422
            );
        }

        $message = ChatMessage::query()->create([
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'type' => $type,
            'body' => $data['body'] ?? null,
            'template_name' => $data['template_name'] ?? null,
            'media_url' => $data['media_url'] ?? null,
            'media_mime_type' => $data['media_mime_type'] ?? null,
            'status' => 'queued',
            'sender_user_id' => $agent->id,
            'raw_payload' => $type === 'template' ? [
                'language_code' => $data['language_code'] ?? 'en_US',
                'body_params' => $data['body_params'] ?? [],
            ] : null,
        ]);

        $conversation->update([
            'last_message_at' => now(),
            'unread_count' => 0,
        ]);

        // Named queue (not 'default') so a dedicated Supervisor worker can
        // target outbound chat sends specifically, same convention as the
        // report-generation jobs' 'report-generate' queue.
        match ($conversation->channel) {
            'messenger' => SendMessengerMessageJob::dispatch($message->id)->onQueue('meta-message'),
            'instagram' => SendInstagramMessageJob::dispatch($message->id)->onQueue('meta-message'),
            default => SendWhatsAppMessageJob::dispatch($message->id)->onQueue('meta-message'),
        };

        return $message;
    }

    /**
     * @throws GeneralException
     */
    public function claim(ChatConversation $conversation, User $agent): ChatConversation
    {
        if ($conversation->assigned_agent_id !== null && $conversation->assigned_agent_id !== $agent->id && !$agent->isAppAdmin()) {
            throw new GeneralException('This conversation is already assigned to another agent', 409);
        }

        $conversation->update(['assigned_agent_id' => $agent->id]);

        $this->logAssignment($conversation, $agent->id, 'claimed', $agent->id);

        ChatConversationAssigned::dispatch($conversation->fresh());

        return $conversation;
    }

    /**
     * @throws GeneralException
     */
    public function assign(ChatConversation $conversation, int $agentId, User $actor): ChatConversation
    {
        if (!$actor->isAppAdmin()) {
            throw new GeneralException('Only admins can assign conversations', 403);
        }

        $conversation->update(['assigned_agent_id' => $agentId]);

        $this->logAssignment($conversation, $agentId, 'assigned', $actor->id);

        ChatConversationAssigned::dispatch($conversation->fresh());

        return $conversation;
    }

    /**
     * @throws GeneralException
     */
    public function reassign(ChatConversation $conversation, int $agentId, User $actor): ChatConversation
    {
        if ($actor->id !== $conversation->assigned_agent_id && !$actor->isAppAdmin()) {
            throw new GeneralException('Only the assigned agent or an admin can reassign this conversation', 403);
        }

        $conversation->update(['assigned_agent_id' => $agentId]);

        $this->logAssignment($conversation, $agentId, 'reassigned', $actor->id);

        ChatConversationAssigned::dispatch($conversation->fresh());

        return $conversation;
    }

    /**
     * @throws GeneralException
     */
    public function close(ChatConversation $conversation, User $actor): ChatConversation
    {
        $this->authorizeReply($conversation, $actor);

        $conversation->update(['status' => 'closed']);

        $this->logAssignment($conversation, $conversation->assigned_agent_id, 'closed', $actor->id);

        return $conversation;
    }

    /**
     * @throws GeneralException
     */
    public function reopen(ChatConversation $conversation, User $actor): ChatConversation
    {
        $conversation->update(['status' => 'open']);

        $this->logAssignment($conversation, $conversation->assigned_agent_id, 'reopened', $actor->id);

        return $conversation;
    }

    /**
     * @throws GeneralException
     */
    public function assignTeam(ChatConversation $conversation, int $teamId, User $actor): ChatConversation
    {
        if (!$actor->isAppAdmin()) {
            throw new GeneralException('Only admins can assign a team to this conversation', 403);
        }

        $conversation->update(['assigned_team_id' => $teamId]);

        ChatConversationAssigned::dispatch($conversation->fresh());

        return $conversation;
    }

    /**
     * @throws GeneralException
     */
    public function setPriority(ChatConversation $conversation, ?string $priority, User $actor): ChatConversation
    {
        $this->authorizeReply($conversation, $actor);

        $conversation->update(['priority' => $priority]);

        return $conversation;
    }

    /**
     * @throws GeneralException
     */
    public function syncLabels(ChatConversation $conversation, array $labelIds, User $actor): ChatConversation
    {
        $this->authorizeReply($conversation, $actor);

        $conversation->labels()->sync($labelIds);

        return $conversation;
    }

    private function logAssignment(ChatConversation $conversation, ?int $agentId, string $action, ?int $performedBy): void
    {
        AssignmentLog::query()->create([
            'conversation_id' => $conversation->id,
            'agent_id' => $agentId,
            'action' => $action,
            'performed_by' => $performedBy,
        ]);
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/[^0-9]/', '', $phone);
    }
}
