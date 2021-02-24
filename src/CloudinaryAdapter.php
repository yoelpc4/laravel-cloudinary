<?php

namespace Yoelpc4\LaravelCloudinary;

use Cloudinary;
use Cloudinary\Api;
use Cloudinary\Api\BadRequest;
use Cloudinary\Api\GeneralError;
use Cloudinary\Uploader;
use Exception;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;

class CloudinaryAdapter extends AbstractAdapter implements AdapterInterface
{
    use NotSupportingVisibilityTrait;

    /**
     * Cloudinary api
     *
     * @var Api
     */
    protected $api;

    /**
     * CloudinaryAdapter constructor.
     *
     * @param  array  $options
     * @param  Api  $api
     */
    public function __construct(array $options, Api $api)
    {
        Cloudinary::config($options);

        $this->api = $api;
    }

    /**
     * @inheritDoc
     */
    public function write($path, $contents, Config $config)
    {
        $tmpFile = tmpfile();

        if (fwrite($tmpFile, $contents)) {
            return $this->writeStream($path, $tmpFile, $config);
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function writeStream($path, $resource, Config $config)
    {
        $metadata = stream_get_meta_data($resource);

        $options = [
            'public_id'       => $this->getPublicId($path),
            'use_filename'    => true,
            'unique_filename' => false,
            'resource_type'   => $this->getResourceType($path),
        ];

        return Uploader::upload($metadata['uri'], $options);
    }

    /**
     * @inheritDoc
     */
    public function update($path, $contents, Config $config)
    {
        return $this->write($path, $contents, $config);
    }

    /**
     * @inheritDoc
     */
    public function updateStream($path, $resource, Config $config)
    {
        return $this->writeStream($path, $resource, $config);
    }

    /**
     * @inheritDoc
     */
    public function rename($path, $newpath)
    {
        $fromPublicId = $this->getPublicId($path);

        $toPublicId = $this->getPublicId($newpath);

        $options = [
            'resource_type' => $this->getResourceType($newpath),
        ];

        $result = Uploader::rename($fromPublicId, $toPublicId, $options);

        return is_array($result) ? $result['public_id'] === $toPublicId : false;
    }

    /**
     * @inheritDoc
     */
    public function copy($path, $newpath)
    {
        $url = $this->getUrl($path);

        $options = [
            'public_id'     => $this->getPublicId($newpath),
            'resource_type' => $this->getResourceType($newpath),
        ];

        $result = Uploader::upload($url, $options);

        return is_array($result) ? $result['public_id'] === $this->getPublicId($newpath) : false;
    }

    /**
     * @inheritDoc
     */
    public function delete($path)
    {
        $options = [
            'resource_type' => $this->getResourceType($path),
            'invalidate'    => true,
        ];

        $result = Uploader::destroy($this->getPublicId($path), $options);

        return is_array($result) ? $result['result'] === 'ok' : false;
    }

    /**
     * @inheritDoc
     */
    public function createDir($dirname, Config $config)
    {
        try {
            return $this->api->create_folder($dirname);
        } catch (BadRequest $e) {
            return false;
        } catch (GeneralError $e) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function deleteDir($dirname)
    {
        try {
            $this->api->delete_folder($dirname);
        } catch (BadRequest $e) {
            return false;
        } catch (GeneralError $e) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function has($path)
    {
        $options = [
            'resource_type' => $this->getResourceType($path),
        ];

        try {
            $this->api->resource($this->getPublicId($path), $options);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function read($path)
    {
        $contents = file_get_contents($this->getUrl($path));

        return compact('contents', 'path');
    }

    /**
     * @inheritDoc
     */
    public function readStream($path)
    {
        try {
            $stream = fopen($this->getUrl($path), 'r');
        } catch (Exception $e) {
            return false;
        }

        return compact('stream', 'path');
    }

    /**
     * @inheritDoc
     * @throws GeneralError
     */
    public function listContents($directory = '', $recursive = false)
    {
        $resources = [];

        $response = null;

        do {
            try {
                $response = (array) $this->api->resources([
                    'type'        => 'upload',
                    'prefix'      => $directory,
                    'max_results' => 500,
                    'next_cursor' => isset($response['next_cursor']) ? $response['next_cursor'] : null,
                ]);
            } catch (GeneralError $e) {
                throw $e;
            }

            $resources = array_merge($resources, $response['resources']);
        } while (array_key_exists('next_cursor', $response));

        foreach ($resources as $index => $resource) {
            $resources[$index] = $this->prepareResourceMetadata($resource);
        }

        return $resources;
    }

    /**
     * @inheritDoc
     */
    public function getMetadata($path)
    {
        try {
            return $this->prepareResourceMetadata($this->getResource($path));
        } catch (GeneralError $e) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function getSize($path)
    {
        try {
            return $this->prepareSize($this->getResource($path));
        } catch (GeneralError $e) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function getMimetype($path)
    {
        try {
            return $this->prepareMimetype($this->getResource($path));
        } catch (GeneralError $e) {
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function getTimestamp($path)
    {
        try {
            return $this->prepareTimestamp($this->getResource($path));
        } catch (GeneralError $e) {
            return false;
        }
    }

    /**
     * Get the resource of a file
     *
     * @param  string  $path
     * @return array
     * @throws GeneralError
     */
    public function getResource($path)
    {
        $publicId = $this->getPublicId($path);

        $options = [
            'resource_type' => $this->getResourceType($path),
        ];

        try {
            return (array) $this->api->resource($publicId, $options);
        } catch (GeneralError $e) {
            throw $e;
        }
    }

    /**
     * Get the URL for the file at the given path.
     *
     * @param  string|array  $path
     * @return string
     */
    public function getUrl($path)
    {
        $options = [
            'secure' => config('filesystems.disks.cloudinary.secure'),
        ];

        if (is_array($path)) {
            foreach ($path as $key => $value) {
                $options[$key] = $value;
            }

            unset($options['path']);

            $path = $path['path'];
        }

        $options['resource_type'] = $this->getResourceType($path);

        return cloudinary_url($path, $options);
    }

    /**
     * Prepare cloudinary resource metadata
     *
     * @param  array  $resource
     * @return array
     */
    protected function prepareResourceMetadata(array $resource)
    {
        $resource['type'] = 'file';

        $resource['path'] = $resource['public_id'];

        $resource = array_merge($resource, $this->prepareSize($resource));

        $resource = array_merge($resource, $this->prepareTimestamp($resource));

        $resource = array_merge($resource, $this->prepareMimetype($resource));

        return $resource;
    }

    /**
     * Prepare cloudinary resource mimetype
     *
     * @param  array  $resource
     * @return array
     */
    protected function prepareMimetype(array $resource)
    {
        $format = isset($resource['format']) ? "/{$resource['format']}" : '';

        return [
            'mimetype' => str_replace('jpg', 'jpeg', "{$resource['resource_type']}{$format}"),
        ];
    }

    /**
     * Prepare cloudinary resource timestamp
     *
     * @param  array  $resource
     * @return array
     */
    protected function prepareTimestamp(array $resource)
    {
        return [
            'timestamp' => strtotime($resource['created_at']),
        ];
    }

    /**
     * Prepare cloudinary resource size
     *
     * @param  array  $resource
     * @return array
     */
    protected function prepareSize(array $resource)
    {
        return [
            'size' => $resource['bytes'],
        ];
    }

    /**
     * Get cloudinary resource type
     *
     * @param  string  $path
     * @return string
     * @see https://cloudinary.com/documentation/image_upload_api_reference#upload_method
     */
    protected function getResourceType(string $path)
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        $value = 'image';

        $resourceTypes = config('filesystems.disks.cloudinary.resource_types', []);

        foreach ($resourceTypes as $resourceType => $extensions) {
            if (in_array($extension, $extensions)) {
                $value = $resourceType;

                break;
            }
        }

        return $value;
    }

    /**
     * Get cloudinary public id
     *
     * @param  string  $path
     * @return string
     */
    protected function getPublicId(string $path)
    {
        $basename = pathinfo($path, PATHINFO_BASENAME);

        $dirname = pathinfo($path, PATHINFO_DIRNAME);

        $filename = pathinfo($path, PATHINFO_FILENAME);

        // for raw resource type use basename as filename
        if ($this->getResourceType($path) === 'raw') {
            $filename = $basename;
        }

        // if directory exists prepends with dirname
        return $dirname != '.' ? "{$dirname}/{$filename}" : $filename;
    }
}
