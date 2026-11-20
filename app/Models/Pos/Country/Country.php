<?php

namespace App\Models\Pos\Country;
use App\Models\Common\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends BaseModel
{
    use HasFactory;
    protected $fillable = ['code', 'name'];
}
