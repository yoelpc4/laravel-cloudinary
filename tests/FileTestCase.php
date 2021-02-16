<?php

namespace Yoelpc4\LaravelCloudinary\Tests;

use Illuminate\Contracts\Filesystem\FileExistsException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Yoelpc4\LaravelCloudinary\Tests\Mocks\Mockable;

abstract class FileTestCase extends TestCase
{
    /**
     * @var File
     */
    protected $file;

    /**
     * FileTestCase constructor.
     *
     * @param  null  $name
     * @param  array  $data
     * @param  string  $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->file = $this->mockFile()->make($this->randomPath($this->extension()));
    }

    /**
     * Get mock instance
     *
     * @return Mockable
     */
    abstract protected function mockFile();

    /**
     * Get file extension
     *
     * @return string
     */
    abstract protected function extension();

    /**
     * Get directory
     *
     * @return string
     */
    abstract protected function directory();

    /** @test */
    public function testItHasRootPath()
    {
        $this->assertIsString(Storage::path('/'));
    }

    /** @test */
    public function testItCanWriteFile()
    {
        try {
            $this->assertTrue(Storage::write($this->randomPath($this->extension()), $this->file->get()));
        } catch (FileNotFoundException $e) {
            throw $e;
        }
    }

    /** @test */
    public function testItCanWriteStreamFile()
    {
        $isUpdated = false;

        $tmpFile = tmpfile();

        try {
            if (fwrite($tmpFile, $this->file->get())) {
                $isUpdated = Storage::writeStream($this->randomPath($this->extension()), $tmpFile);
            }
        } catch (FileExistsException $e) {
            throw $e;
        } catch (FileNotFoundException $e) {
            throw $e;
        }

        $this->assertTrue($isUpdated);
    }

    /** @test */
    public function testItCanUpdateFile()
    {
        $isUpdated = false;

        $path = $this->randomPath($this->extension());

        try {
            if (Storage::write($path, $this->file->get())) {
                $isUpdated = Storage::update($path, $this->file->get());
            }
        } catch (FileNotFoundException $e) {
            throw $e;
        }

        $this->assertTrue($isUpdated);
    }

    /** @test */
    public function testItCanUpdateStreamFile()
    {
        $isUpdated = false;

        $path = $this->randomPath($this->extension());

        $tmpFile = tmpfile();

        try {
            if (fwrite($tmpFile, $this->file->get())) {
                if (Storage::writeStream($path, $tmpFile)) {
                    $isUpdated = Storage::updateStream($path, $tmpFile);
                }
            }
        } catch (FileExistsException $e) {
            throw $e;
        } catch (FileNotFoundException $e) {
            throw $e;
        }

        $this->assertTrue($isUpdated);
    }

    /** @test */
    public function testItCanRenameFile()
    {
        $this->assertTrue(Storage::rename($this->storeFile(), Str::random().'.'.$this->extension()));
    }

    /** @test */
    public function testItCanCopyFIle()
    {
        $this->assertTrue(Storage::copy($this->storeFile(), Str::random().'.'.$this->extension()));
    }

    /** @test */
    public function testItCanDeleteFile()
    {
        $this->assertTrue(Storage::delete($this->storeFile()));
    }

    /** @test */
    public function testItCanCreateDir()
    {
        $this->assertTrue(Storage::createDir($this->randomPath()));
    }

    /** @test */
    public function testItHasFile()
    {
        $this->assertTrue(Storage::has($this->storeFile()));
    }

    /** @test */
    public function testItCanReadFile()
    {
        $this->assertIsString(Storage::read($this->storeFile()));
    }

    /** @test */
    public function testItCanReadStreamFile()
    {
        try {
            Storage::fake()->put('/fake.png', Storage::readStream($this->storeFile()));
        } catch (FileNotFoundException $e) {
            throw $e;
        }

        Storage::assertExists('/fake.png');
    }

    /** @test */
    public function testItCanListContentsFile()
    {
        $path = $this->randomPath();

        $this->storeFile();

        $this->assertIsArray(Storage::listContents($path));
    }

    /** @test */
    public function testItCanGetMetadataFile()
    {
        $this->assertIsArray(Storage::getMetadata($this->storeFile()));
    }

    /** @test */
    public function testItCanGetSizeFile()
    {
        $this->assertIsInt(Storage::getSize($this->storeFile()));
    }

    /** @test */
    public function testItCanGetMimetypeFile()
    {
        $this->assertIsString(Storage::getMimetype($this->storeFile()));
    }

    /** @test */
    public function testItCanGetTimestampFile()
    {
        $this->assertIsInt(Storage::getTimestamp($this->storeFile()));
    }

    /** @test */
    public function testItCanGetFileUrl()
    {
        $this->assertStringStartsWith('http', Storage::url($this->storeFile()));
    }

    /**
     * Store the uploaded file to the cloud storage
     *
     * @return false|string
     */
    protected function storeFile()
    {
        return $this->file->store("test/{$this->directory()}", config('filesystems.default'));
    }

    /**
     * Get random path
     *
     * @param  string|null  $extension
     * @return string
     */
    protected function randomPath(string $extension = null)
    {
        $path = "test/{$this->directory()}/".Str::random();

        if ($extension) {
            return "{$path}.{$extension}";
        }

        return $path;
    }
}
