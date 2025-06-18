<?php

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

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // SMS Gateway Configuration (placeholder)
    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'), // log, twilio, nexmo, local_gateway
        'twilio' => [
            'sid' => env('TWILIO_SID'),
            'token' => env('TWILIO_TOKEN'),
            'from' => env('TWILIO_FROM'),
        ],
        'nexmo' => [
            'key' => env('NEXMO_KEY'),
            'secret' => env('NEXMO_SECRET'),
            'from' => env('NEXMO_FROM'),
        ],
        'local_gateway' => [
            'url' => env('SMS_GATEWAY_URL'),
            'username' => env('SMS_GATEWAY_USERNAME'),
            'password' => env('SMS_GATEWAY_PASSWORD'),
            'sender_id' => env('SMS_GATEWAY_SENDER_ID', 'MADANI'),
        ],
    ],

    // WhatsApp Configuration (placeholder - will be activated after purchasing service)
    'whatsapp' => [
        'driver' => env('WHATSAPP_DRIVER', 'disabled'), // disabled, business_api, blast_service
        'business_api' => [
            'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
            'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
            'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
        ],
        'blast_service' => [
            'api_url' => env('WHATSAPP_BLAST_API_URL'),
            'api_key' => env('WHATSAPP_BLAST_API_KEY'),
            'sender_number' => env('WHATSAPP_BLAST_SENDER_NUMBER'),
        ],
    ],

    // WatZap API Configuration
    'watzap' => [
        'api_key' => env('WATZAP_API_KEY', 'ILOZ2ZNXJIQILIAX'),
        'number_key' => env('WATZAP_NUMBER_KEY', 'GL9jBTuExuUDzncU'),
        'base_url' => env('WATZAP_BASE_URL', 'https://api.watzap.id/v1'),
    ],

];
