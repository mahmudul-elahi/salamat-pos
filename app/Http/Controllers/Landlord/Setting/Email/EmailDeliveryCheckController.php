<?php

namespace App\Http\Controllers\Landlord\Setting\Email;

use App\Http\Controllers\Controller;
use App\Jobs\Landlord\Test\TestJob;
use App\Services\Setting\EmailSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailDeliveryCheckController extends Controller
{
    public function __construct(protected EmailSettingService $service) {}

    public function sendTestMail(Request $request): JsonResponse
    {
        $request->validate([
            'email_address' => 'required|email',
            'subject' => 'required',
            'message' => 'required',
        ]);

        dispatch(new TestJob($request->all()))->onConnection('sync');

        return successResponse('Email sent successfully');

    }

    public function isExists(): int
    {

        $default = $this->service->getDefaultSettings('default_mail');
        $check = $this->service->getFormattedDeliverySettings([optional($default)->value, 'default_mail_email_name']);

        return count($check) ? 1 : 0;
    }
}
