<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class AuthorizeException extends Exception
{
    public function __construct($message = '', $code = 403, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function render(Request $request): Application|Response|JsonResponse|\Illuminate\Contracts\Foundation\Application|ResponseFactory
    {
        if ($request->expectsJson()) {
            return errorResponse($this->getMessage(), null, $this->getCode());
        }

        return errorResponse('Bad Request');
    }
}
