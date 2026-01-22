<?php

return [
    'new_user_registration' => [
        'recipients' => explode(',', env('NEW_USER_NOTIFICATION_RECIPIENTS', 'sales@get-sales.com')),
    ],
];
