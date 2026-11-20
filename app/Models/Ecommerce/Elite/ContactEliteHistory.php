<?php

namespace App\Models\Ecommerce\Elite;

use App\Models\Pos\Contact\Contact;
use Illuminate\Database\Eloquent\Model;

class ContactEliteHistory extends Model
{
    public $timestamps = false;

    protected $fillable = ['contact_id', 'elite_id', 'achieved_at'];

    protected $casts = ['achieved_at' => 'datetime'];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function elite()
    {
        return $this->belongsTo(Elite::class);
    }
}
