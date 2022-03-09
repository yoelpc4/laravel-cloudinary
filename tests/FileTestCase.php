<?php

namespace Yoelpc4\LaravelCloudinary\Tests;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Flysystem\DirectoryListing;

abstract class FileTestCase extends TestCase
{
    protected File $file;

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

        $this->assertTrue(Storage::put($path, $contents));
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

    public function testIsFileExists()
    {
        $path = $this->storeFile();

        $this->assertTrue(Storage::fileExists($path));
    }

    public function testItCanReadFile()
    {
        $path = $this->storeFile();

        $this->assertIsString(Storage::get($path));
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

        $this->assertInstanceOf(DirectoryListing::class, Storage::listContents($path));
    }

    public function testItCanGetFileMimetype()
    {
        $path = $this->storeFile();

        $this->assertIsString(Storage::mimeType($path));
    }

    public function testItCanGetFileLastModified()
    {
        $path = $this->storeFile();

        $this->assertIsInt(Storage::lastModified($path));
    }

    public function testItCanGetFileSize()
    {
        $path = $this->storeFile();

        $this->assertIsInt(Storage::size($path));
    }

    public function testItCanGetUrl()
    {
        $path = $this->storeFile();

        $this->assertStringStartsWith('https', Storage::url($path));
    }

    public function testItCanDeleteFile()
    {
        $path = $this->storeFile();

        $this->assertTrue(Storage::delete($path));
    }

    public function testItCanCopyFile()
    {
        $from = $this->storeFile();

        $to = $this->getRandomFilename();

        $this->assertTrue(Storage::copy($from, $to));
    }

    public function testItCanMoveFile()
    {
        $from = $this->storeFile();

        $to = $this->getRandomFilename();

        $this->assertTrue(Storage::move($from, $to));
    }

    /**
     * Store file to the filesystem disk
     *
     * @return string|false
     */
    protected function storeFile(): string|false
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
