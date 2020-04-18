<?php

namespace Yoelpc4\LaravelCloudinary\Adapters;

use Cloudinary\Api;
use Cloudinary\Api\BadRequest;
use Cloudinary\Api\GeneralError;
use Cloudinary\Uploader;
use Exception;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use Yoelpc4\LaravelCloudinary\Concerns\CloudinaryManager;

class CloudinaryAdapter extends AbstractAdapter implements AdapterInterface
{
    use NotSupportingVisibilityTrait;

    /**
     * The cloudinary api instance
     *
     * @var Api
     */
    protected $api;

    /**
     * @var CloudinaryManager
     */
    protected $cloudinaryManager;

    /**
     * CloudinaryAdapter constructor.
     *
     * @param  array  $options
     */
    public function __construct(array $options)
    {
        \Cloudinary::config($options);

        $this->api = new Api;

        $this->cloudinaryManager = new CloudinaryManager;
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

        $this->cloudinaryManager->init($path);

        $options = [
            'public_id'       => $this->cloudinaryManager->getPublicId(),
            'use_filename'    => true,
            'unique_filename' => false,
            'resource_type'   => $this->cloudinaryManager->getResourceType(),
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
        $fromPublicId = $this->cloudinaryManager->init($path)->getPublicId();

        $toPublicId = $this->cloudinaryManager->init($newpath)->getPublicId();

        $options = [
            'resource_type' => $this->cloudinaryManager->getResourceType(),
        ];

        $result = Uploader::rename($fromPublicId, $toPublicId, $options);

        return $result['public_id'] == $toPublicId;
    }

    /**
     * @inheritDoc
     */
    public function copy($path, $newpath)
    {
        $this->cloudinaryManager->init($newpath);

        $options = [
            'public_id'     => $this->cloudinaryManager->getPublicId(),
            'resource_type' => $this->cloudinaryManager->getResourceType(),
        ];

        $result = Uploader::upload($this->getUrl($path), $options);

        return is_array($result) ? $result['public_id'] === $newpath : false;
    }

    /**
     * @inheritDoc
     */
    public function delete($path)
    {
        $this->cloudinaryManager->init($path);

        $options = [
            'resource_type' => $this->cloudinaryManager->getResourceType(),
            'invalidate'    => true,
        ];

        $result = Uploader::destroy($this->cloudinaryManager->getPublicId(), $options);

        return is_array($result) ? $result['result'] == 'ok' : false;
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
     * @inheritoc
     */
    public function has($path)
    {
        $this->cloudinaryManager->init($path);

        $options = [
            'resource_type' => $this->cloudinaryManager->getResourceType(),
        ];

        try {
            $this->api->resource($this->cloudinaryManager->getPublicId(), $options);
        } catch (GeneralError $e) {
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

        foreach ($resources as $i => $resource) {
            $resources[$i] = $this->prepareResourceMetadata($resource);
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
        $this->cloudinaryManager->init($path);

        $options = [
            'resource_type' => $this->cloudinaryManager->getResourceType(),
        ];

        try {
            return (array) $this->api->resource($this->cloudinaryManager->getPublicId(), $options);
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
            'secure' => \Config::get('filesystems.disks.cloudinary.secure'),
        ];

        if (is_array($path)) {
            foreach ($path as $key => $value) {
                $options[$key] = $value;
            }

            unset($options['path']);

            $path = $path['path'];
        }

        $options['resource_type'] = $this->cloudinaryManager->init($path)->getResourceType();

        return cloudinary_url($path, $options);
    }

    /**
     * Prepare appropriate metadata for resource metadata given from cloudinary
     *
     * @param  array  $resource
     * @return array
     */
    protected function prepareResourceMetadata($resource)
    {
        $resource['type'] = 'file';

        $resource['path'] = $resource['public_id'];

        $resource = array_merge($resource, $this->prepareSize($resource));

        $resource = array_merge($resource, $this->prepareTimestamp($resource));

        $resource = array_merge($resource, $this->prepareMimetype($resource));

        return $resource;
    }

    /**
     * Prepare timestamp response
     *
     * @param  array  $resource
     * @return array
     */
    protected function prepareMimetype($resource)
    {
        $format = isset($resource['format']) ? "/{$resource['format']}" : '';

        $mimetype = "{$resource['resource_type']}{$format}";

        $mimetype = str_replace('jpg', 'jpeg', $mimetype);

        return compact('mimetype');
    }

    /**
     * Prepare timestamp response
     *
     * @param  array  $resource
     * @return array
     */
    protected function prepareTimestamp($resource)
    {
        $timestamp = strtotime($resource['created_at']);

        return compact('timestamp');
    }

    /**
     * Prepare size response
     *
     * @param  array  $resource
     * @return array
     */
    protected function prepareSize($resource)
    {
        $size = $resource['bytes'];

        return compact('size');
    }
}
