<?php

return [
    'driver' => env('BROADCAST_CONNECTION', 'null'),
    'connections' => [
        'log' => ['driver' => 'log'],
        'null' => ['driver' => 'null'],
    ],
];
