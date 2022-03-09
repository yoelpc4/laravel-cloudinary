<?php

namespace Yoelpc4\LaravelCloudinary;

use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Api\Exception\ApiError;
use Cloudinary\Api\Exception\NotFound;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Asset\Media;
use Cloudinary\Configuration\Configuration;
use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToCheckExistence;
use League\Flysystem\UnableToCopyFile;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToDeleteDirectory;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use Throwable;

class CloudinaryAdapter implements FilesystemAdapter
{
    protected AdminApi $adminApi;

    protected UploadApi $uploadApi;

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
    public function fileExists(string $path): bool
    {
        try {
            $this->getAsset($path);

            return true;
        } catch (Throwable $e) {
            if ($e instanceof NotFound) {
                return false;
            }

            throw UnableToCheckExistence::forLocation($path, $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function directoryExists(string $path): bool
    {
        try {
            $this->adminApi->subFolders($path);

            return true;
        } catch (ApiError $e) {
            if ($e instanceof NotFound) {
                return false;
            }

            throw UnableToCheckExistence::forLocation($path, $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function write(string $path, string $contents, Config $config): void
    {
        $tmpFile = tmpfile();

        if (! fwrite($tmpFile, $contents)) {
            throw UnableToWriteFile::atLocation($path);
        }

        $this->writeStream($path, $tmpFile, $config);
    }

    /**
     * @inheritdoc
     */
    public function writeStream(string $path, $contents, Config $config): void
    {
        try {
            $metadata = stream_get_meta_data($contents);

            $this->uploadApi->upload($metadata['uri'], [
                'public_id'       => $this->getPublicId($path),
                'use_filename'    => true,
                'unique_filename' => false,
                'resource_type'   => $this->getResourceType($path),
            ]);
        } catch (ApiError $e) {
            throw UnableToWriteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function read(string $path): string
    {
        try {
            return file_get_contents($this->getUrl($path));
        } catch (Throwable $e) {
            throw UnableToReadFile::fromLocation($path, $e->getMessage(), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function readStream(string $path)
    {
        try {
            return fopen($this->getUrl($path), 'r');
        } catch (Throwable $e) {
            throw UnableToReadFile::fromLocation($path, $e->getMessage(), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function delete(string $path): void
    {
        try {
            $this->uploadApi->destroy($this->getPublicId($path), [
                'resource_type' => $this->getResourceType($path),
                'invalidate'    => true,
            ]);
        } catch (Throwable $e) {
            throw UnableToDeleteFile::atLocation($path, $e->getMessage(), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function deleteDirectory(string $path): void
    {
        try {
            $this->adminApi->deleteFolder($path);
        } catch (ApiError $e) {
            throw UnableToDeleteDirectory::atLocation($path, $e->getMessage(), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function createDirectory(string $path, Config $config): void
    {
        try {
            $this->adminApi->createFolder($path);
        } catch (ApiError $e) {
            throw UnableToCreateDirectory::dueToFailure($path, $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function setVisibility(string $path, string $visibility): void
    {
        throw new UnableToSetVisibility('visibility is unsupported');
    }

    /**
     * @inheritdoc
     */
    public function visibility(string $path): FileAttributes
    {
        throw UnableToRetrieveMetadata::visibility($path, 'visibility is unsupported');
    }

    /**
     * @inheritdoc
     */
    public function mimeType(string $path): FileAttributes
    {
        try {
            return $this->getFileAttributes($this->getAsset($path));
        } catch (Throwable $e) {
            throw UnableToRetrieveMetadata::mimeType($path, $e->getMessage(), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function lastModified(string $path): FileAttributes
    {
        try {
            return $this->getFileAttributes($this->getAsset($path));
        } catch (Throwable $e) {
            throw UnableToRetrieveMetadata::lastModified($path, $e->getMessage(), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function fileSize(string $path): FileAttributes
    {
        try {
            return $this->getFileAttributes($this->getAsset($path));
        } catch (Throwable $e) {
            throw UnableToRetrieveMetadata::fileSize($path, $e->getMessage(), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function listContents(string $path, bool $deep): iterable
    {
        $resources = [];

        $response = null;

        try {
            do {
                $response = (array) $this->adminApi->assets([
                    'type'        => 'upload',
                    'prefix'      => $path,
                    'max_results' => 500,
                    'next_cursor' => $response['next_cursor'] ?? null,
                ]);

                $resources = array_merge($resources, $response['resources']);
            } while (array_key_exists('next_cursor', $response));

            return array_map(fn(array $resource) => $this->getFileAttributes($resource), $resources);
        } catch (Throwable $e) {
            throw UnableToListContents::fromLocation($path, $e->getMessage(), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function move(string $source, string $destination, Config $config): void
    {
        $toPublicId = $this->getPublicId($destination);

        try {
            $this->uploadApi->rename($this->getPublicId($source), $toPublicId, [
                'resource_type' => $this->getResourceType($destination),
            ]);
        } catch (Throwable $e) {
            throw UnableToMoveFile::fromLocationTo($source, $destination, $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function copy(string $source, string $destination, Config $config): void
    {
        try {
            $this->uploadApi->upload($this->getUrl($source), [
                'public_id'     => $this->getPublicId($destination),
                'resource_type' => $this->getResourceType($destination),
            ]);
        } catch (ApiError $e) {
            throw UnableToCopyFile::fromLocationTo($source, $destination, $e);
        }
    }

    /**
     * Get the URL for the file at the given path.
     *
     * @param  string|array  $path
     * @return string
     */
    public function getUrl(string|array $path): string
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
     * Get the cloudinary asset from the specified path
     *
     * @param  string  $path
     * @return array
     * @throws Throwable
     */
    protected function getAsset(string $path): array
    {
        return (array) $this->adminApi->asset($this->getPublicId($path), [
            'resource_type' => $this->getResourceType($path),
        ]);
    }

    /**
     * Get cloudinary asset as file attributes
     *
     * @param  array  $asset
     * @return FileAttributes
     */
    protected function getFileAttributes(array $asset): FileAttributes
    {
        $format = isset($asset['format']) ? "/{$asset['format']}" : '';

        $mimeType = str_replace('jpg', 'jpeg', "{$asset['resource_type']}{$format}");

        return new FileAttributes(
            $asset['public_id'],
            $asset['bytes'],
            null,
            strtotime($asset['created_at']),
            $mimeType
        );
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
