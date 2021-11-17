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
    | signature in header of request
    |
    */

    'header' => 'idempotent',

    /*
    |--------------------------------------------------------------------------
    | Table name
    |--------------------------------------------------------------------------
    |
    | The nae of the table that will be used to store the idempotent signature of
    | requests. This table is being used only if you want to use database for
    | storing the idempotent signatures.
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
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => null,
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
    | The key of each entity is the name of the route you want the middleware
    | to act on. If you use "." notation on naming the routes, you should make
    | sure to replace it with "_" for naming the entities. Make sure that if you
    | are using "mysql" storage on an entity, the name's length should not be
    | more than "64" characters.
    |
    | "storage", shows the storage you want to use for storing the idempotent
    | signature. Currently, available options are "mysql" and "redis".
    | Configuration of redis should be set in this configuration file, however,
    | the mysql configuration will be read from "config\database" file.
    |
    | "ttl", is the time for in seconds, in that this signature is available
    | and being checked with other requests idempotent signatures.
    |
    | "timeout" is related to mysql storage and it tries to obtain a lock
    | using this field in seconds. A negative value means infinite timeout
    | which is not recommended.
    |
    | "fields" as its name suggests, is a list from the names of the fields,
    | together make a request (idempotent-signature) unique - in regards to
    | the "ttl" of an entity.
    |
    | "headers" list all header names that the application may use to make the
    | idempotent signature unique. It's important to mention that the header's
    | name should exist, as the package does not change the name in any way and
    | ignores non-existence parameters.
    |
    | "servers" list all server parameters that the application may use to make
    | the idempotent signature unique. It's important to mention that the server's
    | parameter should exist, as the package does not change the name in any way
    | and ignores non-existence parameters.
    |
    */

    'entities' => [
        'users_post' => [
            'storage' => 'mysql',
            'ttl' => 100,
            'timeout' => 5,
            'fields' => ['first_name', 'last_name', 'email'],
            'headers' => ['User-Agent'],
            'servers' => ['REMOTE_ADDR']
        ],
        'news_post' => [
            'storage' => 'redis',
            'ttl' => 100,
            'fields' => ['title', 'summary'],
            'headers' => [],
            'servers' => []
        ],
    ]
];
