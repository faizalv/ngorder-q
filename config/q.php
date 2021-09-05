<?php


return [
    'connect_to' => 'default',

    'exchange' => [
        'name' => 'events',
        'type' => 'topic'
    ],

    'queue' => [
        'naming' => [
            'prefix' => 'Q'
        ],
        'arguments' => [
            'type' => 'classic'
        ]
    ],

    'connections' => [
        'default' => [
            'host' => env('Q_HOST', 'localhost'),
            'port' => env('Q_PORT', 5672),
            'user' => env('Q_USER', 'guest'),
            'pass' => env('Q_PASSWORD', 'guest'),
            'vhost' => env('Q_VHOST', '/'),
        ]
    ],
];