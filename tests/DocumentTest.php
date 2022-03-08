<?php

namespace Yoelpc4\LaravelCloudinary\Tests;

use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;

class DocumentTest extends FileTestCase
{
    /**
     * @inheritdoc
     */
    protected function getFile(): File
    {
        return UploadedFile::fake()->createWithContent(
            $this->getRandomPath($this->getExtension()),
            file_get_contents('https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf')
        );
    }

    /**
     * @inheritdoc
     */
    protected function getExtension(): string
    {
        return 'pdf';
    }

    /**
     * @inheritdoc
     */
    protected function getDirectory(): string
    {
        return 'documents';
    }
}
