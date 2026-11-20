<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Throwable;

class GeneralException extends Exception
{
    public function __construct($message = '', $code = 400, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function report()
    {
        //
    }


    public function render(Request $request): \Illuminate\Http\JsonResponse
    {
        if ($request->expectsJson()) {
            return errorResponse($this->getMessage(), null, $this->getCode());
        }
        return errorResponse('Bad Request');
    }
}
