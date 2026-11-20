<?php

namespace App\Models\Pos;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntryJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'heading',
        'slug',
        'image',
        'vacancy',
        'job_type',
        'salary',
        'deadline',
        'qualification',
        'benefits',
        'meta_title',
        'meta_description',
        'keywords',
        'status',
    ];
    //
}
