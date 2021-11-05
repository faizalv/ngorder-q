<?php

use \Ngorder\Q\Amqp\Contracts\ExchangeType;
use Ngorder\Q\Amqp\Contracts\QueueType;

return [
    'connect_to' => 'default',

    'exchange' => [
        'type' => ExchangeType::TOPIC,
        'name' => 'events',
    ],

    'queue' => [
        'type' => QueueType::CLASSIC,
        'naming' => [
            'prefix' => 'Q'
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