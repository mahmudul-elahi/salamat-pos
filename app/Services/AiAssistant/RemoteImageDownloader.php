<?php

namespace App\Services\AiAssistant;

use finfo;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Downloads an image from a public web address on behalf of the assistant.
 *
 * The address comes from the model, so it is treated as untrusted: only http(s) on the default
 * ports, only hosts that resolve to public IP addresses (the checked address is pinned for the
 * request, so it cannot be swapped by a second DNS lookup), each redirect checked the same way,
 * and only real image files up to MAX_BYTES.
 */
class RemoteImageDownloader
{
    public const int MAX_BYTES = 10 * 1024 * 1024;

    private const int MAX_REDIRECTS = 3;

    private const array EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'image/avif' => 'avif',
    ];

    /**
     * @return array{contents: string, extension: string}
     *
     * @throws RemoteImageException
     */
    public function download(string $url): array
    {
        for ($redirects = 0; $redirects <= self::MAX_REDIRECTS; $redirects++) {
            [$host, $port, $address] = $this->resolvePublicAddress($url);

            try {
                $response = Http::connectTimeout(5)
                    ->timeout(20)
                    ->withoutRedirecting()
                    ->accept('image/*')
                    ->withOptions([
                        'curl' => [CURLOPT_RESOLVE => ["{$host}:{$port}:{$address}"]],
                        'progress' => function (int $expectedBytes, int $downloadedBytes): void {
                            if ($expectedBytes > self::MAX_BYTES || $downloadedBytes > self::MAX_BYTES) {
                                throw new RemoteImageException('The image is larger than 10 MB.');
                            }
                        },
                    ])
                    ->get($url);
            } catch (RemoteImageException $exception) {
                throw $exception;
            } catch (Throwable $exception) {
                throw new RemoteImageException(
                    $this->findRemoteImageException($exception)?->getMessage() ?? 'The image could not be downloaded.',
                    previous: $exception,
                );
            }

            if ($response->redirect()) {
                $url = (string) UriResolver::resolve(new Uri($url), new Uri($response->header('Location')));

                continue;
            }

            if (! $response->successful()) {
                throw new RemoteImageException("The image address answered with HTTP {$response->status()}.");
            }

            $contents = $response->body();

            if (strlen($contents) > self::MAX_BYTES) {
                throw new RemoteImageException('The image is larger than 10 MB.');
            }

            $mimeType = (new finfo(FILEINFO_MIME_TYPE))->buffer($contents);

            if (! isset(self::EXTENSIONS[$mimeType])) {
                throw new RemoteImageException('The address did not return a JPEG, PNG, WebP, GIF or AVIF image.');
            }

            return ['contents' => $contents, 'extension' => self::EXTENSIONS[$mimeType]];
        }

        throw new RemoteImageException('The image address redirected too many times.');
    }

    /**
     * @return array{0: string, 1: int, 2: string}
     *
     * @throws RemoteImageException
     */
    protected function resolvePublicAddress(string $url): array
    {
        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower($parts['host'] ?? '');

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            throw new RemoteImageException('Only http and https image addresses are allowed.');
        }

        $defaultPort = $scheme === 'https' ? 443 : 80;
        $port = $parts['port'] ?? $defaultPort;

        if ($port !== $defaultPort) {
            throw new RemoteImageException('Image addresses must use the standard web port.');
        }

        $addresses = filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
            ? [$host]
            : $this->lookUp($host);

        if ($addresses === []) {
            throw new RemoteImageException("The host {$host} could not be found.");
        }

        foreach ($addresses as $address) {
            if (! filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new RemoteImageException('Images can only be downloaded from public internet addresses.');
            }
        }

        return [$host, $port, $addresses[0]];
    }

    /**
     * Find a size-limit error raised inside the transfer, which the HTTP client wraps in its own exception.
     */
    private function findRemoteImageException(Throwable $exception): ?RemoteImageException
    {
        for ($current = $exception; $current !== null; $current = $current->getPrevious()) {
            if ($current instanceof RemoteImageException) {
                return $current;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    protected function lookUp(string $host): array
    {
        return gethostbynamel($host) ?: [];
    }
}
