<?php

use Illuminate\Http\JsonResponse;

if (! function_exists('successResponse')) {
    /**
     * Return a JSON response for successful operations.
     *
     * @param  string  $message  Success message.
     * @param  mixed|null  $data  Additional data to include in the response.
     * @param  int  $code  HTTP status code (default: 200).
     */
    function successResponse(string $message = '', mixed $data = null, int $code = 200): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'result' => $data,
        ], $code);
    }
}

if (! function_exists('errorResponse')) {
    /**
     * Return a JSON response for error scenarios.
     *
     * @param  string  $message  Error message.
     * @param  mixed|null  $data  Additional data to include in the response.
     * @param  int  $code  HTTP status code (default: 400).
     */
    function errorResponse(string $message = '', mixed $data = null, int $code = 400): JsonResponse
    {
        if ($code >= 500 && ! config('app.debug')) {
            $message = 'An unexpected error occurred. Please try again.';
        }

        return response()->json([
            'message' => $message,
            'result' => $data,
        ], $code);
    }
}
