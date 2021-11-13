## Introduction

Idempotent package provides [idempotency](https://restfulapi.net/idempotent-rest-apis/) for your laravel package.
However, in its current state, maybe not extendable to different requirements and can be used as a sample of doing so.
Moreover, this package will be updated, in attempt to giving more control over various aspects of it.

## Installation

To get started, install the Idempotent package via the Composer package manager:

```bash
$ composer require sobhanatar/idempotent
```

Idempotent service provider registers its own config, language, and database migration file, so you need to export them

```bash
$ php artisan vendor:publish --provider="Sobhanatar\Idempotent\IdempotentServiceProvider"
```

**Note:** If you don't want to use mysql database as shared memory, you can publish config, and language file using
following command:

```bash
$ php artisan vendor:publish --tag=idempotent-config --tag=idempotent-language
```

## Deploying Idempotent

To use idempotent package, you need set the options as per the need of your service in configuration and language files.
The configuration file is self-documented so that you can find your way through.

The next step is deciding on how you want to control the idempotency of your service. Idempotent package provides two
middlewares that can help you achieve the idempotency; `IdempotentHeader`, and `VerifyIdempotent`. Don't forget to
register the middleware in `Kernel.php`.

### IdempotentHeader

`IdempotentHeader` makes an idempotent key/hash based on the entity's configurations and put it in the header of
request. The assumption in this middleware is that the developer will remain responsible for the logic of using the
idempotent header.

### VerifyIdempotent

`VerifyIdempotent` handles all the required steps for making an endpoint idempotent. The steps are as follows:

1. Get the `entity` configuration
2. Create an idempotent key/hash based on the entity's configurations.
3. Check if the idempotent key/hash exists in the selected `storage`.
4. If it doesn't exist:
    1. A new record with the status of `progress` be created with the entity's `timeout,` and it continues to the logic
       of the service
    2. When code execution has finished, the response to the client updates the `status` and `response` of the cache.
5. If it exists:
    1. If the `status` is `done` or `fail`, then the `response` will be read from storage and replied to the user.
    2. If the `status` is `progress`, the message in `idempotent` language file for that `entity` will be return as
       response to the user.

Note: Make sure to use any of two middlewares to only those routes that you want to be idempotent, and not all the
routes.

## Purging Idempotent Keys/Hashes

If you use `mysql` as the storage, it's important to purge the expired keys/hashes. Idempotent included
`idempotent:purge` Artisan command can do this for you.

```bash
# Purge expired keys/hashes
$ php artisan idempotent:purge --entity=my-idempotent-endpoint
```

You may also configure a scheduled job in your application's `App\Console\Kernel` class to automatically prune your
tokens on a schedule:

```php
/**
 * Define the application's command schedule.
 *
 * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
 * @return void
 */
protected function schedule(Schedule $schedule)
{
    $schedule->command('idempotent:purge')->hourly();
}

```

## Testing

```bash
$ composer test
```

## Contributing

Thank you for considering contributing to Idempotent! You can read the contribution guide [here](CONTRIBUTING.md).

## Security

If you discover any security-related issues, please email sobhanattar@gmail.com instead of using the issue tracker.

## Credits

- [Sobhan Atar](https://github.com/sobhanatar)

## License

Idempotent is open-sourced software licensed under the [MIT license](LICENSE.md).
