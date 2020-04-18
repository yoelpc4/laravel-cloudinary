<?php

namespace Yoelpc4\LaravelCloudinary\Mocks;

use Illuminate\Http\Testing\File;
use Illuminate\Http\Testing\FileFactory;
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

        return $this->createWithContent($fake, $name);
    }

    /**
     * Create with content with polyfill
     *
     * @param  FileFactory  $fake
     * @param  string  $name
     * @return File|mixed
     */
    protected function createWithContent(FileFactory $fake, string $name)
    {
        if (! method_exists($fake, 'createWithContent')) {
            $tmpfile = tmpfile();

            fwrite($tmpfile, $this->contents);

            return tap(new File($name, $tmpfile), function ($file) use ($tmpfile) {
                $file->sizeToReport = fstat($tmpfile)['size'];
            });
        }

        return $fake->createWithContent($name, $this->contents);
    }
}
