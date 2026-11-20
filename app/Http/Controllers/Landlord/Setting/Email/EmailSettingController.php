<?php

namespace App\Http\Controllers\Landlord\Setting\Email;

use App\Http\Controllers\Controller;
use App\Http\Requests\Setting\Email\EmailSettingRequest;
use App\Services\Setting\EmailSettingService;

class EmailSettingController extends Controller
{

    public function __construct(protected EmailSettingService $service)
    {

    }

    public function index(): array
    {
        
            $default = $this->service->getDefaultSettings();
            return $this->service->getFormattedDeliverySettings([optional($default)->value, 'default_mail_email_name']);
        
    }

    public function update(EmailSettingRequest $request)
    {
        $context = $request->get('provider');

        foreach ($request->only('from_name', 'from_email') as $key => $value) {

            $this->service
                ->update($key, $value, 'default_mail_email_name');
        }

        foreach ($request->except('allowed_resource', 'from_name', 'from_email') as $key => $value) {

            $this->service
                ->update($key, $value, $context);
        }

        $this->service->setDefaultSettings('default_mail', $context, 'mail');

        return successResponse('Email settings updated successfully');
    }
}
