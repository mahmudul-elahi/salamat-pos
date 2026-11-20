<?php

namespace App\Models\Ecommerce\PopupDialog;

use App\Helpers\Traits\RevalidatesFrontendTrait;
use Illuminate\Database\Eloquent\Model;

class PopupDialog extends Model
{
    use RevalidatesFrontendTrait;

    protected static $revalidationType = 'popup-dialog';

    protected $fillable = [
        'name',
        'image',
        'mobile_image',
        'url',
        'status',
    ];
}
