<?php

namespace App\Helpers\General;

class AddressHelper
{
    /**
     * Join a typed street address with the picked address parts (area, thana, district, postcode, country).
     *
     * Customers often type their area, district or postcode into the street field, so a picked
     * part is skipped when the typed street already mentions it as a whole word. Picked parts are
     * never compared with each other: an area named "Gazipur Sadar" must not hide the district "Gazipur".
     *
     * @param  array<int, string|null>  $pickedParts
     */
    public static function join(?string $street, array $pickedParts): string
    {
        $street = self::clean($street);
        $address = $street === '' ? [] : [$street];

        foreach ($pickedParts as $part) {
            $part = self::clean($part);

            if ($part === '' || self::mentions($street, $part) || self::alreadyAdded($address, $part)) {
                continue;
            }

            $address[] = $part;
        }

        return implode(', ', $address);
    }

    private static function clean(?string $part): string
    {
        return trim((string) $part, " \t\n\r\0\x0B,");
    }

    private static function mentions(string $street, string $part): bool
    {
        $pattern = '/(?<![\p{L}\p{N}])'.preg_quote($part, '/').'(?![\p{L}\p{N}])/iu';

        return preg_match($pattern, $street) === 1;
    }

    /**
     * @param  array<int, string>  $address
     */
    private static function alreadyAdded(array $address, string $part): bool
    {
        foreach ($address as $added) {
            if (mb_strtolower($added) === mb_strtolower($part)) {
                return true;
            }
        }

        return false;
    }
}
