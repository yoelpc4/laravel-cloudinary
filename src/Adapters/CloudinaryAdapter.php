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
     * CloudinaryAdapter constructor.
     *
     * @param  array  $options
     */
    public function __construct(array $options)
    {
        \Cloudinary::config($options);

        $this->api = new Api;
    }

    /**
     * @inheritDoc
     */
    public function write($path, $contents, Config $options)
    {
        $tmpFile = tmpfile();

        if (fwrite($tmpFile, $contents)) {
            return $this->writeStream($path, $tmpFile, $options);
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function writeStream($path, $resource, Config $options)
    {
        $metadata = stream_get_meta_data($resource);

        $options = [
            'public_id'     => $this->preparePublicId($path),
            'resource_type' => 'auto',
        ];

        return Uploader::upload($metadata['uri'], $options);
    }

    /**
     * @inheritDoc
     */
    public function update($path, $contents, Config $options)
    {
        return $this->write($path, $contents, $options);
    }

    /**
     * @inheritDoc
     */
    public function updateStream($path, $resource, Config $options)
    {
        return $this->writeStream($path, $resource, $options);
    }

    /**
     * @inheritDoc
     */
    public function rename($path, $newpath)
    {
        $fromPublicId = $this->preparePublicId($path);

        $toPublicId = $this->preparePublicId($newpath);

        $result = Uploader::rename($fromPublicId, $toPublicId);

        return $result['public_id'] == pathinfo($newpath)['filename'];
    }

    /**
     * @inheritDoc
     */
    public function copy($path, $newpath)
    {
        $url = cloudinary_url_internal($path);

        $result = Uploader::upload($url, ['public_id' => $newpath]);

        return is_array($result) ? $result['public_id'] == $newpath : false;
    }

    /**
     * @inheritDoc
     */
    public function delete($path)
    {
        $result = Uploader::destroy($this->preparePublicId($path), [
            'invalidate' => true,
        ]);

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
    public function createDir($dirname, Config $options)
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
    public function has($path)
    {
        try {
            $this->api->resource($this->preparePublicId($path));
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
        $contents = file_get_contents(cloudinary_url($path));

        return compact('contents', 'path');
    }

    /**
     * @inheritDoc
     */
    public function readStream($path)
    {
        try {
            $stream = fopen(cloudinary_url($path), 'r');
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
        try {
            return (array) $this->api->resource($this->preparePublicId($path));
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
        return cloudinary_url($path, [
            'secure' => \Config::get('filesystems.disks.cloudinary.secure'),
        ]);
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
        $mimetype = $resource['resource_type'].'/'.$resource['format'];

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

    /**
     * Prepare cloudinary public id
     *
     * @param  string  $path
     * @return string
     */
    protected function preparePublicId(string $path)
    {
        $pathInfo = pathinfo($path);

        if ($pathInfo['dirname'] != '.') {
            return "{$pathInfo['dirname']}/{$pathInfo['filename']}";
        } else {
            return $pathInfo['filename'];
        }
    }
}
