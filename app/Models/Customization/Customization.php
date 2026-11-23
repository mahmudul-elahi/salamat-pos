<?php

declare(strict_types=1);

namespace App\Models\Customization;

use Illuminate\Database\Eloquent\Model;

class Customization extends Model
{
    #[\Override]
    protected $fillable = ['name', 'value', 'type'];
}
