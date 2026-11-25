<?php

declare(strict_types=1);

namespace App\Models\Pos\Country;

use App\Models\Common\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property string $name
 */
class Country extends BaseModel
{
    use HasFactory;

    #[\Override]
    protected $fillable = ['code', 'name'];
}
