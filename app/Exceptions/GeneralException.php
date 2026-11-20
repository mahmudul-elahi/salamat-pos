<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class GeneralException extends Exception
{
    public function __construct($message = '', $code = 400, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function report(): void
    {
        if ($this->getCode() >= 500) {
            Log::error($this->getMessage(), [
                'exception' => $this,
            ]);
        }
    }

    public function render(Request $request): JsonResponse
    {
        if ($request->expectsJson()) {
            return errorResponse($this->getMessage(), null, $this->getCode());
        }

        return errorResponse('Bad Request');
    }
}
