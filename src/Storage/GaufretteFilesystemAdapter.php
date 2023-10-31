<?php

namespace SRIO\RestUploadBundle\Storage;

use Gaufrette\Adapter\MetadataSupporter;
use Gaufrette\Exception\FileAlreadyExists;
use Gaufrette\Exception\FileNotFound;
use Gaufrette\Filesystem;
use Gaufrette\StreamMode;
use RuntimeException;
use SRIO\RestUploadBundle\Exception\FileExistsException as WrappingFileExistsException;
use SRIO\RestUploadBundle\Exception\FileNotFoundException as WrappingFileNotFoundException;

class GaufretteFilesystemAdapter implements FilesystemAdapterInterface
{
    /**
     * @var Filesystem
     */
    protected $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function getFilesystem(): mixed
    {
        return $this->filesystem;
    }

    public function getAdapter(): mixed
    {
        return $this->filesystem->getAdapter();
    }

    public function has(string $path): bool
    {
        return $this->filesystem->has($path);
    }

    public function get(string $path): FileAdapterInterface|GaufretteFileAdapter
    {
        return new GaufretteFileAdapter($this->filesystem->get($path));
    }

    public function write(string $path, mixed $content, array $config = []): bool
    {
        return $this->writeContents($path, $content, $config, false);
    }

    public function writeStream(string $path, $resource, array $config = []): bool
    {
        // This is not ideal, stream_get_contents will read the full stream into memory before we can
        // flow it into the write function. Watch out with big files and Gaufrette!
        return $this->writeContents($path, stream_get_contents($resource, -1, 0), $config, false);
    }

    public function put(string $path, $content, array $config = []): bool
    {
        return $this->writeContents($path, $content, $config, true);
    }

    public function putStream(string $path, $resource, array $config = []): bool
    {
        // This is not ideal, stream_get_contents will read the full stream into memory before we can
        // flow it into the write function. Watch out with big files and Gaufrette!
        return $this->writeContents($path, stream_get_contents($resource, -1, 0), $config, true);
    }

    /**
     * General function for all writes.
     *
     * @param bool $overwrite
     */
    protected function writeContents($path, $content, array $config = [], $overwrite = false): bool
    {
        if (!empty($config['metadata'])) {
            $adapter = $this->getAdapter();
            if ($adapter instanceof MetadataSupporter) {
                $allowed = empty($config['allowedMetadataKeys'])
                    ? [FileStorage::METADATA_CONTENT_TYPE]
                    : array_merge($config['allowedMetadataKeys'], [FileStorage::METADATA_CONTENT_TYPE]);

                $adapter->setMetadata($path, $this->resolveMetadataMap($allowed, $config['metadata']));
            }
        }

        try {
            $this->filesystem->write($path, $content, $overwrite);

            return true;
        } catch (RuntimeException) {
            return false;
        }
    }

    /**
     * Resolve the metadata map.
     */
    protected function resolveMetadataMap(array $allowedMetadataKeys, array $metadataMap): array
    {
        $map = [];

        foreach ($allowedMetadataKeys as $key) {
            if (array_key_exists($key, $metadataMap)) {
                $map[$key] = $metadataMap[$key];
            }
        }

        return $map;
    }

    public function read(string $path): bool|string
    {
        try {
            $this->filesystem->read($path);

            return true;
        } catch (FileNotFound $ex) {
            throw $this->createFileNotFoundException($path, $ex);
        } catch (RuntimeException) {
            return false;
        }
    }

    public function readStream(string $path)
    {
        if (!$this->filesystem->has($path)) {
            throw $this->createFileNotFoundException($path);
        }

        // If castable to a real stream (local filesystem for instance) use that stream.
        $streamWrapper = $this->filesystem->createStream($path);
        $streamWrapper->open(new StreamMode('rb'));

        $stream = $streamWrapper->cast(0);

        if (false === $stream) {
            // This is not ideal, read will first read the full file into memory before we can
            // flow it into the temp stream. Watch out with big files and Gaufrette!
            $stream = fopen('php://temp', 'w+b');
            fwrite($stream, $this->read($path));
            rewind($stream);
        }

        return $stream;
    }

    public function getStreamCopy(string $path)
    {
        if (!$this->filesystem->has($path)) {
            throw $this->createFileNotFoundException($path);
        }

        // If castable to a real stream (local filesystem for instance) use that stream.
        $streamWrapper = $this->filesystem->createStream($path);
        $streamWrapper->open(new StreamMode('rb'));

        $stream = $streamWrapper->cast(0);

        // Neatly overflow into a file on disk after more than 10MBs.
        $mbLimit = 10 * 1024 * 1024;
        $streamCopy = fopen('php://temp/maxmemory:' . $mbLimit, 'w+b');
        if (false === $stream) {
            // This is not ideal, read will first read the full file into memory before we can
            // flow it into the temp stream. Watch out with big files and Gaufrette!
            $data = $this->read($path);
            fwrite($streamCopy, $data);
        } else {
            stream_copy_to_stream($stream, $streamCopy);
        }

        rewind($streamCopy);

        return $streamCopy;
    }

    public function delete(string $path): bool
    {
        try {
            return $this->filesystem->delete($path);
        } catch (FileNotFound $fileNotFound) {
            throw $this->createFileNotFoundException($path, $fileNotFound);
        }
    }

    public function getModifiedTimestamp(string $path): bool|int
    {
        try {
            return $this->filesystem->mtime($path);
        } catch (FileNotFound $fileNotFound) {
            throw $this->createFileNotFoundException($path, $fileNotFound);
        }
    }

    public function getSize(string $path)
    {
        try {
            return $this->filesystem->size($path);
        } catch (FileNotFound $fileNotFound) {
            throw $this->createFileNotFoundException($path, $fileNotFound);
        }
    }

    public function getMimeType(string $path)
    {
        try {
            return $this->filesystem->mimeType($path);
        } catch (FileNotFound $fileNotFound) {
            throw $this->createFileNotFoundException($path, $fileNotFound);
        }
    }

    protected function createFileNotFoundException($path, $previousEx = null): WrappingFileNotFoundException
    {
        if (null === $previousEx) {
            $previousEx = new FileNotFound($path);
        }

        return new WrappingFileNotFoundException($previousEx->getMessage(), $previousEx->getCode(), $previousEx);
    }

    protected function createFileExistsException($path, $previousEx = null): WrappingFileExistsException
    {
        if (null === $previousEx) {
            $previousEx = new FileAlreadyExists($path);
        }

        return new WrappingFileExistsException($previousEx->getMessage(), $previousEx->getCode(), $previousEx);
    }
}
