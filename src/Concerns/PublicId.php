<?php

namespace Yoelpc4\LaravelCloudinary\Concerns;

class PublicId implements ConcernAware
{
    /**
     * @var ResourceType
     */
    protected $resourceType;

    /**
     * @var string
     */
    protected $publicId;

    /**
     * @var string
     */
    protected $basename;

    /**
     * @var string
     */
    protected $filename;

    /**
     * @var string
     */
    protected $dirname;

    /**
     * PublicId constructor.
     *
     * @param  ResourceType  $resourceType
     * @param  string  $basename
     * @param  string  $filename
     * @param  string  $dirname
     * @see https://cloudinary.com/documentation/image_upload_api_reference#upload_method
     */
    public function __construct(ResourceType $resourceType, string $basename, string $filename, string $dirname)
    {
        $this->resourceType = $resourceType;

        $this->basename = $basename;

        $this->filename = $filename;

        $this->dirname = $dirname;
    }

    /**
     * @inheritDoc
     */
    public function handle()
    {
        // for raw resource type use basename otherwise filename
        $filename = $this->resourceType->value() === 'raw' ? $this->basename : $this->filename;

        // if directory exists prepends with dirname
        if ($this->dirname != '.') {
            $this->publicId = "{$this->dirname}/{$filename}";
        } else {
            $this->publicId = $filename;
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function value()
    {
        return $this->publicId;
    }
}
