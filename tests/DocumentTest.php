<?php

namespace Yoelpc4\LaravelCloudinary\Tests;

use Yoelpc4\LaravelCloudinary\Tests\Mocks\DocumentMock;

class DocumentTest extends FileTestCase
{
    /**
     * @inheritDoc
     */
    protected function mockFile()
    {
        return new DocumentMock;
    }

    /**
     * @inheritDoc
     */
    protected function extension()
    {
        return 'pdf';
    }

    /**
     * @inheritDoc
     */
    protected function directory()
    {
        return 'documents';
    }
}
