<?php

namespace App\Enums;

enum CustomerTypeEnum: int
{
    case Dealer = 1;
    case Retailer = 2;
    case B2B = 3;
    case Consumer = 4;
    case Franchise = 5;
}
