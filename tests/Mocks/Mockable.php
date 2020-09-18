<?php

namespace Yoelpc4\LaravelCloudinary\Tests\Mocks;

use Illuminate\Http\Testing\File;

interface Mockable
{
    /**
     * Create an instance of mock file
     *
     * @param  string  $name
     * @return File
     */
    public function make(string $name);
}
