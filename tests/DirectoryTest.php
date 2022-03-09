<?php

namespace Yoelpc4\LaravelCloudinary\Tests;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DirectoryTest extends TestCase
{
    public function testItCanCreateDirectory()
    {
        $this->assertTrue(Storage::makeDirectory('test/' . Str::random()));
    }

    public function testIsDirectoryExists()
    {
        $path = 'test/' . Str::random();

        Storage::makeDirectory($path);

        $this->assertTrue(Storage::directoryExists($path));
    }

    public function testItCanDeleteDirectory()
    {
        $path = 'test/' . Str::random();

        Storage::makeDirectory($path);

        $this->assertTrue(Storage::deleteDirectory($path));
    }
}
