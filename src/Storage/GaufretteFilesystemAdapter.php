<?php

namespace SRIO\RestUploadBundle\Storage;

use Exception;
use Gaufrette\Adapter;
use Gaufrette\Adapter\MetadataSupporter;
use Gaufrette\Exception\FileNotFound;
use Gaufrette\Filesystem;
use Gaufrette\StreamMode;
use RuntimeException;
use SRIO\RestUploadBundle\Exception\FileNotFoundException as WrappingFileNotFoundException;

class GaufretteFilesystemAdapter implements FilesystemAdapterInterface
{
    public function __construct(protected Filesystem $filesystem)
    {
    }

    public function getFilesystem(): Filesystem
    {
        return $this->filesystem;
    }

    public function getAdapter(): Adapter
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
    protected function writeContents(string $path, string|false $content, array $config = [], $overwrite = false): bool
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

    public function read(string $path): false|string
    {
        try {
            return $this->filesystem->read($path);
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
        $streamCopy = fopen('php://temp/maxmemory:'.$mbLimit, 'w+b');
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

    public function getModifiedTimestamp(string $path): int
    {
        try {
            return $this->filesystem->mtime($path) ?: 0;
        } catch (Exception $exception) {
            throw $this->createFileNotFoundException($path, $exception);
        }
    }

    public function getSize(string $path): int
    {
        try {
            return $this->filesystem->size($path) ?: 0;
        } catch (Exception $exception) {
            throw $this->createFileNotFoundException($path, $exception);
        }
    }

    public function getMimeType(string $path): string
    {
        try {
            return $this->filesystem->mimeType($path);
        } catch (Exception $exception) {
            throw $this->createFileNotFoundException($path, $exception);
        }
    }

    protected function createFileNotFoundException(string $path, Exception $previousEx = null): WrappingFileNotFoundException
    {
        if (!$previousEx instanceof FileNotFound) {
            $previousEx = new FileNotFound($path);
        }

        return new WrappingFileNotFoundException($previousEx->getMessage(), $previousEx->getCode(), $previousEx);
    }
}
