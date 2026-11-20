<?php

namespace Database\Seeders\Template;

use App\Models\Template\Template;
use App\Models\Template\TemplateType;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'subject' => 'You’re Invited to {app_name} – Get Started in Minutes',
                'default_content' => '<p class="logo" style="text-align: center; width: 70%;"><img width="auto" height="72px" src="{app_logo}" alt="Logo"/></p>
                                    <p style="color: #4F5867; font-size: 15px; padding-top: 20px; margin: 0 auto;  width: 70%;">
                                        Hey,
                                    </p>
                                    <p style="color: #4F5867; font-size: 15px; font-weight: 400; padding-top: 30px; margin: 0 auto; width: 70%;">Exciting news! {app_name} has invited you to join Easy Invoice, the easiest way to manage invoices,quotes, and payments.</p>
                                    <p style="color: #4F5867; font-size: 15px; font-weight: 400; padding-top: 30px; margin: 0 auto; width: 70%;">Here’s how to get started:</p>
                                    <p style="color: #4F5867; font-size: 15px; font-weight: 400; padding-top: 10px; margin: 0 auto; width: 70%;"><span>1️.</span> <strong>Activate Your Account</strong> – Click below to set up your password. </p>
                                    <p style="color: #4F5867; font-size: 15px; font-weight: 400; padding-top: 10px; margin: 0 auto; width: 70%;"><span>2️.</span> <strong>Explore the Features</strong> – Create invoices, send estimates, and track payments effortlessly. </p>
                                    <p style="text-align: center; padding-top: 50px; padding-bottom: 50px; width: 70%; margin: 0 auto;">
                                        <a href="{button_url}" style="font-size: 16px;font-weight: 700; padding: 15px 32px; border-radius: 50px; background: #2b8768; text-decoration: none; color: #ffffff; border: none;">Accept Invitation</a>
                                    </p>
                                    <p style="color: #4F5867; font-size: 15px; font-weight: 400; margin: 0 auto; width: 70%;">We can’t wait for you to see how simple invoicing can be!</p>
                                    <p style="color: #4F5867; font-size: 15px; font-weight: 400; padding-top: 30px; margin: 0 auto; width: 70%;">Best,</p>
                                    <p style="color: #4F5867; font-size: 15px; font-weight: 400; padding-top: 10px; margin: 0 auto; width: 70%;">The {app_name} Team</p>',
                'custom_content' => null,
                'type' => 'mail',
                'template_type_id' => $this->templateType('user_invitation'),
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'subject' => 'Reset Your Password Request',
                'default_content' => '<p class="logo" style="text-align: center; width: 70%;">
                    <img width="auto" height="72px" src="{app_logo}" alt="Logo"/>
                </p>

                <p style="color: #4F5867; font-size: 15px; padding-top: 20px; margin: 0 auto; width: 70%;">
                    Hello, <span style="color: #2b8768; font-size: 20px;">{receiver_name}</span>
                </p>

                <p style="color: #4F5867; font-size: 15px; font-weight: 400; padding-top: 30px; margin: 0 auto; width: 70%;">
                    We received a request to reset your password. Click the button below to choose a new password.
                </p>

                <p style="text-align: center; padding-top: 40px; padding-bottom: 40px; width: 70%; margin: 0 auto;">
                    <a href="{button_url}" style="font-size: 16px; font-weight: 700; padding: 15px 32px; border-radius: 50px; background: #2b8768; text-decoration: none; color: #ffffff; border: none;">Reset Password</a>
                </p>

                <p style="color: #4F5867; font-size: 15px; font-weight: 400; padding-top: 10px; margin: 0 auto; width: 70%;">
                    If you didn\'t request a password reset, please ignore this email. Your current password will remain unchanged.
                </p>
                <p style="width: 70%; margin: 0 auto; color: #4F5867; font-size: 15px; padding-top: 30px;">Best,</p>
                <p style="width: 70%; margin: 0 auto; color: #4F5867; font-size: 15px; padding-top: 10px;">{app_name} Team</p>',
                'custom_content' => null,
                'type' => 'mail',
                'template_type_id' => $this->templateType('forget_password'),
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'subject' => 'Password Changed Successfully',
                'default_content' => '<p class="logo" style="text-align: center; width: 70%;">
                    <img width="auto" height="72px" src="{app_logo}" alt="Logo"/>
                </p>

                <p style="color: #4F5867; font-size: 15px; padding-top: 20px; margin: 0 auto; width: 70%;">
                    Hello <span style="color: #2b8768; font-size: 20px;">{receiver_name}</span>,
                </p>

                <p style="color: #4F5867; font-size: 15px; font-weight: 400; padding-top: 20px; margin: 0 auto; width: 70%;">
                We wanted to let you know that your password has been changed successfully. You don’t need to do anything else if you made this change.
                </p>

                <p style="color: #4F5867; font-size: 15px; font-weight: 400; padding-top: 20px; margin: 0 auto; width: 70%;">
                    If you didn’t change your password, please reset it immediately or contact our support team.
                </p>

                <p style="text-align: center; padding-top: 40px; padding-bottom: 40px; width: 70%; margin: 0 auto;">
                    <a href="{button_url}" style="font-size: 16px; font-weight: 700; padding: 15px 32px; border-radius: 50px; background: #2b8768; text-decoration: none; color: #ffffff; border: none;">Contact Support</a>
                </p>
                <p style="width: 70%; margin: 0 auto; color: #4F5867; font-size: 15px; padding-top: 30px;">Best,</p>
                <p style="width: 70%; margin: 0 auto; color: #4F5867; font-size: 15px; padding-top: 10px;">{app_name} Team</p>',
                'custom_content' => null,
                'type' => 'mail',
                'template_type_id' => $this->templateType('password_change_confirmation'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'subject' => 'Welcome to {app_name} – Let’s Get Started!',
                'default_content' => '<p class="logo" style="text-align: center; width: 70%;">
                    <img width="auto" height="72px" src="{app_logo}" alt="Logo"/>
                </p>

                <p style="color: #4F5867; font-size: 15px; padding-top: 20px; margin: 0 auto; width: 70%;">
                    Hello <span style="color: #2b8768; font-size: 20px;">{receiver_name}</span>,
                </p>

                <p style="color: #4F5867; font-size: 15px; font-weight: 400; padding-top: 20px; margin: 0 auto; width: 70%;">
                    Welcome to <strong>{app_name}</strong>! We’re excited to have you on board. Your account has been successfully created and you can now start exploring all the features available to you.
                </p>

                <p style="color: #4F5867; font-size: 15px; font-weight: 400; padding-top: 20px; margin: 0 auto; width: 70%;">
                    Click the button below to log in and get started.
                </p>

                <p style="text-align: center; padding-top: 40px; padding-bottom: 40px; width: 70%; margin: 0 auto;">
                    <a href="{button_url}" style="font-size: 16px; font-weight: 700; padding: 15px 32px; border-radius: 50px; background: #2b8768; text-decoration: none; color: #ffffff; border: none;">Log In</a>
                </p>
                <p style="width: 70%; margin: 0 auto; color: #4F5867; font-size: 15px; padding-top: 30px;">Best,</p>
                <p style="width: 70%; margin: 0 auto; color: #4F5867; font-size: 15px; padding-top: 10px;">{app_name} Team</p>',
                'custom_content' => null,
                'type' => 'mail',
                'template_type_id' => $this->templateType('user_joined'),
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ];

        foreach ($templates as $template) {
            Template::query()->create($template);
        }
    }

    private function templateType($type)
    {
        return TemplateType::query()
            ->where('name', $type)
            ->value('id');
    }
}
