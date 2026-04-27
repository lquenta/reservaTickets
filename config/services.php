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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
    ],

    'smtpkit' => [
        'api_key' => env('SMTPKIT_API_KEY'),
        'api_url' => env('SMTPKIT_API_URL', 'https://smtpkit.com/api/v1/send-email'),
    ],

    'sendgrid' => [
        'api_key' => env('SENDGRID_API_KEY'),
        'verify_ssl' => filter_var(env('SENDGRID_VERIFY_SSL', true), FILTER_VALIDATE_BOOLEAN),
    ],

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_API_KEY'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'https://api.mailgun.net'),
    ],

    'brevo' => [
        'api_key' => env('BREVO_API_KEY'),
        'api_url' => env('BREVO_API_URL', 'https://api.brevo.com/v3/smtp/email'),
        'verify_ssl' => filter_var(env('BREVO_VERIFY_SSL', true), FILTER_VALIDATE_BOOLEAN),
    ],

    'ticket_validator' => [
        'enabled' => filter_var(env('TICKET_VALIDATOR_API_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'api_key' => env('TICKET_VALIDATOR_API_KEY'),
        'test_mode' => filter_var(env('TICKET_VALIDATOR_TEST_MODE', false), FILTER_VALIDATE_BOOLEAN),
        'test_force_valid' => env('TICKET_VALIDATOR_TEST_FORCE_VALID'),
    ],

];
