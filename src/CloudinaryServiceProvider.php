<?php

namespace Yoelpc4\LaravelCloudinary;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use Yoelpc4\LaravelCloudinary\Adapters\CloudinaryAdapter;

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
        \Storage::extend('cloudinary', function (Application $app, array $config) {
            return new Filesystem(new CloudinaryAdapter($config));
        });
    }
}
