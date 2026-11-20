<?php

namespace App\Jobs\WhatsApp;

use App\Events\Chat\ChatMessageStatusUpdated;
use App\Exceptions\GeneralException;
use App\Models\Pos\Chat\ChatMessage;
use App\Services\WhatsApp\WhatsAppCloudApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(protected int $messageId)
    {
    }

    public function handle(WhatsAppCloudApiService $client): void
    {
        $message = ChatMessage::query()->find($this->messageId);

        if (!$message) {
            return;
        }

        $conversation = $message->conversation;

        try {
            $response = match (true) {
                $message->type === 'template' => $client->sendTemplateMessage(
                    $conversation->external_id,
                    $message->template_name,
                    $message->raw_payload['language_code'] ?? 'en_US',
                    $message->raw_payload['body_params'] ?? [],
                ),
                in_array($message->type, ['image', 'video', 'audio', 'document'], true) => $client->sendMediaMessage(
                    $conversation->external_id,
                    $message->type,
                    $message->media_url,
                    $message->body,
                ),
                default => $client->sendTextMessage($conversation->external_id, $message->body ?? ''),
            };

            $message->update([
                'external_message_id' => $response['messages'][0]['id'] ?? null,
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (GeneralException $exception) {
            $message->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            Log::error('Failed to send WhatsApp message', [
                'message_id' => $message->id,
                'error' => $exception->getMessage(),
            ]);
        }

        ChatMessageStatusUpdated::dispatch($message->fresh());
    }
}
