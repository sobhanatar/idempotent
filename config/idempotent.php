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
    | Idempotent Storage Service
    |--------------------------------------------------------------------------
    |
    | If any of the entities you use needs to use database to provide idempotency,
    | use following parameters to set the table name. Available option is mysql.
    | Otherwise, if the requirement is redis, use `redis` in connection part of
    | the entity.
    |
    */

    'database' => [
        'connection' => 'mysql',
        'table' => 'idempotent',
    ],

    /*
    |--------------------------------------------------------------------------
    | Entities
    |--------------------------------------------------------------------------
    |
    | Here are each of the entities setup for your application. Each entity can
    | have its own connection, TTL(ms), and required fields to unique a request
    | of an entity. Notice that if an entity uses the redis provider, the name of
    | entity will be used as redis database.
    |
    */

    'entities' => [
        'users-post' => [
            'ttl' => 3600,
            'connection' => 'mysql',
            'fields' => ['first_name', 'last_name', 'email'],
        ],
        'news-post' => [
            'ttl' => 3600,
            'connection' => 'redis',
            'fields' => ['title', 'summary'],
        ],
    ]
];
