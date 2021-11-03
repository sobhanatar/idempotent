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
    | Header name
    |--------------------------------------------------------------------------
    |
    | This option controls the header name being used for hash in header of request
    |
    */

    'header' => 'idempotent',

    /*
    |--------------------------------------------------------------------------
    | Table name
    |--------------------------------------------------------------------------
    |
    | This table name that holds the hash of entities
    |
    */

    'table' => 'idempotent',

    /*
    |--------------------------------------------------------------------------
    | Redis connection
    |--------------------------------------------------------------------------
    |
    | specify the redis connection variables
    |
    */

    'redis' => [
        'host' => 'localhost',
        'port' => 6379,
        'timeout' => 0.0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Entities
    |--------------------------------------------------------------------------
    |
    | Here are each of the entities setup for your application. Each entity can
    | have its own connection, TTL(seconds), timeout(seconds) for locking timeout, and
    | required fields to unique a request of an entity, .
    |
    */

    'entities' => [
        'users-post' => [
            'connection' => 'mysql',
            'ttl' => 100,
            'timeout' => 5,
            'fields' => ['first_name', 'last_name', 'email'],
        ],
        'news-post' => [
            'connection' => 'redis',
            'ttl' => 100,
            'timeout' => 1,
            'fields' => ['title', 'summary'],
        ],
    ]
];
