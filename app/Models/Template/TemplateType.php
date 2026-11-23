<?php

namespace App\Models\Template;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TemplateType extends Model
{
    #[\Override]
    protected $fillable = [
        'name', 'display_name', 'group_name',
    ];

    public function templates(): HasMany
    {
        return $this->hasMany(Template::class);
    }
}
