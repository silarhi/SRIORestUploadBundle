<?php

namespace SRIO\RestUploadBundle\Storage;

use SRIO\RestUploadBundle\Strategy\NamingStrategy;
use SRIO\RestUploadBundle\Strategy\StorageStrategy;
use SRIO\RestUploadBundle\Upload\UploadContext;

class FileStorage
{
    final public const METADATA_CONTENT_TYPE = 'contentType';

    public function __construct(
        protected string $name,
        protected FilesystemAdapterInterface $filesystem,
        protected StorageStrategy $storageStrategy,
        protected NamingStrategy $namingStrategy
    ) {
    }

    /**
     * Store a file's content.
     *
     * @param bool $overwrite
     */
    public function store(UploadContext $context, string $content, array $config = [], $overwrite = false): UploadedFile
    {
        $path = $this->getFilePathFromContext($context);
        if (true === $overwrite) {
            $this->filesystem->put($path, $content, $config);
        } else {
            $this->filesystem->write($path, $content, $config);
        }

        $file = $this->filesystem->get($path);

        return new UploadedFile($this, $file);
    }

    /**
     * Store a file's content.
     *
     * @param resource $resource
     */
    public function storeStream(UploadContext $context, $resource, array $config = [], bool $overwrite = false): UploadedFile
    {
        $path = $this->getFilePathFromContext($context);
        if ($overwrite) {
            $this->filesystem->putStream($path, $resource, $config);
        } else {
            $this->filesystem->writeStream($path, $resource, $config);
        }

        $file = $this->filesystem->get($path);

        return new UploadedFile($this, $file);
    }

    /**
     * Get or creates a file path from UploadContext.
     */
    protected function getFilePathFromContext(UploadContext $context): string
    {
        if (null != $context->getFile()) {
            return $context->getFile()->getFile()->getName();
        }

        $name = $this->getNamingStrategy()->getName($context);
        $directory = $this->storageStrategy->getDirectory($context, $name);

        return $directory.'/'.$name;
    }

    public function getFilesystem(): FilesystemAdapterInterface
    {
        return $this->filesystem;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNamingStrategy(): NamingStrategy
    {
        return $this->namingStrategy;
    }

    public function getStorageStrategy(): StorageStrategy
    {
        return $this->storageStrategy;
    }
}
