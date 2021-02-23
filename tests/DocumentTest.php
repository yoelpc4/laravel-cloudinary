<?php

namespace Yoelpc4\LaravelCloudinary\Tests;

use Illuminate\Http\UploadedFile;

class DocumentTest extends TestCase
{
    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $name = $this->getRandomPath($this->extension());

        $contents = file_get_contents($url = 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf');

        $this->file = UploadedFile::fake()->createWithContent($name, $contents);
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
