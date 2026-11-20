<?php

namespace App\Models\Traits\User;

trait UserMethodTrait
{
    public function isAppAdmin(): bool
    {
        return $this->admin();
    }

    public function isCustomer(): bool
    {
        return $this->customer();
    }

    /*
    * @param string $type
    * @param null $brand_id
    * @return mixed
    * @throws \Exception
    *
    * Basically the result is cached. it will be deleted if any updated is happens in user model
    */
    public function admin(): bool
    {
        return $this->roles()
            ->where('is_admin', 1)
            ->where('is_default', 1)
            ->exists();
    }

    public function customer(): bool
    {
        return $this->roles()
            ->where('alias', 'customer')
            ->where('is_default', 1)
            ->exists();
    }
}
