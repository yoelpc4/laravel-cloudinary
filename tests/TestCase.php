<?php

namespace Yoelpc4\LaravelCloudinary\Tests;

use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Yoelpc4\LaravelCloudinary\CloudinaryServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * @inheritDoc
     */
    protected function getPackageProviders($app)
    {
        return [
            CloudinaryServiceProvider::class,
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getEnvironmentSetUp($app)
    {
        $app->useEnvironmentPath(__DIR__.'/../../laravel-cloudinary')
            ->loadEnvironmentFrom('.env.testing')
            ->bootstrapWith([
                LoadEnvironmentVariables::class,
            ]);

        $app['config']->set('filesystems.disks.cloudinary', [
            'driver'         => 'cloudinary',
            'api_key'        => env('CLOUDINARY_API_KEY'),
            'api_secret'     => env('CLOUDINARY_API_SECRET'),
            'cloud_name'     => env('CLOUDINARY_CLOUD_NAME'),
            'secure'         => env('CLOUDINARY_SECURE', true),
            'resource_types' => [
                'image' => [
                    'png',
                ],
                'video' => [],
                'raw'   => [
                    'pdf',
                ],
            ],
        ]);
    }
}
