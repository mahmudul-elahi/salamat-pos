<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

abstract class BaseFilter
{
    /**
     * Supported SQL operators
     */
    protected array $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=', '<=>',
        'like', 'like binary', 'not like', 'ilike',
        '&', '|', '^', '<<', '>>',
        'rlike', 'not rlike', 'regexp', 'not regexp',
        '~', '~*', '!~', '!~*', 'similar to',
        'not similar to', 'not ilike', '~~*', '!~~*',
    ];

    /**
     * Stores filter attributes
     */
    protected array $attributes = [];

    /**
     * Eloquent Query Builder instance
     */
    protected Builder $builder;

    /**
     * Apply filters to the query builder
     */
    public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;

        // Process operator filters
        foreach (request()->all() as $key => $operator) {
            if (Str::startsWith($key, 'operator')) {
                $this->attributes[$key] = $this->sanitizeOperator($operator);
            }
        }

        // Process filter methods dynamically
        foreach (request()->except(array_keys($this->attributes)) as $name => $value) {
            $methodName = Str::camel($name);
            if (method_exists($this, $methodName)) {
                call_user_func([$this, $methodName], $value);
            }
        }

        return $this->builder;
    }

    /**
     * Get the filter operator if valid, default to '='
     */
    public function __get(string $name): string
    {
        $name = Str::snake($name);
        return (!empty($this->attributes[$name]) && in_array($this->attributes[$name], $this->operators))
            ? $this->attributes[$name]
            : '=';
    }

    /**
     * Sanitize operator input
     */
    private function sanitizeOperator($value): string
    {
        return in_array($value, $this->operators) ? $value : '=';
    }

    /**
     * Sanitize filter input data
     */
    private function sanitizeData($value)
    {
        return filter_var($value, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    }

    /**
     * Example filter method: Filter by selected ID
     */
    public function selected($selected = null): void
    {
        $selected = explode(',', $selected);

        $this->builder->when($selected, fn($query) => $query->whereIn('id', $selected));
    }
}
