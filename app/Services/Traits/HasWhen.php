<?php

namespace App\Services\Traits;

trait HasWhen
{
    public function when($value, callable $callback, ?callable $fallback = null)
    {
        if ($value) {
            return $callback($this, $value) ?: $this;
        }
        if ($fallback) {
            return $fallback($this, $value) ?: $this;
        }

        return $this;
    }
}
