<?php


use App\Exceptions\AuthorizeException;
use App\Helpers\General\AuthorizeHelper;

if (!function_exists('authorize')) {

    /**
     * @param  $actions  array|string
     *
     * @return bool
     * @throws AuthorizeException
     */
    function authorize(array|string $actions): bool
    {
        return resolve(AuthorizeHelper::class)->authorize($actions);
    }
}

if (!function_exists('get_authorized')) {

    /**
     * @return mixed
     *               get authorized list if empty that mean whole list
     * @throws AuthorizeException
     */
    function get_authorized(string $action): mixed
    {
        return resolve(AuthorizeHelper::class)->getAuthorized($action);
    }
}

if (!function_exists('is_authorized_specific')) {

    /**
     * @param string $action
     * @param  $payload  integer|string
     * @return mixed
     *               is a specific model is authorized
     * @throws AuthorizeException
     */
    function is_authorized_specific(string $action, int|string $payload): mixed
    {
        return resolve(AuthorizeHelper::class)->isAuthorizedSpecific($action, $payload);
    }
}

if (!function_exists('authorize_any')) {

    function authorize_any(array $actions): bool
    {
        return resolve(AuthorizeHelper::class)->authorizeAny($actions);
    }
}

if (!function_exists('authorize_multiple')) {

    function authorize_multiple(array $actions): bool
    {
        return resolve(AuthorizeHelper::class)->authorizeMultiple($actions);
    }
}
