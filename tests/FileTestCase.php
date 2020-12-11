<?php

namespace Yoelpc4\LaravelCloudinary\Tests;

use Illuminate\Contracts\Filesystem\FileExistsException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Yoelpc4\LaravelCloudinary\Adapters\CloudinaryAdapter;
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

    /**
     */
    public function testCloudAdapter()
    {
        $this->assertInstanceOf(CloudinaryAdapter::class, Storage::cloud()->getAdapter());
    }

    /**
     */
    public function testPath()
    {
        $this->assertIsString(Storage::cloud()->path('/'));
    }

    /**
     * @throws FileNotFoundException
     */
    public function testWrite()
    {
        try {
            $this->assertTrue(Storage::cloud()->write($this->randomPath($this->extension()), $this->file->get()));
        } catch (FileNotFoundException $e) {
            throw $e;
        }
    }

    /**
     * @throws FileExistsException
     * @throws FileNotFoundException
     */
    public function testWriteStream()
    {
        $isUpdated = false;

        $tmpFile = tmpfile();

        try {
            if (fwrite($tmpFile, $this->file->get())) {
                $isUpdated = Storage::cloud()->writeStream($this->randomPath($this->extension()), $tmpFile);
            }
        } catch (FileExistsException $e) {
            throw $e;
        } catch (FileNotFoundException $e) {
            throw $e;
        }

        $this->assertTrue($isUpdated);
    }

    /**
     * @throws FileNotFoundException
     */
    public function testUpdate()
    {
        $isUpdated = false;

        $path = $this->randomPath($this->extension());

        try {
            if (Storage::cloud()->write($path, $this->file->get())) {
                $isUpdated = Storage::cloud()->update($path, $this->file->get());
            }
        } catch (FileNotFoundException $e) {
            throw $e;
        }

        $this->assertTrue($isUpdated);
    }

    /**
     * @throws FileExistsException
     * @throws FileNotFoundException
     */
    public function testUpdateStream()
    {
        $isUpdated = false;

        $path = $this->randomPath($this->extension());

        $tmpFile = tmpfile();

        try {
            if (fwrite($tmpFile, $this->file->get())) {
                if (Storage::cloud()->writeStream($path, $tmpFile)) {
                    $isUpdated = Storage::cloud()->updateStream($path, $tmpFile);
                }
            }
        } catch (FileExistsException $e) {
            throw $e;
        } catch (FileNotFoundException $e) {
            throw $e;
        }

        $this->assertTrue($isUpdated);
    }

    /**
     */
    public function testRename()
    {
        $this->assertTrue(Storage::cloud()->rename($this->storeFile(), Str::random().'.'.$this->extension()));
    }

    /**
     */
    public function testCopy()
    {
        $this->assertTrue(Storage::cloud()->copy($this->storeFile(), Str::random().'.'.$this->extension()));
    }

    /**
     */
    public function testDelete()
    {
        $this->assertTrue(Storage::cloud()->delete($this->storeFile()));
    }

    /**
     */
    public function testCreateDir()
    {
        $this->assertTrue(Storage::cloud()->createDir($this->randomPath()));
    }

    /**
     */
    public function testHas()
    {
        $this->assertTrue(Storage::cloud()->has($this->storeFile()));
    }

    /**
     */
    public function testRead()
    {
        $this->assertIsString(Storage::cloud()->read($this->storeFile()));
    }

    /**
     * @throws FileNotFoundException
     */
    public function testReadStream()
    {
        try {
            Storage::fake()->put('/fake.png', Storage::cloud()->readStream($this->storeFile()));
        } catch (FileNotFoundException $e) {
            throw $e;
        }

        Storage::assertExists('/fake.png');
    }

    /**
     */
    public function testListContents()
    {
        $path = $this->randomPath();

        $this->storeFile();

        $this->assertIsArray(Storage::cloud()->listContents($path));
    }

    /**
     */
    public function testGetMetadata()
    {
        $this->assertIsArray(Storage::cloud()->getMetadata($this->storeFile()));
    }

    /**
     */
    public function testGetSize()
    {
        $this->assertIsInt(Storage::cloud()->getSize($this->storeFile()));
    }

    /**
     */
    public function testGetMimetype()
    {
        $this->assertIsString(Storage::cloud()->getMimetype($this->storeFile()));
    }

    /**
     */
    public function testGetTimestamp()
    {
        $this->assertIsInt(Storage::cloud()->getTimestamp($this->storeFile()));
    }

    /**
     */
    public function testUrl()
    {
        $this->assertStringStartsWith('http', Storage::cloud()->url($this->storeFile()));
    }

    /**
     * Store the uploaded file to the cloud storage
     *
     * @return false|string
     */
    protected function storeFile()
    {
        return $this->file->store("test/{$this->directory()}", config('filesystems.cloud'));
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
