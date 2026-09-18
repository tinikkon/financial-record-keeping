<?php

declare(strict_types=1);

return [

    'rabbitmq' => [
        'host' => env('RABBITMQ_HOST', 'rabbitmq'),
        'port' => (int) env('RABBITMQ_PORT', 5672),
        'user' => env('RABBITMQ_USER', 'finance'),
        'password' => env('RABBITMQ_PASSWORD', 'finance'),
        'vhost' => env('RABBITMQ_VHOST', 'finance'),
        'exchange' => env('RABBITMQ_EXCHANGE', 'finance.events'),
        'connection_timeout' => 3.0,
    ],

    'routing_keys' => [
        'cells_changed' => 'sheet.cells.changed',
    ],

];
