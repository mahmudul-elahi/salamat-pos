<?php

declare(strict_types=1);

namespace App\Services\Pos\Contact;

use App\Models\Pos\Contact\Contact;
use App\Services\BaseService;

class ContactService extends BaseService
{
    public function __construct(Contact $model)
    {
        $this->model = $model;
    }

    public function createCustomer(): static
    {
        $this->model::query()->create([
            'type' => $this->getAttribute('type'),
            'name' => $this->getAttribute('name'),
            'email' => $this->getAttribute('email'),
            'phone' => $this->getAttribute('phone'),
            'secondary_phone' => $this->getAttribute('secondary_phone'),
            'new_district_id' => $this->getAttribute('new_district_id'),
            'area_id' => $this->getAttribute('area_id'),
            'address' => $this->getAttribute('address'),
        ]);

        return $this;
    }

    public function updateCustomer(): static
    {
        $this->model->update([
            'type' => $this->getAttribute('type'),
            'name' => $this->getAttribute('name'),
            'email' => $this->getAttribute('email'),
            'phone' => $this->getAttribute('phone'),
            'secondary_phone' => $this->getAttribute('secondary_phone'),
            'new_district_id' => $this->getAttribute('new_district_id'),
            'area_id' => $this->getAttribute('area_id'),
            'address' => $this->getAttribute('address'),
        ]);

        return $this;
    }
}
