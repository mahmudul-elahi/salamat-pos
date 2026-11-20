<?php

namespace App\Jobs\Messenger;

use App\Events\Chat\ChatMessageStatusUpdated;
use App\Exceptions\GeneralException;
use App\Models\Pos\Chat\ChatMessage;
use App\Services\Messenger\MessengerApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendMessengerMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(protected int $messageId) {}

    public function handle(MessengerApiService $client): void
    {
        $message = ChatMessage::query()->find($this->messageId);

        if (! $message) {
            return;
        }

        $conversation = $message->conversation;

        try {
            $response = in_array($message->type, ['image', 'video', 'audio', 'document'], true)
                ? $client->sendAttachment($conversation->external_id, $message->type, $message->media_url)
                : $client->sendTextMessage($conversation->external_id, $message->body ?? '');

            $message->update([
                'external_message_id' => $response['message_id'] ?? null,
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        } catch (GeneralException $exception) {
            $message->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            Log::error('Failed to send Messenger message', [
                'message_id' => $message->id,
                'error' => $exception->getMessage(),
            ]);
        }

        ChatMessageStatusUpdated::dispatch($message->fresh());
    }
}
