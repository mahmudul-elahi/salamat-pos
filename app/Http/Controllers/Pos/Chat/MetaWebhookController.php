<?php

namespace App\Http\Controllers\Pos\Chat;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Services\Chat\ChatService;
use App\Services\Instagram\InstagramApiService;
use App\Services\Messenger\MessengerApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * A Meta App only ever has ONE registered webhook Callback URL - it is not
 * per-product. WhatsApp, Messenger, and Instagram events for this app all
 * arrive here (routes/api.php: "webhooks/meta"), distinguished only by the
 * top-level "object" field in the payload ("whatsapp_business_account" |
 * "page" | "instagram"). This is the single URL that should be registered
 * in the Meta App dashboard, under whichever product's "Configure Webhooks"
 * screen was used first - the other products' "Configure webhooks" step
 * will show as already-done because it's the same underlying app-level
 * setting.
 */
class MetaWebhookController extends Controller
{
    /**
     * Meta's webhook verification handshake, called once when the callback
     * URL is registered. The verify token is app-level (shared across
     * products), so any one of the per-service config values works.
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $validTokens = array_filter([
            config('services.whatsapp.webhook_verify_token'),
            config('services.messenger.webhook_verify_token'),
            config('services.instagram.webhook_verify_token'),
        ]);

        foreach ($validTokens as $validToken) {
            if ($mode === 'subscribe' && hash_equals((string) $validToken, (string) $token)) {
                return response($challenge, 200);
            }
        }

        return response('Forbidden', 403);
    }

    /**
     * Receives inbound events for every channel. Always acks quickly (Meta
     * retries aggressively on non-2xx / slow responses).
     */
    public function handle(
        Request $request,
        ChatService $service,
        MessengerApiService $messengerClient,
        InstagramApiService $instagramClient,
    ) {
        if (! $this->hasValidSignature($request)) {
            Log::warning('Meta webhook: invalid signature');

            return response()->json(['error' => 'Invalid signature'], 403);
        }

        match ($request->input('object')) {
            'whatsapp_business_account' => $this->handleWhatsApp($request, $service),
            'page' => $this->handleMessenger($request, $service, $messengerClient),
            'instagram' => $this->handleInstagram($request, $service, $instagramClient),
            default => Log::info('Meta webhook: unhandled object type', ['object' => $request->input('object')]),
        };

        return response()->json(['status' => 'received']);
    }

    private function handleWhatsApp(Request $request, ChatService $service): void
    {
        foreach ($request->input('entry', []) as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];

                foreach ($value['messages'] ?? [] as $messagePayload) {
                    $waPhone = $messagePayload['from'] ?? null;

                    if (! $waPhone) {
                        continue;
                    }

                    $profileName = collect($value['contacts'] ?? [])
                        ->firstWhere('wa_id', $waPhone)['profile']['name'] ?? null;

                    $conversation = $service->findOrCreateConversation('whatsapp', $waPhone, $profileName);
                    $service->storeInboundMessage($conversation, $messagePayload);
                }

                foreach ($value['statuses'] ?? [] as $statusPayload) {
                    $service->updateMessageStatus(
                        $statusPayload['id'] ?? '',
                        $statusPayload['status'] ?? 'sent',
                        $statusPayload['errors'] ?? null,
                    );
                }
            }
        }
    }

    private function handleMessenger(Request $request, ChatService $service, MessengerApiService $client): void
    {
        foreach ($request->input('entry', []) as $entry) {
            foreach ($entry['messaging'] ?? [] as $messagingEvent) {
                $senderId = $messagingEvent['sender']['id'] ?? null;
                $messagePayload = $messagingEvent['message'] ?? null;

                if (! $senderId || ! $messagePayload || ($messagePayload['is_echo'] ?? false)) {
                    continue;
                }

                $profile = $this->fetchMessengerProfile($client, $senderId);
                $conversation = $service->findOrCreateConversation('messenger', $senderId, $profile['name'], $profile['profile_pic']);

                $service->storeInboundMessage($conversation, $this->buildMetaMessagingPayload($messagePayload));
            }
        }
    }

    /**
     * Messenger and Instagram share the same Send/Receive API message shape.
     * A plain text message (including one that's just an emoji) has a
     * "text" field; anything else - a photo, a sticker (Messenger's big
     * thumbs-up/hand sticker included), a video, a voice clip, a file -
     * arrives as an "attachments" entry instead, which the old code never
     * looked at, so those messages saved with no body and no media and
     * effectively vanished from the inbox.
     */
    private function buildMetaMessagingPayload(array $messagePayload): array
    {
        $attachment = $messagePayload['attachments'][0] ?? null;

        if ($attachment) {
            // Matches MessengerApiService::sendAttachment()'s own mapping:
            // this app's "document" is Messenger/Instagram's "file".
            $type = $attachment['type'] === 'file' ? 'document' : ($attachment['type'] ?? 'document');

            return [
                'id' => $messagePayload['mid'] ?? null,
                'type' => $type,
                'media_url' => $attachment['payload']['url'] ?? null,
            ];
        }

        return [
            'id' => $messagePayload['mid'] ?? null,
            'type' => 'text',
            'text' => ['body' => $messagePayload['text'] ?? null],
        ];
    }

    private function handleInstagram(Request $request, ChatService $service, InstagramApiService $client): void
    {
        foreach ($request->input('entry', []) as $entry) {
            foreach ($entry['messaging'] ?? [] as $messagingEvent) {
                $senderId = $messagingEvent['sender']['id'] ?? null;
                $messagePayload = $messagingEvent['message'] ?? null;

                if (! $senderId || ! $messagePayload || ($messagePayload['is_echo'] ?? false)) {
                    continue;
                }

                $profile = $this->fetchInstagramProfile($client, $senderId);
                $conversation = $service->findOrCreateConversation('instagram', $senderId, $profile['name'], $profile['profile_pic']);

                $service->storeInboundMessage($conversation, $this->buildMetaMessagingPayload($messagePayload));
            }
        }
    }

    private function fetchMessengerProfile(MessengerApiService $client, string $senderId): array
    {
        try {
            $profile = $client->getUserProfile($senderId);

            return [
                'name' => trim(($profile['first_name'] ?? '').' '.($profile['last_name'] ?? '')) ?: null,
                'profile_pic' => $profile['profile_pic'] ?? null,
            ];
        } catch (GeneralException) {
            // Meta has locked first_name/last_name/profile_pic behind App
            // Review for most apps since 2022 (error_subcode 33 - "missing
            // permissions") - this fails for every PSID, not just this one,
            // so fall back to a readable placeholder instead of leaving the
            // conversation with a blank name forever.
            return ['name' => 'Messenger User #'.substr($senderId, -6), 'profile_pic' => null];
        }
    }

    private function fetchInstagramProfile(InstagramApiService $client, string $senderId): array
    {
        try {
            $profile = $client->getUserProfile($senderId);

            return [
                'name' => $profile['name'] ?? $profile['username'] ?? null,
                'profile_pic' => $profile['profile_pic'] ?? null,
            ];
        } catch (GeneralException) {
            // Same Meta profile-API lockdown as Messenger (see
            // fetchMessengerProfile) - fall back to a readable placeholder.
            return ['name' => 'Instagram User #'.substr($senderId, -6), 'profile_pic' => null];
        }
    }

    private function hasValidSignature(Request $request): bool
    {
        $signatureHeader = $request->header('X-Hub-Signature-256');

        if (! $signatureHeader) {
            return false;
        }

        // WhatsApp/Messenger and Instagram aren't guaranteed to be the same
        // Meta App (they aren't in this project's setup), so each one signs
        // with its own App Secret. Try every configured secret rather than
        // assuming one is shared, same as verify() does for the token.
        $candidateSecrets = array_unique(array_filter([
            config('services.whatsapp.app_secret'),
            config('services.messenger.app_secret'),
            config('services.instagram.app_secret'),
        ]));

        foreach ($candidateSecrets as $appSecret) {
            $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $appSecret);

            if (hash_equals($expected, (string) $signatureHeader)) {
                return true;
            }
        }

        return false;
    }
}
