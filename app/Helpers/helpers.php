<?php

use Carbon\Carbon;

if (! function_exists('user_timezone')) {
    /**
     * Get the authenticated user's timezone (fallback: Asia/Dhaka).
     */
    function user_timezone(): string
    {
        return auth()->user()?->timezone ?? 'Asia/Dhaka';
    }
}

if (! function_exists('to_user_timezone_datetime')) {
    /**
     * Convert a Carbon/datetime to the authenticated user's timezone for display.
     * Stored UTC value is untouched — only the representation changes.
     *
     * @param  Carbon|DateTimeInterface|string|null  $date
     */
    function to_user_timezone_datetime(mixed $date, string $format = 'd M Y H:i:s'): ?string
    {
        if (! $date) {
            return null;
        }

        $carbon = $date instanceof Carbon
            ? $date
            : Carbon::parse($date, 'UTC');

        return $carbon->setTimezone(user_timezone())->format($format);
    }
}

if (! function_exists('to_user_timezone_date')) {
    /**
     * Convert a Carbon/datetime to the authenticated user's timezone for display.
     * Stored UTC value is untouched — only the representation changes.
     *
     * @param  Carbon|DateTimeInterface|string|null  $date
     */
    function to_user_timezone_date(mixed $date, string $format = 'd M Y'): ?string
    {
        if (! $date) {
            return null;
        }

        $carbon = $date instanceof Carbon
            ? $date
            : Carbon::parse($date, 'UTC');

        return $carbon->setTimezone(user_timezone())->format($format);
    }
}

if (! function_exists('to_cdn_url')) {
    function to_cdn_url(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $s3Base = 'https://'.config('filesystems.disks.s3.bucket').'.s3.'.config('filesystems.disks.s3.region').'.amazonaws.com';
        $cdnBase = rtrim(config('filesystems.disks.s3.url', ''), '/');

        if (! $cdnBase || $cdnBase === $s3Base) {
            return $url;
        }

        return str_replace($s3Base, $cdnBase, $url);
    }
}
