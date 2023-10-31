<?php

namespace SRIO\RestUploadBundle\Storage;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToRetrieveMetadata;
use League\Flysystem\UnableToWriteFile;
use SRIO\RestUploadBundle\Exception\FileExistsException as WrappingFileExistsException;
use SRIO\RestUploadBundle\Exception\FileNotFoundException as WrappingFileNotFoundException;
use Throwable;

class FlysystemFilesystemAdapter implements FilesystemAdapterInterface
{
    public function __construct(protected FilesystemOperator $filesystem)
    {
    }

    public function getFilesystem(): FilesystemOperator
    {
        return $this->filesystem;
    }

    public function getAdapter(): mixed
    {
        return $this->filesystem;
    }

    public function has(string $path): bool
    {
        return $this->filesystem->has($path);
    }

    public function get(string $path): FlysystemFileAdapter|FileAdapterInterface
    {
        return new FlysystemFileAdapter($this->filesystem, $path);
    }

    public function write(string $path, mixed $content, array $config = []): bool
    {
        try {
            $this->filesystem->write($path, $content, $config);

            return true;
        } catch (UnableToWriteFile $unableToWriteFile) {
            throw $this->createFileExistsException($unableToWriteFile);
        }
    }

    public function writeStream(string $path, $resource, array $config = []): bool
    {
        try {
            $this->filesystem->writeStream($path, $resource, $config);

            return true;
        } catch (UnableToWriteFile $unableToWriteFile) {
            throw $this->createFileExistsException($unableToWriteFile);
        }
    }

    public function put(string $path, mixed $content, array $config = []): bool
    {
        return $this->write($path, $content, $config);
    }

    public function putStream(string $path, $resource, array $config = []): bool
    {
        return $this->writeStream($path, $resource, $config);
    }

    public function read(string $path): string
    {
        try {
            return $this->filesystem->read($path);
        } catch (UnableToReadFile $unableToReadFile) {
            throw $this->createFileNotFoundException($unableToReadFile);
        }
    }

    public function readStream(string $path)
    {
        try {
            return $this->filesystem->readStream($path);
        } catch (UnableToReadFile $unableToReadFile) {
            throw $this->createFileNotFoundException($unableToReadFile);
        }
    }

    public function delete(string $path): bool
    {
        try {
            $this->filesystem->delete($path);

            return true;
        } catch (UnableToDeleteFile $unableToDeleteFile) {
            throw $this->createFileNotFoundException($unableToDeleteFile);
        }
    }

    public function getStreamCopy(string $path)
    {
        $stream = $this->readStream($path);

        // Neatly overflow into a file on disk after more than 10MBs.
        $mbLimit = 10 * 1024 * 1024;
        $streamCopy = fopen('php://temp/maxmemory:'.$mbLimit, 'w+b');

        stream_copy_to_stream($stream, $streamCopy);
        rewind($streamCopy);

        return $streamCopy;
    }

    public function getModifiedTimestamp(string $path): int
    {
        try {
            return $this->filesystem->lastModified($path);
        } catch (UnableToRetrieveMetadata $unableToRetrieveMetadata) {
            throw $this->createFileNotFoundException($unableToRetrieveMetadata);
        }
    }

    public function getSize(string $path): int
    {
        try {
            return $this->filesystem->fileSize($path);
        } catch (UnableToRetrieveMetadata $unableToRetrieveMetadata) {
            throw $this->createFileNotFoundException($unableToRetrieveMetadata);
        }
    }

    public function getMimeType(string $path): string
    {
        try {
            return $this->filesystem->mimeType($path);
        } catch (UnableToRetrieveMetadata $unableToRetrieveMetadata) {
            throw $this->createFileNotFoundException($unableToRetrieveMetadata);
        }
    }

    protected function createFileNotFoundException(Throwable $previousEx): WrappingFileNotFoundException
    {
        return new WrappingFileNotFoundException($previousEx->getMessage(), $previousEx->getCode(), $previousEx);
    }

    protected function createFileExistsException(Throwable $previousEx): WrappingFileExistsException
    {
        return new WrappingFileExistsException($previousEx->getMessage(), $previousEx->getCode(), $previousEx);
    }
}
