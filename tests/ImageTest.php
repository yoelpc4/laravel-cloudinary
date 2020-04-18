<?php

namespace Yoelpc4\LaravelCloudinary\Tests;

use Yoelpc4\LaravelCloudinary\Mocks\ImageMock;

class ImageTest extends FileTestCase
{
    /**
     * @inheritDoc
     */
    protected function mockFile()
    {
        return new ImageMock;
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
