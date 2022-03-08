<?php

namespace Yoelpc4\LaravelCloudinary\Tests;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DirectoryTest extends TestCase
{
    /** @test */
    public function testItCanCreateDir()
    {
        $this->assertTrue(Storage::createDir('test/' . Str::random()));
    }

    /** @test */
    public function testItCanDeleteDir()
    {
        $path = 'test/' . Str::random();

        Storage::createDir($path);

        $this->assertTrue(Storage::deleteDir($path));
    }
}
