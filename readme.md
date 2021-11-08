# Idempotent

This package is created as a sample for creating [idempotent services](https://restfulapi.net/idempotent-rest-apis/),
and in its current state, maybe not extendable to different requirements. However, this package will be updated from
time to time, giving complete control over various aspects of it.

## Installation

Via Composer

```bash
$ composer require sobhanatar/idempotent
```

## Usage

To use this package, you need to publish its configuration and language files and set the options as per the need of
your service. The configuration file is self-documented so that you can find your way through.

```bash
$ php artisan vendor:publish --tag=idempotent-config --tag=idempotent-language
```

The next step is deciding on how you want to control the idempotency of your service. In this package, two kinds of
middlewares can help you achieve the idempotency.

### IdempotentHash

This middleware gets the `entity` from the configuration file, and it makes an idempotent identifier based on the
entity's `fields`. It's important to know that storing, checking, or any other use of the key will remain for the
developer to handle.

### IdempotentVerify

This middleware handles all the required steps for making an endpoint idempotent. The steps are as follows:

1. Get the entity configuration
2. Create an idempotent key/hash based on the entity's `fields`.
3. Check if the idempotent key/hash exists in the selected `storage`.
4. If it doesn't exist:
    1. A new record with the status of `progress` be created with the entity's `timeout,` and it continues to the logic
       of the service
    2. When code execution has finished, the response to the client updates the `status` and `response` fields of the
       cache.
5. If it exists:
    1. If the `status` is `done` or `fail`, then the `response` will be read from storage and replied to the user.
    2. If the `status` is `progress`, the message in `idempotent` language file for that entity will be returned.

Note: Make sure to use any of two middlewares to only those routes that you want to be idempotent, and not all the
routes.

## Changelog

Please see the [changelog](changelog.MD) for more information on recent changes.

## Testing

```bash
$ composer test
```

## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.

## Security

If you discover any security-related issues, please email atarsobhan@gmail.com instead of using the issue tracker.

## Credits

- [Sobhan Atar][link-author]
- [All Contributors][link-contributors]

## License

MIT. Please see the [license file](license.MD) for more information.

[ico-version]: https://img.shields.io/packagist/v/sobhanatar/idempotent.svg?style=flat-square

[ico-downloads]: https://img.shields.io/packagist/dt/sobhanatar/idempotent.svg?style=flat-square

[ico-travis]: https://img.shields.io/travis/sobhanatar/idempotent/master.svg?style=flat-square

[ico-styleci]: https://styleci.io/repos/12345678/shield

[link-packagist]: https://packagist.org/packages/sobhanatar/idempotent

[link-downloads]: https://packagist.org/packages/sobhanatar/idempotent

[link-travis]: https://travis-ci.org/sobhanatar/idempotent

[link-styleci]: https://styleci.io/repos/12345678

[link-author]: https://github.com/sobhanatar

[link-contributors]: ../../contributors
