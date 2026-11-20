<?php

namespace App\Services\Messenger;

use App\Exceptions\GeneralException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MessengerApiService
{
    private function baseUrl(): string
    {
        return rtrim(config('services.messenger.base_url'), '/').'/'.config('services.messenger.graph_api_version');
    }

    /**
     * @throws GeneralException
     */
    private function request(string $method, string $uri, array $payload = []): array
    {
        $response = Http::timeout(20)
            ->acceptJson()
            ->withToken(config('services.messenger.page_access_token'))
            ->{$method}($this->baseUrl().$uri, $payload);

        if (! $response->successful()) {
            $message = $response->json('error.message') ?? 'Messenger API request failed';

            Log::error('Messenger API error', [
                'uri' => $uri,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new GeneralException($message, 422);
        }

        return $response->json();
    }

    /**
     * @throws GeneralException
     */
    public function sendTextMessage(string $psid, string $text): array
    {
        return $this->request('post', '/me/messages', [
            'recipient' => ['id' => $psid],
            'messaging_type' => 'RESPONSE',
            'message' => ['text' => $text],
        ]);
    }

    /**
     * @throws GeneralException
     */
    public function sendAttachment(string $psid, string $type, string $mediaUrl): array
    {
        // Messenger's attachment types are image|video|audio|file - our internal
        // "document" type maps to Messenger's "file".
        $attachmentType = $type === 'document' ? 'file' : $type;

        return $this->request('post', '/me/messages', [
            'recipient' => ['id' => $psid],
            'messaging_type' => 'RESPONSE',
            'message' => [
                'attachment' => [
                    'type' => $attachmentType,
                    'payload' => ['url' => $mediaUrl, 'is_reusable' => true],
                ],
            ],
        ]);
    }

    /**
     * @throws GeneralException
     */
    public function getUserProfile(string $psid): array
    {
        return $this->request('get', "/{$psid}", ['fields' => 'first_name,last_name,profile_pic']);
    }
}
