<?php

namespace Yoelpc4\LaravelCloudinary\Tests;

use Illuminate\Contracts\Filesystem\FileExistsException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Yoelpc4\LaravelCloudinary\CloudinaryServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * @var File
     */
    protected $file;

    /**
     * Get resource extension
     *
     * @return string
     */
    abstract protected function extension();

    /**
     * Get resource directory
     *
     * @return string
     */
    abstract protected function directory();

    /**
     * @inheritDoc
     */
    protected function getPackageProviders($app)
    {
        return [
            CloudinaryServiceProvider::class,
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getEnvironmentSetUp($app)
    {
        $app->useEnvironmentPath(__DIR__.'/../../laravel-cloudinary')
            ->loadEnvironmentFrom('.env.testing')
            ->bootstrapWith([
                LoadEnvironmentVariables::class,
            ]);

        $app['config']->set('filesystems', [
            'default' => env('FILESYSTEM_DRIVER'),
            'disks'   => [
                'cloudinary' => [
                    'driver'         => 'cloudinary',
                    'api_key'        => env('CLOUDINARY_API_KEY'),
                    'api_secret'     => env('CLOUDINARY_API_SECRET'),
                    'cloud_name'     => env('CLOUDINARY_CLOUD_NAME'),
                    'secure'         => env('CLOUDINARY_SECURE', true),
                    'resource_types' => [
                        'image' => [
                            'png',
                        ],
                        'video' => [],
                        'raw'   => [
                            'pdf',
                        ],
                    ],
                ],
            ],
        ]);
    }

    /** @test */
    public function testItHasRootPath()
    {
        $this->assertIsString(Storage::path('/'));
    }

    /** @test */
    public function testItCanWriteFile()
    {
        $path = $this->getRandomPath($this->extension());

        try {
            $contents = $this->file->get();
        } catch (FileNotFoundException $e) {
            throw $e;
        }

        $this->assertTrue(Storage::write($path, $contents));
    }

    /**
     * @test
     * @throws FileExistsException
     * @throws FileNotFoundException
     */
    public function testItCanWriteStreamFile()
    {
        $path = $this->getRandomPath($this->extension());

        $isUpdated = false;

        $tmpFile = tmpfile();

        try {
            if (fwrite($tmpFile, $this->file->get())) {
                $isUpdated = Storage::writeStream($path, $tmpFile);
            }
        } catch (FileExistsException $e) {
            throw $e;
        } catch (FileNotFoundException $e) {
            throw $e;
        }

        $this->assertTrue($isUpdated);
    }

    /**
     * @test
     * @throws FileNotFoundException
     */
    public function testItCanUpdateFile()
    {
        $isUpdated = false;

        $path = $this->getRandomPath($this->extension());

        try {
            $contents = $this->file->get();
        } catch (FileNotFoundException $e) {
            throw $e;
        }

        if (Storage::write($path, $contents)) {
            $isUpdated = Storage::update($path, $contents);
        }

        $this->assertTrue($isUpdated);
    }

    /**
     * @test
     * @throws FileNotFoundException
     * @throws FileExistsException
     */
    public function testItCanUpdateStreamFile()
    {
        $isUpdated = false;

        $path = $this->getRandomPath($this->extension());

        $tmpFile = tmpfile();

        try {
            $contents = $this->file->get();
        } catch (FileNotFoundException $e) {
            throw $e;
        }

        if (fwrite($tmpFile, $contents)) {
            if (Storage::writeStream($path, $tmpFile)) {
                $isUpdated = Storage::updateStream($path, $tmpFile);
            }
        }

        $this->assertTrue($isUpdated);
    }

    /** @test */
    public function testItCanRenameFile()
    {
        $from = $this->storeFile();

        $to = $this->getRandomFilename();

        $this->assertTrue(Storage::rename($from, $to));
    }

    /** @test */
    public function testItCanCopyFile()
    {
        $from = $this->storeFile();

        $to = $this->getRandomFilename();

        $this->assertTrue(Storage::copy($from, $to));
    }

    /** @test */
    public function testItCanDeleteFile()
    {
        $path = $this->storeFile();

        $this->assertTrue(Storage::delete($path));
    }

    /** @test */
    public function testItCanCreateDir()
    {
        $path = $this->getRandomPath();

        $this->assertTrue(Storage::createDir($path));
    }

    /** @test */
    public function testItHasFile()
    {
        $path = $this->storeFile();

        $this->assertTrue(Storage::has($path));
    }

    /** @test */
    public function testItCanReadFile()
    {
        $path = $this->storeFile();

        $this->assertIsString(Storage::read($path));
    }

    /** @test */
    public function testItCanReadStreamFile()
    {
        $path = $this->storeFile();

        $contents = Storage::readStream($path);

        $otherPath = $this->getRandomFilename();

        Storage::fake()->put($otherPath, $contents);

        Storage::assertExists($otherPath);
    }

    /** @test */
    public function testItCanListContentsFile()
    {
        $path = $this->getRandomPath();

        $this->storeFile();

        $this->assertIsArray(Storage::listContents($path));
    }

    /** @test */
    public function testItCanGetMetadataFile()
    {
        $path = $this->storeFile();

        $this->assertIsArray(Storage::getMetadata($path));
    }

    /** @test */
    public function testItCanGetSizeFile()
    {
        $path = $this->storeFile();

        $this->assertIsInt(Storage::getSize($path));
    }

    /** @test */
    public function testItCanGetMimetypeFile()
    {
        $path = $this->storeFile();

        $this->assertIsString(Storage::getMimetype($path));
    }

    /** @test */
    public function testItCanGetTimestampFile()
    {
        $path = $this->storeFile();

        $this->assertIsInt(Storage::getTimestamp($path));
    }

    /** @test */
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
        $path = "test/{$this->directory()}";

        $disk = config('filesystems.default');

        return $this->file->store($path, $disk);
    }

    /**
     * Get random filename
     *
     * @return string
     */
    protected function getRandomFilename()
    {
        return Str::random().".{$this->extension()}";
    }

    /**
     * Get random path
     *
     * @param  string|null  $extension
     * @return string
     */
    protected function getRandomPath(string $extension = null)
    {
        $path = "test/{$this->directory()}/".Str::random();

        return $extension ? "{$path}.{$extension}" : $path;
    }
}
