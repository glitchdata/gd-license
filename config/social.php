<?php

return [
    'providers' => [
        'google' => [
            'enabled' => (bool) env('GOOGLE_OAUTH_ENABLED', true),
        ],
        'meta' => [
            'enabled' => (bool) env('META_OAUTH_ENABLED', false),
        ],
    ],
];
