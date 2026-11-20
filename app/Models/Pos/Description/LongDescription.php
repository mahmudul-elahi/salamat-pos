<?php
 
namespace App\Models\Pos\Description;
 
use Illuminate\Database\Eloquent\Model;
 
class LongDescription extends Model
{
    protected $table = 'long_descriptions';
 
    protected $fillable = [
        'tenant_id',
        'description',
        'model_type',
        'model_id',
    ];
 
    public function model()
    {
        return $this->morphTo();
    }
}
