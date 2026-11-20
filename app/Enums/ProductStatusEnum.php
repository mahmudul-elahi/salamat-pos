<?php

namespace App\Enums;

enum ProductStatusEnum: int
{
    case Inactive = 0;

    case Active = 1;

    case Pending = 2;
    case Rejected = 3;
    case Approved = 4;
    case Slider = 5;
    case Arrival = 6;
    case Comming = 7;
    case Featured = 8;

    public function color(): string
    {
        return match ($this) {
            self::Inactive => 'danger',
            self::Active => 'success',
            self::Pending => 'warning',
            self::Rejected => 'danger',
            self::Approved => 'success',
            self::Slider => 'info',
            self::Arrival => 'warning',
            self::Comming => 'light',
            self::Featured => 'info',
        };
    }
}
