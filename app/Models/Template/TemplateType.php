<?php

namespace App\Models\Template;

use Illuminate\Database\Eloquent\Model;

class TemplateType extends Model
{
    protected $fillable = [
        'name', 'display_name', 'group_name'
    ];

    public function templates(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Template::class);
    }
}
