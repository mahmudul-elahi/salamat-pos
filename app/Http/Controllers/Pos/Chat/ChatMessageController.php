<?php

namespace App\Http\Controllers\Pos\Chat;

use App\Exceptions\GeneralException;
use App\Http\Controllers\Controller;
use App\Models\Pos\Chat\ChatConversation;
use App\Services\Chat\ChatService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ChatMessageController extends Controller
{
    private const array MAX_SIZES_KB = [
        'image' => 5 * 1024,
        'video' => 16 * 1024,
        'audio' => 16 * 1024,
        'document' => 100 * 1024,
    ];

    public function __construct(protected ChatService $service) {}

    /**
     * @throws GeneralException
     * @throws Throwable
     */
    public function store(Request $request, ChatConversation $chat_conversation)
    {
        $data = $request->validate([
            'type' => ['nullable', 'in:text,template,image,video,audio,document'],
            'body' => ['nullable', 'string'],
            'template_name' => ['required_if:type,template', 'nullable', 'string'],
            'language_code' => ['nullable', 'string'],
            'body_params' => ['nullable', 'array'],
            'attachment' => ['nullable', 'file', 'max:'.self::MAX_SIZES_KB['document']],
        ]);

        if ($request->hasFile('attachment')) {
            $data = [...$data, ...$this->storeAttachment($chat_conversation, $request->file('attachment'))];
        }

        $data['type'] ??= 'text';

        if ($data['type'] === 'text' && ! $data['body']) {
            return errorResponse('A message body is required', null, 422);
        }

        try {
            DB::beginTransaction();

            $message = $this->service->sendMessage($chat_conversation, $request->user(), $data);

            DB::commit();

            return successResponse('Message queued for delivery', $message);
        } catch (GeneralException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            throw new GeneralException($e->getMessage(), 500, $e);
        }
    }

    /**
     * @throws GeneralException
     */
    private function storeAttachment(ChatConversation $conversation, UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: Str::afterLast($file->getMimeType(), '/'));
        $type = $this->resolveTypeFromMime($file->getMimeType(), $extension);

        $maxKb = self::MAX_SIZES_KB[$type];
        if ($file->getSize() > $maxKb * 1024) {
            throw new GeneralException("This {$type} exceeds the ".($maxKb / 1024).'MB limit', 422);
        }

        $filename = Str::uuid().'.'.$extension;
        $path = "chat/attachments/{$conversation->id}/{$filename}";

        Storage::disk(config('filesystems.default'))->put($path, file_get_contents($file->getRealPath()));

        return [
            'type' => $type,
            'media_url' => Storage::disk(config('filesystems.default'))->url($path),
            'media_mime_type' => $file->getMimeType(),
        ];
    }

    private function resolveTypeFromMime(?string $mimeType, string $extension = ''): string
    {
        // Audio-only files in an ISO-BMFF/MP4 container (m4a, and some
        // aac/3gp recordings) share the exact same container format as MP4
        // video, so fileinfo/libmagic content-sniffing frequently guesses
        // "video/mp4" for them - there's no reliable way to tell them apart
        // from magic bytes alone. The extension is the only signal that
        // disambiguates this, so it takes priority over the sniffed MIME type.
        if (in_array($extension, ['m4a', 'aac', 'mp3', 'wav', 'oga', 'weba', '3ga'], true)) {
            return 'audio';
        }

        return match (true) {
            str_starts_with((string) $mimeType, 'image/') => 'image',
            str_starts_with((string) $mimeType, 'video/') => 'video',
            str_starts_with((string) $mimeType, 'audio/') => 'audio',
            default => 'document',
        };
    }
}
