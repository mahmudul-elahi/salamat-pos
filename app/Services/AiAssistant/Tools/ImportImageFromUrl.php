<?php

namespace App\Services\AiAssistant\Tools;

use App\Models\Ecommerce\MediaFolder\MediaFolder;
use App\Services\AiAssistant\RemoteImageDownloader;
use App\Services\AiAssistant\RemoteImageException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Stringable;

class ImportImageFromUrl extends AssistantTool
{
    public function __construct(protected RemoteImageDownloader $downloader) {}

    public function description(): Stringable|string
    {
        return 'Download an image from a web address into the media library and return its media library URL. Use the returned URL for product, category and brand images; never use the original web address directly.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'url' => $schema->string()->required()->description('The direct address of the image file.'),
            'name' => $schema->string()->nullable()->description('A descriptive name for the image, such as "iPhone 17 Pro Max Black front".'),
        ];
    }

    protected function permission(): ?string
    {
        return 'update_medias';
    }

    protected function rules(): array
    {
        return [
            'url' => ['required', 'url:http,https', 'max:2048'],
            'name' => ['nullable', 'string', 'max:120'],
        ];
    }

    protected function run(array $arguments): array
    {
        try {
            $image = $this->downloader->download($arguments['url']);
        } catch (RemoteImageException $exception) {
            return ['error' => 'download_failed', 'message' => $exception->getMessage()];
        }

        $name = filled($arguments['name'] ?? null)
            ? $arguments['name']
            : pathinfo((string) parse_url($arguments['url'], PHP_URL_PATH), PATHINFO_FILENAME);
        $name = Str::limit($name ?: 'image', 120, '');

        $media = MediaFolder::query()
            ->firstOrCreate(['id' => 1], ['name' => 'root', 'parent_id' => null])
            ->addMediaFromString($image['contents'])
            ->usingName($name)
            ->usingFileName((Str::slug($name) ?: 'image').'.'.$image['extension'])
            ->toMediaCollection('global_media');

        return [
            'imported' => true,
            'url' => $media->getFullUrl(),
            'media_id' => $media->id,
        ];
    }
}
