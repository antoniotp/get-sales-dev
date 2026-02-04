<?php

return [
    'new_user_registration' => [
        'recipients' => explode(',', env('NEW_USER_NOTIFICATION_RECIPIENTS', 'sales@get-sales.com')),
    ],

    'ai_processing_failure' => [
        'recipients' => explode(',', env('DEVELOPER_NOTIFICATION_RECIPIENTS', 'admin@get-sales.com')),
    ],

    'sales_address' => [
        'recipients' => explode(',', env('SALES_NOTIFICATION_RECIPIENTS', 'sales@get-sales.com')),
    ],
];
