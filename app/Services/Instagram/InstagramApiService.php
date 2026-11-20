<?php

namespace App\Services\Instagram;

use App\Exceptions\GeneralException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramApiService
{
    private function baseUrl(): string
    {
        return rtrim(config('services.instagram.base_url'), '/').'/'.config('services.instagram.graph_api_version');
    }

    private function accountId(): string
    {
        return config('services.instagram.account_id');
    }

    /**
     * @throws GeneralException
     */
    private function request(string $method, string $uri, array $payload = []): array
    {
        $response = Http::timeout(20)
            ->acceptJson()
            ->withToken(config('services.instagram.access_token'))
            ->{$method}($this->baseUrl().$uri, $payload);

        if (! $response->successful()) {
            $message = $response->json('error.message') ?? 'Instagram API request failed';

            Log::error('Instagram API error', [
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
    public function sendTextMessage(string $igsid, string $text): array
    {
        return $this->request('post', "/{$this->accountId()}/messages", [
            'recipient' => ['id' => $igsid],
            'message' => ['text' => $text],
        ]);
    }

    /**
     * @throws GeneralException
     */
    public function sendAttachment(string $igsid, string $type, string $mediaUrl): array
    {
        $attachmentType = $type === 'document' ? 'file' : $type;

        return $this->request('post', "/{$this->accountId()}/messages", [
            'recipient' => ['id' => $igsid],
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
    public function getUserProfile(string $igsid): array
    {
        return $this->request('get', "/{$igsid}", ['fields' => 'name,username,profile_pic']);
    }
}
