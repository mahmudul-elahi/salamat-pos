<?php

namespace App\Services;

use App\Services\Traits\HasAttrs;
use Illuminate\Database\Eloquent\Model;

class BaseService
{
    use HasAttrs;
    protected Model $model;

    /**
     * Set the model instance.
     */
    public function setModel(Model $model): static
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Get the current model instance.
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * Save the model with the provided options or request data.
     */
    public function save(array $data = []): Model
    {
        $this->model->fill(!empty($data) ? $data : request()->all())->save();
        return $this->model;
    }

    /**
     * Find a model by its primary key.
     */
    public function find(int|string $id): Model|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Builder|array|null
    {
        return $this->model = $this->model::query()->find($id);
    }

    /**
     * Handle dynamic method calls to the model.
     */
    public function __call(string $method, array $arguments)
    {
        return $this->model->{$method}(...$arguments);
    }
}
