# Laravel Cloudinary

[![Packagist][ico-packagist]][link-packagist]
[![Build][ico-build]][link-build]
[![Code Coverage][ico-code-coverage]][link-code-coverage]
[![Downloads][ico-downloads]][link-downloads]
[![License][ico-license]][link-license]
[![Contributor Covenant][ico-code-of-conduct]][link-code-of-conduct]

_Laravel Cloudinary filesystem cloud driver._

## Requirement

- [Laravel](https://laravel.com)
- [Cloudinary](http://cloudinary.com)

## Install

Require this package with composer via command:

```bash
composer require yoelpc4/laravel-cloudinary
```

## Environment

Get your Cloudinary account details at https://cloudinary.com/console, then add these lines to your .env.

```dotenv
FILESYSTEM_CLOUD=cloudinary

CLOUDINARY_API_KEY=
CLOUDINARY_API_SECRET=
CLOUDINARY_CLOUD_NAME=
```

## Filesystem Configuration

Register cloudinary driver configuration in config/filesystems.php at disks section as follows

```php
'cloudinary' => [
    'driver'     => 'cloudinary',
    'api_key'    => env('CLOUDINARY_API_KEY'),
    'api_secret' => env('CLOUDINARY_API_SECRET'),
    'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
],
```

## Tips

To use pre-defined filename as public ID when uploading to cloudinary, you need to tweak some configuration 
in `Settings -> Upload -> Upload presets`. 
- Click edit button on signed mode preset, initial preset is `ml_default` you can updates it.
- Turn on `Use filename or externally defined public ID` to using the pre-defined filename instead of random characters.
- Turn off `Unique filename` to prevent cloudinary from adding random characters at the end of filename.
- Click `Save` and you're good to go.

## License

The Laravel Cloudinary is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).

[ico-packagist]: https://img.shields.io/packagist/v/yoelpc4/laravel-cloudinary.svg?style=flat-square
[ico-build]: https://travis-ci.com/yoelpc4/laravel-cloudinary.svg?branch=master&style=flat-square
[ico-code-coverage]: https://codecov.io/gh/yoelpc4/laravel-cloudinary/branch/master/graph/badge.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/yoelpc4/laravel-cloudinary.svg?style=flat-square
[ico-license]: https://img.shields.io/packagist/l/yoelpc4/laravel-cloudinary.svg?style=flat-square
[ico-code-of-conduct]: https://img.shields.io/badge/contributor%20covenant-v1.4-ff69b4.svg

[link-packagist]: https://packagist.org/packages/yoelpc4/laravel-cloudinary
[link-build]: https://travis-ci.com/yoelpc4/laravel-cloudinary
[link-code-coverage]: https://codecov.io/gh/yoelpc4/laravel-cloudinary
[link-downloads]: https://packagist.org/packages/yoelpc4/laravel-cloudinary
[link-license]: LICENSE.md
[link-code-of-conduct]: CODE_OF_CONDUCT.md
