<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
    ],

    'photoroom' => [
        'key' => env('PHOTOROOM_API_KEY'),
    ],

    'pathao' => [
        'base_url' => env('PATHAO_BASE_URL', 'https://courier-api-sandbox.pathao.com'),
        'client_id' => env('PATHAO_CLIENT_ID'),
        'client_secret' => env('PATHAO_CLIENT_SECRET'),
        'username' => env('PATHAO_USERNAME'),
        'password' => env('PATHAO_PASSWORD'),
        'grant_type' => env('PATHAO_GRANT_TYPE', 'password'),
        'store_id' => env('PATHAO_STORE_ID'),
        'webhook_secret' => env('PATHAO_WEBHOOK_SECRET'),
        'webhook_integration_secret' => env('PATHAO_WEBHOOK_INTEGRATION_SECRET'),
    ],

    'steadfast' => [
        'base_url' => env('STEADFAST_BASE_URL', 'https://portal.packzy.com/api/v1'),
        'api_key' => env('STEADFAST_API_KEY'),
        'secret_key' => env('STEADFAST_SECRET_KEY'),
        'webhook_bearer_token' => env('STEADFAST_WEBHOOK_BEARER_TOKEN'),
    ],

    'redx' => [
        'base_url' => env('REDX_BASE_URL', 'https://sandbox.redx.com.bd/v1.0.0-beta'),
        'access_token' => env('REDX_ACCESS_TOKEN'),
        'pickup_store_id' => env('REDX_PICKUP_STORE_ID'),
        'webhook_bearer_token' => env('REDX_WEBHOOK_BEARER_TOKEN'),
    ],

    'bdcourier' => [
        'base_url' => env('BDCOURIER_BASE_URL', 'https://api.bdcourier.com'),
        'api_key' => env('BDCOURIER_API_KEY'),
    ],

    'whatsapp' => [
        'base_url' => env('WHATSAPP_BASE_URL', 'https://graph.facebook.com'),
        'graph_api_version' => env('WHATSAPP_GRAPH_API_VERSION', 'v21.0'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),
        'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
    ],

    'messenger' => [
        'base_url' => env('MESSENGER_BASE_URL', 'https://graph.facebook.com'),
        'graph_api_version' => env('MESSENGER_GRAPH_API_VERSION', 'v21.0'),
        'page_id' => env('MESSENGER_PAGE_ID'),
        'page_access_token' => env('MESSENGER_PAGE_ACCESS_TOKEN'),
        'app_secret' => env('MESSENGER_APP_SECRET'),
        'webhook_verify_token' => env('MESSENGER_WEBHOOK_VERIFY_TOKEN'),
    ],

    'instagram' => [
        'base_url' => env('INSTAGRAM_BASE_URL', 'https://graph.facebook.com'),
        'graph_api_version' => env('INSTAGRAM_GRAPH_API_VERSION', 'v21.0'),
        'account_id' => env('INSTAGRAM_ACCOUNT_ID'),
        'access_token' => env('INSTAGRAM_ACCESS_TOKEN'),
        'app_secret' => env('INSTAGRAM_APP_SECRET'),
        'webhook_verify_token' => env('INSTAGRAM_WEBHOOK_VERIFY_TOKEN'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
