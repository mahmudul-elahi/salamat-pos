<?php

namespace App\Services\WhatsApp;

use App\Exceptions\GeneralException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppCloudApiService
{
    private function baseUrl(): string
    {
        return rtrim(config('services.whatsapp.base_url'), '/').'/'.config('services.whatsapp.graph_api_version');
    }

    private function phoneNumberId(): string
    {
        return config('services.whatsapp.phone_number_id');
    }

    /**
     * @throws GeneralException
     */
    private function request(string $method, string $uri, array $payload = []): array
    {
        $response = Http::timeout(20)
            ->acceptJson()
            ->withToken(config('services.whatsapp.access_token'))
            ->{$method}($this->baseUrl().$uri, $payload);

        if (! $response->successful()) {
            $message = $response->json('error.message') ?? 'WhatsApp Cloud API request failed';

            Log::error('WhatsApp Cloud API error', [
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
    public function sendTextMessage(string $to, string $body): array
    {
        return $this->request('post', "/{$this->phoneNumberId()}/messages", [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'text',
            'text' => ['preview_url' => false, 'body' => $body],
        ]);
    }

    /**
     * @throws GeneralException
     */
    public function sendTemplateMessage(string $to, string $templateName, string $languageCode, array $bodyParams = []): array
    {
        $components = [];

        if ($bodyParams !== []) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(fn ($param) => ['type' => 'text', 'text' => (string) $param], $bodyParams),
            ];
        }

        return $this->request('post', "/{$this->phoneNumberId()}/messages", [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $languageCode],
                ...($components !== [] ? ['components' => $components] : []),
            ],
        ]);
    }

    /**
     * @throws GeneralException
     */
    public function sendMediaMessage(string $to, string $type, string $mediaUrl, ?string $caption = null): array
    {
        $media = ['link' => $mediaUrl];

        if ($caption && $type !== 'audio') {
            $media['caption'] = $caption;
        }

        return $this->request('post', "/{$this->phoneNumberId()}/messages", [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => $type,
            $type => $media,
        ]);
    }

    /**
     * @throws GeneralException
     */
    public function markMessageAsRead(string $waMessageId): array
    {
        return $this->request('post', "/{$this->phoneNumberId()}/messages", [
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $waMessageId,
        ]);
    }

    /**
     * @throws GeneralException
     */
    public function getMediaUrl(string $mediaId): array
    {
        return $this->request('get', "/{$mediaId}");
    }

    /**
     * @throws GeneralException
     */
    public function downloadMedia(string $mediaUrl): string
    {
        $response = Http::timeout(30)
            ->withToken(config('services.whatsapp.access_token'))
            ->get($mediaUrl);

        if (! $response->successful()) {
            throw new GeneralException('Failed to download WhatsApp media', 422);
        }

        return $response->body();
    }

    /**
     * @throws GeneralException
     */
    public function fetchTemplates(): array
    {
        $businessAccountId = config('services.whatsapp.business_account_id');

        return $this->request('get', "/{$businessAccountId}/message_templates")['data'] ?? [];
    }
}
