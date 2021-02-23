<?php

namespace Yoelpc4\LaravelCloudinary\Tests;

use Illuminate\Http\UploadedFile;

class ImageTest extends TestCase
{
    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $name = $this->getRandomPath($this->extension());

        $this->file = UploadedFile::fake()->image($name, 1, 1)->size(1);
    }

    /**
     * @inheritDoc
     */
    protected function extension()
    {
        return 'png';
    }

    /**
     * @inheritDoc
     */
    protected function directory()
    {
        return 'images';
    }
}
