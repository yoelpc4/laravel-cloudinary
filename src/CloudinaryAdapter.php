<?php

namespace Yoelpc4\LaravelCloudinary;

use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Api\ApiResponse;
use Cloudinary\Api\Exception\ApiError;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Asset\Media;
use Cloudinary\Configuration\Configuration;
use Exception;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;

class CloudinaryAdapter extends AbstractAdapter implements AdapterInterface
{
    use NotSupportingVisibilityTrait;

    /**
     * Cloudinary admin api
     *
     * @var AdminApi
     */
    protected $adminApi;

    /**
     * Cloudinary upload api
     *
     * @var UploadApi
     */
    protected $uploadApi;

    public function __construct(array $options)
    {
        Configuration::instance([
            'cloud' => [
                'cloud_name' => $options['cloud_name'],
                'api_key'    => $options['api_key'],
                'api_secret' => $options['api_secret'],
            ],
            'url'   => [
                'secure' => $options['secure'],
            ],
        ]);

        $this->adminApi = new AdminApi;

        $this->uploadApi = new UploadApi;
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public function writeStream($path, $resource, Config $config)
    {
        try {
            $metadata = stream_get_meta_data($resource);

            return $this->uploadApi->upload($metadata['uri'], [
                'public_id'       => $this->getPublicId($path),
                'use_filename'    => true,
                'unique_filename' => false,
                'resource_type'   => $this->getResourceType($path),
            ]);
        } catch (ApiError $e) {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function update($path, $contents, Config $config)
    {
        return $this->write($path, $contents, $config);
    }

    /**
     * @inheritdoc
     */
    public function updateStream($path, $resource, Config $config)
    {
        return $this->writeStream($path, $resource, $config);
    }

    /**
     * @inheritdoc
     */
    public function rename($path, $newpath): bool
    {
        $toPublicId = $this->getPublicId($newpath);

        $response = $this->uploadApi->rename($this->getPublicId($path), $toPublicId, [
            'resource_type' => $this->getResourceType($newpath),
        ]);

        return $response->offsetGet('public_id') === $toPublicId;
    }

    /**
     * @inheritdoc
     */
    public function copy($path, $newpath): bool
    {
        try {
            $response = $this->uploadApi->upload($this->getUrl($path), [
                'public_id'     => $this->getPublicId($newpath),
                'resource_type' => $this->getResourceType($newpath),
            ]);

            return $response->offsetGet('public_id') === $this->getPublicId($newpath);
        } catch (ApiError $e) {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function delete($path): bool
    {
        $response = $this->uploadApi->destroy($this->getPublicId($path), [
            'resource_type' => $this->getResourceType($path),
            'invalidate'    => true,
        ]);

        return $response->offsetGet('result') === 'ok';
    }

    /**
     * @inheritdoc
     */
    public function has($path)
    {
        $options = [
            'resource_type' => $this->getResourceType($path),
        ];

        try {
            $this->adminApi->asset($this->getPublicId($path), $options);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function read($path)
    {
        $contents = file_get_contents($this->getUrl($path));

        return compact('contents', 'path');
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public function listContents($directory = '', $recursive = false): array
    {
        $resources = [];

        $response = null;

        do {
            $response = (array) $this->adminApi->assets([
                'type'        => 'upload',
                'prefix'      => $directory,
                'max_results' => 500,
                'next_cursor' => $response['next_cursor'] ?? null,
            ]);

            $resources = array_merge($resources, $response['resources']);
        } while (array_key_exists('next_cursor', $response));

        foreach ($resources as $index => $resource) {
            $resources[$index] = $this->prepareResourceMetadata($resource);
        }

        return $resources;
    }

    /**
     * @inheritdoc
     */
    public function getMetadata($path)
    {
        return $this->prepareResourceMetadata($this->getResource($path));
    }

    /**
     * @inheritdoc
     */
    public function getSize($path)
    {
        return $this->prepareSize($this->getResource($path));
    }

    /**
     * @inheritdoc
     */
    public function getMimetype($path)
    {
        return $this->prepareMimetype($this->getResource($path));
    }

    /**
     * @inheritdoc
     */
    public function getTimestamp($path)
    {
        return $this->prepareTimestamp($this->getResource($path));
    }

    /**
     * Get the resource of a file
     *
     * @param  string  $path
     * @return array
     */
    public function getResource(string $path): array
    {
        $publicId = $this->getPublicId($path);

        $options = [
            'resource_type' => $this->getResourceType($path),
        ];

        return (array) $this->adminApi->asset($publicId, $options);
    }

    /**
     * Get the URL for the file at the given path.
     *
     * @param  string|array  $path
     * @return string
     */
    public function getUrl($path): string
    {
        $options = [];

        if (is_array($path)) {
            foreach ($path as $key => $value) {
                $options[$key] = $value;
            }

            unset($options['path']);

            $path = $path['path'];
        }

        $options['resource_type'] = $this->getResourceType($path);

        return Media::fromParams($path, $options)->toUrl();
    }

    /**
     * @inheritdoc
     */
    public function createDir($dirname, Config $config)
    {
        try {
            return (array) $this->adminApi->createFolder($dirname);
        } catch (ApiError $e) {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function deleteDir($dirname): bool
    {
        try {
            $this->adminApi->deleteFolder($dirname);

            return true;
        } catch (ApiError $e) {
            return false;
        }
    }

    /**
     * Prepare cloudinary resource metadata
     *
     * @param  array  $resource
     * @return array
     */
    protected function prepareResourceMetadata(array $resource): array
    {
        $resource['type'] = 'file';

        $resource['path'] = $resource['public_id'];

        return array_merge(
            $resource,
            $this->prepareSize($resource),
            $this->prepareTimestamp($resource),
            $this->prepareMimetype($resource)
        );
    }

    /**
     * Prepare cloudinary resource mimetype
     *
     * @param  array  $resource
     * @return array
     */
    protected function prepareMimetype(array $resource): array
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
    protected function prepareTimestamp(array $resource): array
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
    protected function prepareSize(array $resource): array
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
    protected function getResourceType(string $path): string
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
    protected function getPublicId(string $path): string
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
