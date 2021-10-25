<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Hash Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default hash driver that will be used to hash
    | fields for your application. By default, the "sha-256" algorithm is used;
    | however, you remain free to use any algorithms that is possible to use
    | hash() function
    |
    | Supported: hash_algos()
    |
    */

    'driver' => 'sha256',

    /*
    |--------------------------------------------------------------------------
    | Idempotent Storage Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the connections the package requires as idempotent
    | provider. If app uses both database and redis in its entities then change
    | the configuration in accordance with the app needs.
    |
    */

    'storage' => [
        'database' => [
            'connection' => config('database.default'),
            'table' => 'service_idempotent',
        ],
        'redis' => [
            'connection' => config('database.redis.default')
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Entities
    |--------------------------------------------------------------------------
    |
    | Here are each of the entities setup for your application. Each entity can
    | have its own connection (databases or redis), TTL(ms), and required fields
    | to unique a request of an entity. Notice that if an entity uses the redis
    | provider, the name of entity will be used as redis database.
    |
    */

    'entities' => [
        'user' => [
            'ttl' => 3600,
            'connection' => 'database', // or redis
            'methods' => ['post'], // other options are put, patch, and delete
            'fields' => ['first_name', 'last_name', 'email'],
        ]
    ]
];
