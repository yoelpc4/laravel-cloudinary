# Laravel Cloudinary

[![Laravel][ico-laravel]][link-laravel]
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
CLOUDINARY_SECURE=true
```

## Filesystem Configuration

Register cloudinary driver configuration in config/filesystems.php at disks section as follows

```php
'cloudinary' => [
    'driver'         => 'cloudinary',
    'api_key'        => env('CLOUDINARY_API_KEY'),
    'api_secret'     => env('CLOUDINARY_API_SECRET'),
    'cloud_name'     => env('CLOUDINARY_CLOUD_NAME'),
    'secure'         => env('CLOUDINARY_SECURE', true),
    'resource_types' => [
        'image' => [
            'png',
            'jpeg',
            'jpg',
        ],
        'video' => [
            'mp4',
            'avi',
            'mp3',
            'flac',
        ],
        'raw'   => [
            'pdf',
            'xlsx',
            'csv',
            'txt',
        ],
    ],
],
```

The secure option is applied when generating url from storage, when `secure = true` will used `https` 
otherwise `secure = false` will used `http` as protocol.

The resource_types option is applied when generating resource type & public id whenever we call storage method such as
write, writeStream, url, has, etc. Registers the appropriate file extensions according to cloudinary resource type e.g: 
png in image, mp4 in video, xlsx in raw, for audio files registers in video. `The default resource type is image`, 
for more reference see https://cloudinary.com/documentation/image_upload_api_reference#upload_method.

## Tips

To use pre-defined filename as public ID when uploading to cloudinary, you need to tweak some configuration 
in `Settings -> Upload -> Upload presets` as follows:
- Click edit button on signed mode preset, initial preset is `ml_default` you can updates it.
- Turn on `Use filename or externally defined public ID` to using the pre-defined filename instead of random characters.
- Turn off `Unique filename` to prevent cloudinary from adding random characters at the end of filename.
- Click `Save` and you're good to go.

## License

The Laravel Cloudinary is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).

[ico-laravel]: https://img.shields.io/badge/framework-laravel-red.svg?style=flat-square
[ico-packagist]: https://img.shields.io/packagist/v/yoelpc4/laravel-cloudinary.svg?style=flat-square
[ico-build]: https://travis-ci.com/yoelpc4/laravel-cloudinary.svg?branch=master&style=flat-square
[ico-code-coverage]: https://codecov.io/gh/yoelpc4/laravel-cloudinary/branch/master/graph/badge.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/yoelpc4/laravel-cloudinary.svg?style=flat-square
[ico-license]: https://img.shields.io/packagist/l/yoelpc4/laravel-cloudinary.svg?style=flat-square
[ico-code-of-conduct]: https://img.shields.io/badge/contributor%20covenant-v1.4-ff69b4.svg

[link-laravel]: https://laravel.com
[link-packagist]: https://packagist.org/packages/yoelpc4/laravel-cloudinary
[link-build]: https://travis-ci.com/yoelpc4/laravel-cloudinary
[link-code-coverage]: https://codecov.io/gh/yoelpc4/laravel-cloudinary
[link-downloads]: https://packagist.org/packages/yoelpc4/laravel-cloudinary
[link-license]: LICENSE.md
[link-code-of-conduct]: CODE_OF_CONDUCT.md
