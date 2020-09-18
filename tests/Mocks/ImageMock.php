<?php

namespace Yoelpc4\LaravelCloudinary\Tests\Mocks;

use Illuminate\Http\UploadedFile;

class ImageMock implements Mockable
{
    /**
     * @inheritDoc
     */
    public function make(string $name)
    {
        return UploadedFile::fake()->image($name, 1, 1)->size(1);
    }
}
