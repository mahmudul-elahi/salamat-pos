<?php

namespace App\Models\Template;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Template extends Model
{
    protected $fillable = [
        'subject',
        'default_content',
        'custom_content',
        'type',
        'template_type_id',
    ];

    public function templateType(): BelongsTo
    {
        return $this->belongsTo(TemplateType::class);
    }

    public function parse(array $vars = [], $subject = false): string
    {
        $content = $this->attributes['custom_content'] ?? $this->attributes['default_content'];
        if ($subject) {
            $content = $this->attributes['subject'];
        }

        return strtr($content, $vars);
    }

    public function subjectReplace(array $vars = []): string
    {
        return $this->parse($vars, true);
    }
}
