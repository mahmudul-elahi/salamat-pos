<?php

declare(strict_types=1);

namespace App\Models\Pos\Country;

use App\Models\Common\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Country extends BaseModel
{
    use HasFactory;

    protected $fillable = ['code', 'name'];
}
