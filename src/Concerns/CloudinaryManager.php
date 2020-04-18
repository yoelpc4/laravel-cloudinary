<?php

namespace Yoelpc4\LaravelCloudinary\Concerns;

class CloudinaryManager
{
    /**
     * @var ResourceType
     */
    protected $resourceType;

    /**
     * @var PublicId
     */
    protected $publicId;

    /**
     * Initialize manager
     *
     * @param  string  $path
     * @return $this
     */
    public function init(string $path)
    {
        $info = pathinfo($path);

        $this->resourceType = (new ResourceType($info['extension']))->handle();

        $this->publicId = (new PublicId($this->resourceType, $info['basename'], $info['filename'], $info['dirname']))->handle();

        return $this;
    }

    /**
     * Get resource type value
     *
     * @return string
     */
    public function getResourceType()
    {
        return $this->resourceType->value();
    }

    /**
     * Get public id value
     *
     * @return string
     */
    public function getPublicId()
    {
        return $this->publicId->value();
    }
}
