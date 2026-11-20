<?php

namespace App\Http\Middleware\Traits;


trait RouteToPermissionStringTrait
{
    protected array $explodedRouteName;

    protected string $routeName;


    public function validateRouteName(): \Illuminate\Http\JsonResponse|static
    {
        $explodedRouteName = explode('.', $this->routeName);

        if (count($explodedRouteName) > 2) {
            return errorResponse("Route must have a name");
        }

        $this->explodedRouteName = $explodedRouteName;

        return $this;
    }

    public function getPermissionString(): string
    {
        $explodedRouteName = $this->explodedRouteName;

        if (count($explodedRouteName) == 2) {
            $replacerArray = ['store' => 'create', 'index' => 'view', 'destroy' => 'delete', 'show' => 'view', 'lists' => 'view', 'edit' => 'update'];

            /*
             * if the index is existed in replacer array the replacement the value with replacer array value
             * otherwise take the default value
            */
            $action = !empty($replacerArray[$explodedRouteName[1]]) ?
                $replacerArray[$explodedRouteName[1]] :
                $explodedRouteName[1];

            return str_replace('-', '_', $action . '_' . $explodedRouteName[0]);
        }

        $routeName = str_replace('-', '_', $this->routeName);

        return "manage_{$routeName}";
    }


    public function setRouteName(string $routeName): static
    {
        $this->routeName = $routeName;
        return $this;
    }
}
