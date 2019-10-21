<?php

return [
    // To disable the pixel injection, set this to false.
    'pixel' => true,
    // To disable injecting tracking links, set this to false.
    'links' => true,
    // Optionally expire old emails, set to 0 to keep forever.
    'expire-days' => 60,
    // Where should the pingback URL route be?
    'route' => [
        'prefix'     => 'email',
        'middleware' => ['api'],
    ],
    //Auth Routes For telemetry
    'auth-route' => [
        'enabled'     => true,
        'prefix'      => 'email',
        'middleware'  => ['api:auth', 'can:emails-list-telemetry'],
    ],
    // Number of emails per page in the admin view
    'emails-per-page' => 30,
    // Date Format
    'date-format' => 'm/d/Y g:i a',
    // Default database connection name (optional - use null for default)
    'connection' => null,
    // The SNS notification topic - if set, discard all notifications not in this topic.
    'sns-topic' => null,
    // Determines whether or not the body of the email is logged in the sent_emails table
    'log-content' => true,
];
