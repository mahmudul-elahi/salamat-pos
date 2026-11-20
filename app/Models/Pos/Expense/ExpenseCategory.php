<?php

declare(strict_types=1);

namespace App\Models\Pos\Expense;

use App\Models\Common\BaseModel;

class ExpenseCategory extends BaseModel
{
    protected $fillable = ['name', 'logo'];

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
