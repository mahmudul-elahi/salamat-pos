<?php

namespace App\Helpers\General;

use App\Repositories\Settings\SettingRepository;
use Carbon\Carbon;

class SettingHelper
{
    public function numberWithCurrencySymbol($number): string
    {
        $symbol = $this->getCurrencySymbol($type);
        $formattedNumber = $this->numberFormatter($number);

        return match ($this->getCurrencyPosition($type)) {
            'prefix_with_space' => $symbol . ' ' . $formattedNumber,
            'prefix_only' => $symbol . $formattedNumber,
            'suffix_with_space' => $formattedNumber . ' ' . $symbol,
            default => $formattedNumber . $symbol,
        };
    }

    public function numberFormatter($number): string
    {
        $decimals = $this->getNumberOfDecimal();
        $thousandSep = $this->getThousandSeparator();
        $decimalSep = $this->getDecimalSeparator();

        // Format the number using PHP's number_format first (with default separators)
        $number = number_format((float)$number, $decimals, '.', '');

        // Split into whole and decimal parts
        [$whole, $fraction] = explode('.', $number);

        // Add thousand separator manually
        $wholeWithSep = preg_replace('/\B(?=(\d{3})+(?!\d))/', $thousandSep === 'space' ? ' ' : $thousandSep, $whole);

        return $wholeWithSep . $decimalSep . $fraction;
    }


    public function appDateFormat($date): string
    {
        $format = $this->getDateFormat();
        $format = ($format === 'd-m-Y') ? 'd-M-Y' : $format;

        return Carbon::parse($date)->format($format);
    }


    
}
