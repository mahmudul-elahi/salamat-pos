<?php

namespace App\Helpers\Traits;

trait InstanceCreatorTrait
{
    protected static array $instances = [];

    public static function new($singleton = false, ...$arguments)
    {
        if ($singleton) {
            if (isset(static::$instances[get_called_class()])) {
                return static::$instances[get_called_class()];
            }
            return static::$instances[get_called_class()] = new static(...$arguments);
        }
        return new static(...$arguments);
    }
}
