<?php

namespace App\Helpers\Traits;

use Illuminate\Support\Facades\Storage;

trait S3ToLocalPathTrait
{
    private function toCdnUrl(?string $url): ?string
    {
        if (!$url) return null;

        $bucket = config('filesystems.disks.s3.bucket', '');
        $region = config('filesystems.disks.s3.region', '');
        $cdnBase = rtrim(config('filesystems.disks.s3.url', ''), '/');

        if (!$cdnBase) return $url;

        $s3Base = "https://{$bucket}.s3.{$region}.amazonaws.com";

        return str_replace($s3Base, $cdnBase, $url);
    }

    private function s3ToLocalPath(?string $url): ?string
    {
        if (!$url) return null;

        $bucket = config('filesystems.disks.s3.bucket', '');
        $region = config('filesystems.disks.s3.region', '');
        $s3Base = "https://{$bucket}.s3.{$region}.amazonaws.com/";
        $cdnBase = rtrim(config('filesystems.disks.s3.url', ''), '/') . '/';

        if (str_starts_with($url, $s3Base)) {
            $path = substr($url, strlen($s3Base));
        } elseif ($cdnBase !== '/' && str_starts_with($url, $cdnBase)) {
            $path = substr($url, strlen($cdnBase));
        } else {
            return $url;
        }

        try {
            $ext     = strtolower(pathinfo($path, PATHINFO_EXTENSION)) ?: 'jpg';
            $tmpDir  = storage_path('app/pdf-tmp');
            $tmpPath = $tmpDir . '/' . md5($path) . '.' . $ext;

            if (!file_exists($tmpPath)) {
                if (!is_dir($tmpDir)) {
                    mkdir($tmpDir, 0755, true);
                }
                file_put_contents($tmpPath, Storage::disk('s3')->get($path));
            }

            return $tmpPath;
        } catch (\Throwable) {
            return null;
        }
    }
}
