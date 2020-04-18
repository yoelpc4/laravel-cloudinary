<?php

namespace Yoelpc4\LaravelCloudinary\Mocks;

use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class DocumentMock implements Mockable
{
    /**
     * @var string
     */
    protected $contents;

    /**
     * DocumentMock constructor.
     *
     */
    public function __construct()
    {
        if (! $this->contents = file_get_contents($url = 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf')) {
            throw new FileException("Failed to reads entire file into a string from {$url}");
        }
    }

    /**
     * @inheritDoc
     */
    public function make(string $name)
    {
        $fake = UploadedFile::fake();

        if (! method_exists($fake, 'createWithContent')) {
            return $fake->create($name, 12, 'application/pdf');
        }

        return $fake->createWithContent($name, $this->contents);
    }
}
