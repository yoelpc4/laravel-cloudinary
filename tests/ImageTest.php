<?php

namespace Yoelpc4\LaravelCloudinary\Tests;

use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;

class ImageTest extends FileTestCase
{
    /**
     * @inheritdoc
     */
    protected function getFile(): File
    {
        return UploadedFile::fake()
            ->image($this->getRandomPath($this->getExtension()), 1, 1)
            ->size(1);
    }

    /**
     * @inheritdoc
     */
    protected function getExtension(): string
    {
        return 'png';
    }

    /**
     * @inheritdoc
     */
    protected function getDirectory(): string
    {
        return 'images';
    }
}
