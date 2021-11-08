<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Hash Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default hash driver that will be used to hash
    | fields for the application. By default, the "sha-256" algorithm is used;
    | however, you remain free to use any algorithms that is possible to use
    | with hash() function
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
    | This option controls the header name being used for storing the idempotency
    | key in header of request
    |
    */

    'header' => 'idempotent',

    /*
    |--------------------------------------------------------------------------
    | Table name
    |--------------------------------------------------------------------------
    |
    | The nae of the table that will be used to store the idempotent key of
    | requests. This table is being used only if you want to use database for
    | storing the idempotent keys.
    |
    */

    'table' => 'idempotent',

    /*
    |--------------------------------------------------------------------------
    | Redis connection
    |--------------------------------------------------------------------------
    |
    | If you plan to use redis connection to control the idempotency of your service
    | here you should set the requirement configurations.
    |
    */

    'redis' => [
        'host' => 'localhost',
        'port' => 6379,
        'timeout' => 0.0,
        'reserved' => null,
        'retryInterval' => 0,
        'readTimeout' => 0.0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Entities
    |--------------------------------------------------------------------------
    |
    | Entities control the routes that idempotent service should act on them.
    | The structure is as follows:
    |
    | The key of each entity is the name of the route you want the middleware to
    | act on. If you use `.` notation on naming the routes, you should make sure
    | that replace `.` with `-` for naming the entities. Make sure that if you are
    | using `mysql` storage on an entity, the name's length should not be more than
    | `64` characters.
    |
    | `storage`, shows the storage you want to use for storing the
    |  idempotent key/hash. Currently, available options are `mysql` and `redis`.
    | Configuration of redis should be set in this configuration file, however,
    | the mysql configuration will be read from `config\database` file.
    |
    | `ttl`, is the time for in seconds, in that this key is available and being
    | checked with other requests idempotent keys/hashes.
    |
    | `timeout` is related to mysql storage and it tries to obtain a lock using a
    | `timeout` in seconds. A negative `timeout` value means infinite timeout.
    |
    | `fields` as its name suggests, is a list from the names of the fields,
    | together make a request (idempotent-key) unique - in regards to the `ttl` of
    | an entity.
    |
    */

    'entities' => [
        'users-post' => [
            'storage' => 'mysql',
            'ttl' => 100,
            'timeout' => 5,
            'fields' => ['first_name', 'last_name', 'email'],
        ],
        'news-post' => [
            'storage' => 'redis',
            'ttl' => 100,
            'fields' => ['title', 'summary'],
        ],
    ]
];
