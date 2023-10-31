<?php

namespace SRIO\RestUploadBundle\Storage;

use League\Flysystem\FilesystemOperator;

class FlysystemFileAdapter implements FileAdapterInterface
{
    public function __construct(
        private readonly FilesystemOperator $adapter,
        private readonly string $path,
    ) {
    }

    public function exists(): bool
    {
        return $this->adapter->fileExists($this->path);
    }

    public function getSize(): int
    {
        return $this->adapter->fileSize($this->path);
    }

    public function getName(): string
    {
        return $this->path;
    }

    public function getAdapter(): FilesystemOperator
    {
        return $this->adapter;
    }
}
