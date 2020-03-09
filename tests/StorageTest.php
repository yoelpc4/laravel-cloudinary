<?php

namespace Yoelpc4\LaravelCloudinary\Tests;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Yoelpc4\LaravelCloudinary\Adapters\CloudinaryAdapter;

class StorageTest extends TestCase
{
    /**
     * @var File
     */
    protected $file;

    /**
     * StorageTest constructor.
     *
     * @param  null  $name
     * @param  array  $data
     * @param  string  $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->file = UploadedFile::fake()->image('test/'.Str::random().'.png', '1', '1')->size(1);
    }

    /**
     * Test for proper cloud adapter instance.
     *
     * @return void
     */
    public function testCloudAdapter()
    {
        $this->assertTrue(\Storage::cloud()->getAdapter() instanceof CloudinaryAdapter);
    }

    /**
     * Test for successful write a new file.
     *
     * @return void
     * @throws FileNotFoundException
     */
    public function testWrite()
    {
        try {
            $path = 'test/'.Str::random();

            $this->assertTrue(\Storage::cloud()->write("{$path}.png", $this->file->get()));
        } catch (FileNotFoundException $e) {
            throw $e;
        }
    }

    /**
     * Test for successful write a new file using a stream.
     *
     * @return void
     * @throws FileNotFoundException
     */
    public function testWriteStream()
    {
        try {
            $path = 'test/'.Str::random();

            $tmpFile = tmpfile();

            fwrite($tmpFile, $this->file->get());

            $this->assertTrue(\Storage::cloud()->writeStream("{$path}.png", $tmpFile));
        } catch (FileNotFoundException $e) {
            throw $e;
        }
    }

    /**
     * Test for successful update a file.
     *
     * @return void
     * @throws FileNotFoundException
     */
    public function testUpdate()
    {
        try {
            $path = 'test/'.Str::random();

            \Storage::cloud()->write("{$path}.png", $this->file->get());

            $this->assertTrue(\Storage::cloud()->update("{$path}.png", $this->file->get()));
        } catch (FileNotFoundException $e) {
            throw $e;
        }
    }

    /**
     * Test for successful update a file using a stream.
     *
     * @return void
     * @throws FileNotFoundException
     */
    public function testUpdateStream()
    {
        try {
            $path = 'test/'.Str::random();

            $tmpFile = tmpfile();

            fwrite($tmpFile, $this->file->get());

            \Storage::cloud()->writeStream("{$path}.png", $tmpFile);

            $this->assertTrue(\Storage::cloud()->updateStream("{$path}.png", $tmpFile));
        } catch (FileNotFoundException $e) {
            throw $e;
        }
    }

    /**
     * Test for successful rename a file.
     *
     * @return void
     */
    public function testRename()
    {
        $this->assertTrue(\Storage::cloud()->rename($this->upload(), Str::random().'.png'));
    }

    /**
     * Test for successful copy a file.
     *
     * @return void
     */
    public function testCopy()
    {
        $this->assertTrue(\Storage::cloud()->copy($this->upload(), Str::random().'.png'));
    }

    /**
     * Test for successful delete a file.
     *
     * @return void
     */
    public function testDelete()
    {
        $this->assertTrue(\Storage::cloud()->delete($this->upload()));
    }

    /**
     * Test for successful create and delete a directory.
     *
     * @return void
     */
    public function testCreateDir()
    {
        $this->assertTrue(\Storage::cloud()->createDir('test/'.Str::random()));
    }

    /**
     * Test for successful check whether a file exists.
     *
     * @return void
     */
    public function testHas()
    {
        $this->assertTrue(\Storage::cloud()->has($this->upload()));
    }

    /**
     * Test for successful read a file.
     *
     * @return void
     */
    public function testRead()
    {
        $this->assertIsString(\Storage::cloud()->read($this->upload()));
    }

    /**
     * Test for successful read a file as a stream.
     *
     * @return void
     */
    public function testReadStream()
    {
        \Storage::fake()->put('/fake.png', \Storage::cloud()->readStream($this->upload()));

        \Storage::assertExists('/fake.png');
    }

    /**
     * Test for successful list contents of a directory.
     *
     * @return void
     */
    public function testListContents()
    {
        $path = 'test/'.Str::random();

        $this->upload($path);

        $this->assertIsArray(\Storage::cloud()->listContents($path));
    }

    /**
     * Test for successful get all the meta data of a file or directory.
     *
     * @return void
     */
    public function testGetMetadata()
    {
        $this->assertIsArray(\Storage::cloud()->getMetadata($this->upload()));
    }

    /**
     * Test for successful get the size of a file.
     *
     * @return void
     */
    public function testGetSize()
    {
        $this->assertIsInt(\Storage::cloud()->getSize($this->upload()));
    }

    /**
     * Test for successful get the mimetype of a file.
     *
     * @return void
     */
    public function testGetMimetype()
    {
        $this->assertIsString(\Storage::cloud()->getMimetype($this->upload()));
    }

    /**
     * Test for successful get the timestamp of a file.
     *
     * @return void
     */
    public function testGetTimestamp()
    {
        $this->assertIsInt(\Storage::cloud()->getTimestamp($this->upload()));
    }

    /**
     * Test for successful get the URL for the file at the given path.
     *
     * @return void
     */
    public function testUrl()
    {
        $this->assertStringStartsWith('http', \Storage::cloud()->url($this->upload()));
    }

    /**
     * Upload mock file to storage
     *
     * @param  string  $path
     * @return false|string
     */
    protected function upload($path = 'test')
    {
        return $this->file->store($path, \Config::get('filesystems.cloud'));
    }
}
