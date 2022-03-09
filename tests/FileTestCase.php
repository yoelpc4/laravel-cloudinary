<?php

namespace Yoelpc4\LaravelCloudinary\Tests;

use Illuminate\Contracts\Filesystem\FileExistsException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

abstract class FileTestCase extends TestCase
{
    /**
     * @var File
     */
    protected $file;

    /**
     * Get file for test
     *
     * @return File
     */
    abstract protected function getFile(): File;

    /**
     * Get test file extension
     *
     * @return string
     */
    abstract protected function getExtension(): string;

    /**
     * Get test file directory
     *
     * @return string
     */
    abstract protected function getDirectory(): string;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->file = $this->getFile();
    }

    /**
     * @throws FileNotFoundException
     */
    public function testItCanWriteFile()
    {
        $path = $this->getRandomPath($this->getExtension());

        $contents = $this->file->get();

        $this->assertTrue(Storage::write($path, $contents));
    }

    /**
     * @throws FileNotFoundException
     */
    public function testItCanWriteStreamFile()
    {
        $path = $this->getRandomPath($this->getExtension());

        $isUpdated = false;

        $tmpFile = tmpfile();

        if (fwrite($tmpFile, $this->file->get())) {
            $isUpdated = Storage::writeStream($path, $tmpFile);
        }

        $this->assertTrue($isUpdated);
    }

    /**
     * @throws FileNotFoundException
     */
    public function testItCanUpdateFile()
    {
        $isUpdated = false;

        $path = $this->getRandomPath($this->getExtension());

        $contents = $this->file->get();

        if (Storage::write($path, $contents)) {
            $isUpdated = Storage::update($path, $contents);
        }

        $this->assertTrue($isUpdated);
    }

    /**
     * @throws FileNotFoundException
     * @throws FileExistsException
     */
    public function testItCanUpdateStreamFile()
    {
        $isUpdated = false;

        $path = $this->getRandomPath($this->getExtension());

        $tmpFile = tmpfile();

        $contents = $this->file->get();

        if (fwrite($tmpFile, $contents)) {
            if (Storage::writeStream($path, $tmpFile)) {
                $isUpdated = Storage::updateStream($path, $tmpFile);
            }
        }

        $this->assertTrue($isUpdated);
    }

    public function testItCanRenameFile()
    {
        $from = $this->storeFile();

        $to = $this->getRandomFilename();

        $this->assertTrue(Storage::rename($from, $to));
    }

    public function testItCanCopyFile()
    {
        $from = $this->storeFile();

        $to = $this->getRandomFilename();

        $this->assertTrue(Storage::copy($from, $to));
    }

    public function testItCanDeleteFile()
    {
        $path = $this->storeFile();

        $this->assertTrue(Storage::delete($path));
    }

    public function testItHasFile()
    {
        $path = $this->storeFile();

        $this->assertTrue(Storage::has($path));
    }

    public function testItCanReadFile()
    {
        $path = $this->storeFile();

        $this->assertIsString(Storage::read($path));
    }

    public function testItCanReadStreamFile()
    {
        $path = $this->storeFile();

        $this->assertIsResource(Storage::readStream($path));
    }

    public function testItCanListContentsFile()
    {
        $path = $this->getRandomPath();

        $this->storeFile();

        $this->assertIsArray(Storage::listContents($path));
    }

    public function testItCanGetMetadataFile()
    {
        $path = $this->storeFile();

        $this->assertIsArray(Storage::getMetadata($path));
    }

    public function testItCanGetSizeFile()
    {
        $path = $this->storeFile();

        $this->assertIsInt(Storage::getSize($path));
    }

    public function testItCanGetMimetypeFile()
    {
        $path = $this->storeFile();

        $this->assertIsString(Storage::getMimetype($path));
    }

    public function testItCanGetTimestampFile()
    {
        $path = $this->storeFile();

        $this->assertIsInt(Storage::getTimestamp($path));
    }

    public function testItCanGetUrl()
    {
        $path = $this->storeFile();

        $url = Storage::url($path);

        $this->assertStringStartsWith('https', $url);
    }

    /**
     * Store file to the filesystem disk
     *
     * @return false|string
     */
    protected function storeFile()
    {
        $path = "test/{$this->getDirectory()}";

        $disk = config('filesystems.default');

        return $this->file->store($path, $disk);
    }

    /**
     * Get random filename
     *
     * @return string
     */
    protected function getRandomFilename(): string
    {
        return Str::random().".{$this->getExtension()}";
    }

    /**
     * Get random path
     *
     * @param  string|null  $extension
     * @return string
     */
    protected function getRandomPath(string $extension = null): string
    {
        $path = "test/{$this->getDirectory()}/".Str::random();

        return $extension ? "{$path}.{$extension}" : $path;
    }
}
