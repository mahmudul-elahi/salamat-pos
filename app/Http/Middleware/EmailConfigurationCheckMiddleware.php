<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Landlord\Setting\Email\EmailDeliveryCheckController;
use Illuminate\Http\Request;

class EmailConfigurationCheckMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        // Check if the email configuration is set up
        $checkEmailConfig = resolve(EmailDeliveryCheckController::class)->isExists();
        if (! $checkEmailConfig) {
            return errorResponse('Please set up your email configuration first');
        }

        return $next($request);
    }
}
