<?php

namespace Yoelpc4\LaravelCloudinary;

use League\Flysystem\FilesystemException;
use RuntimeException;
use Throwable;

class UnableToListContents extends RuntimeException implements FilesystemException
{
    private string $location = '';

    private string $reason = '';

    public static function fromLocation(string $location, string $reason = '', Throwable $previous = null): static
    {
        $e = new static(rtrim("Unable to list contents from location: {$location}. {$reason}"), 0, $previous);
        $e->location = $location;
        $e->reason = $reason;

        return $e;
    }

    public function operation(): string
    {
        return 'LIST_CONTENTS';
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function location(): string
    {
        return $this->location;
    }
}
