<?php

namespace App\Enums;

enum OnlinePaymentStatusEnum: int
{
    case Pending = 0;
    case Initiated = 1;
    case Processing = 2;
    case Successful = 3;
    case Failed = 4;
    case Canceled = 5;
    case Unsuccessful = 6;
    case PartiallyPaid = 7;
    case Refunded = 8;

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',      // yellow
            self::Initiated => 'info',       // cyan/blue
            self::Processing => 'primary',   // blue
            self::Successful => 'success',    // green
            self::Failed => 'danger',        // red
            self::Canceled => 'secondary',   // gray
            self::Unsuccessful => 'danger',  // red
            self::PartiallyPaid => 'primary', // yellow
            self::Refunded => 'danger',        // red
        };
    }
}
