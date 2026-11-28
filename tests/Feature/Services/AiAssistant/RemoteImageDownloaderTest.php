<?php

namespace Tests\Feature\Services\AiAssistant;

use App\Services\AiAssistant\RemoteImageDownloader;
use App\Services\AiAssistant\RemoteImageException;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RemoteImageDownloaderTest extends TestCase
{
    /** A 1×1 transparent PNG. */
    private const string PNG = "\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR\x00\x00\x00\x01\x00\x00\x00\x01\x08\x06\x00\x00\x00\x1f\x15\xc4\x89\x00\x00\x00\rIDATx\x9cc\xf8\x0f\x00\x00\x01\x01\x00\x05\x18\xd8N\x00\x00\x00\x00IEND\xaeB`\x82";

    private const string PUBLIC_IMAGE_URL = 'https://93.184.216.34/phone.png';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_it_downloads_an_image_from_a_public_address(): void
    {
        Http::fake([self::PUBLIC_IMAGE_URL => Http::response(self::PNG, 200, ['Content-Type' => 'image/png'])]);

        $image = (new RemoteImageDownloader)->download(self::PUBLIC_IMAGE_URL);

        $this->assertSame(self::PNG, $image['contents']);
        $this->assertSame('png', $image['extension']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function blockedAddresses(): array
    {
        return [
            'loopback' => ['http://127.0.0.1/image.png'],
            'private network' => ['http://10.0.0.5/image.png'],
            'cloud metadata' => ['http://169.254.169.254/latest/meta-data'],
            'non-standard port' => ['https://93.184.216.34:8080/image.png'],
            'non-web scheme' => ['file:///etc/passwd'],
        ];
    }

    #[DataProvider('blockedAddresses')]
    public function test_it_refuses_addresses_outside_the_public_web(string $url): void
    {
        Http::fake();

        $this->expectException(RemoteImageException::class);

        try {
            (new RemoteImageDownloader)->download($url);
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_it_refuses_a_redirect_to_a_private_address(): void
    {
        Http::fake([self::PUBLIC_IMAGE_URL => Http::response('', 302, ['Location' => 'http://127.0.0.1/admin.png'])]);

        $this->expectException(RemoteImageException::class);
        $this->expectExceptionMessage('public internet addresses');

        (new RemoteImageDownloader)->download(self::PUBLIC_IMAGE_URL);
    }

    public function test_it_refuses_a_file_that_is_not_an_image_whatever_its_content_type(): void
    {
        Http::fake([self::PUBLIC_IMAGE_URL => Http::response('<html>not an image</html>', 200, ['Content-Type' => 'image/png'])]);

        $this->expectException(RemoteImageException::class);

        (new RemoteImageDownloader)->download(self::PUBLIC_IMAGE_URL);
    }
}
