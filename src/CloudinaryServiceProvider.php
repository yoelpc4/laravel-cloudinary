<?php

namespace Yoelpc4\LaravelCloudinary;

use Cloudinary\Api;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;

class CloudinaryServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Storage::extend('cloudinary', function (Application $app, array $config) {
            $api = new Api;

            return new Filesystem(new CloudinaryAdapter($config, $api));
        });
    }
}
