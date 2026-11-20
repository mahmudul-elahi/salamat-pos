<?php

namespace App\Jobs\WhatsApp;

use App\Exceptions\GeneralException;
use App\Models\Pos\Chat\ChatMessage;
use App\Services\WhatsApp\WhatsAppCloudApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FetchWhatsAppInboundMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(protected int $messageId) {}

    public function handle(WhatsAppCloudApiService $client): void
    {
        $message = ChatMessage::query()->find($this->messageId);

        if (! $message || ! $message->media_id) {
            return;
        }

        try {
            $mediaInfo = $client->getMediaUrl($message->media_id);
            $contents = $client->downloadMedia($mediaInfo['url']);

            $extension = Str::afterLast($mediaInfo['mime_type'] ?? '', '/') ?: 'bin';
            $path = "whatsapp/media/{$message->conversation_id}/{$message->media_id}.{$extension}";

            Storage::disk(config('filesystems.default'))->put($path, $contents);

            $message->update([
                'media_url' => Storage::disk(config('filesystems.default'))->url($path),
                'media_mime_type' => $mediaInfo['mime_type'] ?? null,
            ]);
        } catch (GeneralException $exception) {
            Log::error('Failed to fetch WhatsApp inbound media', [
                'message_id' => $message->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
