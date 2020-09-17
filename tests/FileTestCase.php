<?php

namespace Yoelpc4\LaravelCloudinary\Tests;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Str;
use Yoelpc4\LaravelCloudinary\Adapters\CloudinaryAdapter;
use Yoelpc4\LaravelCloudinary\Mocks\Mockable;

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
     * Test for proper cloud adapter instance.
     *
     */
    public function testCloudAdapter()
    {
        $this->assertTrue(\Storage::cloud()->getAdapter() instanceof CloudinaryAdapter);
    }

    /**
     * Test for successful get the full path for the file at the given "short" path.
     *
     */
    public function testPath()
    {
        $this->assertIsString(\Storage::cloud()->path('/'));
    }

    /**
     * Test for successful write a new file.
     *
     * @throws FileNotFoundException
     */
    public function testWrite()
    {
        try {
            $this->assertTrue(\Storage::cloud()->write($this->randomPath($this->extension()), $this->file->get()));
        } catch (FileNotFoundException $e) {
            throw $e;
        }
    }

    /**
     * Test for successful write a new file using a stream.
     *
     * @throws FileNotFoundException
     */
    public function testWriteStream()
    {
        $isUpdated = false;

        try {
            $tmpFile = tmpfile();

            if (fwrite($tmpFile, $this->file->get())) {
                $isUpdated = \Storage::cloud()->writeStream($this->randomPath($this->extension()), $tmpFile);
            }

            $this->assertTrue($isUpdated);
        } catch (FileNotFoundException $e) {
            throw $e;
        }
    }

    /**
     * Test for successful update a file.
     *
     * @throws FileNotFoundException
     */
    public function testUpdate()
    {
        $isUpdated = false;

        $path = $this->randomPath($this->extension());

        try {
            if (\Storage::cloud()->write($path, $this->file->get())) {
                $isUpdated = \Storage::cloud()->update($path, $this->file->get());
            }
        } catch (FileNotFoundException $e) {
            throw $e;
        }

        $this->assertTrue($isUpdated);
    }

    /**
     * Test for successful update a file using a stream.
     *
     * @throws FileNotFoundException
     */
    public function testUpdateStream()
    {
        $isUpdated = false;

        $path = $this->randomPath($this->extension());

        try {
            $tmpFile = tmpfile();

            if (fwrite($tmpFile, $this->file->get())) {
                if (\Storage::cloud()->writeStream($path, $tmpFile)) {
                    $isUpdated = \Storage::cloud()->updateStream($path, $tmpFile);
                }
            }

            $this->assertTrue($isUpdated);
        } catch (FileNotFoundException $e) {
            throw $e;
        }
    }

    /**
     * Test for successful rename a file.
     *
     */
    public function testRename()
    {
        $this->assertTrue(\Storage::cloud()->rename($this->upload(), Str::random().'.'.$this->extension()));
    }

    /**
     * Test for successful copy a file.
     *
     */
    public function testCopy()
    {
        $this->assertTrue(\Storage::cloud()->copy($this->upload(), Str::random().'.'.$this->extension()));
    }

    /**
     * Test for successful delete a file.
     *
     */
    public function testDelete()
    {
        $this->assertTrue(\Storage::cloud()->delete($this->upload()));
    }

    /**
     * Test for successful create and delete a directory.
     *
     */
    public function testCreateDir()
    {
        $this->assertTrue(\Storage::cloud()->createDir($this->randomPath()));
    }

    /**
     * Test for successful check whether a file exists.
     *
     */
    public function testHas()
    {
        $this->assertTrue(\Storage::cloud()->has($this->upload()));
    }

    /**
     * Test for successful read a file.
     *
     */
    public function testRead()
    {
        $this->assertIsString(\Storage::cloud()->read($this->upload()));
    }

    /**
     * Test for successful read a file as a stream.
     *
     */
    public function testReadStream()
    {
        \Storage::fake()->put('/fake.png', \Storage::cloud()->readStream($this->upload()));

        \Storage::assertExists('/fake.png');
    }

    /**
     * Test for successful list contents of a directory.
     *
     */
    public function testListContents()
    {
        $path = $this->randomPath();

        $this->upload($path);

        $this->assertIsArray(\Storage::cloud()->listContents($path));
    }

    /**
     * Test for successful get all the meta data of a file or directory.
     *
     */
    public function testGetMetadata()
    {
        $this->assertIsArray(\Storage::cloud()->getMetadata($this->upload()));
    }

    /**
     * Test for successful get the size of a file.
     *
     */
    public function testGetSize()
    {
        $this->assertIsInt(\Storage::cloud()->getSize($this->upload()));
    }

    /**
     * Test for successful get the mimetype of a file.
     *
     */
    public function testGetMimetype()
    {
        $this->assertIsString(\Storage::cloud()->getMimetype($this->upload()));
    }

    /**
     * Test for successful get the timestamp of a file.
     *
     */
    public function testGetTimestamp()
    {
        $this->assertIsInt(\Storage::cloud()->getTimestamp($this->upload()));
    }

    /**
     * Test for successful get the URL for the file at the given path.
     *
     */
    public function testUrl()
    {
        $this->assertStringStartsWith('http', \Storage::cloud()->url($this->upload()));
    }

    /**
     * Upload file to cloud storage
     *
     * @return false|string
     */
    protected function upload()
    {
        return $this->file->store("test/{$this->directory()}", config('filesystems.cloud'));
    }

    /**
     * Get random path
     *
     * @param  string  $extension
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
