<?php

namespace SRIO\RestUploadBundle\Storage;

use SRIO\RestUploadBundle\Strategy\NamingStrategy;
use SRIO\RestUploadBundle\Strategy\StorageStrategy;
use SRIO\RestUploadBundle\Upload\UploadContext;

class FileStorage
{
    final public const METADATA_CONTENT_TYPE = 'contentType';

    /**
     * @var FilesystemAdapterInterface
     */
    protected $filesystem;

    /**
     * @var StorageStrategy
     */
    protected $storageStrategy;

    /**
     * @var \Doctrine\ORM\Mapping\NamingStrategy
     */
    protected $namingStrategy;

    /**
     * Constructor.
     *
     * @param string $name
     */
    public function __construct(protected $name, FilesystemAdapterInterface $filesystem, StorageStrategy $storageStrategy, NamingStrategy $namingStrategy)
    {
        $this->filesystem = $filesystem;
        $this->storageStrategy = $storageStrategy;
        $this->namingStrategy = $namingStrategy;
    }

    /**
     * Store a file's content.
     *
     * @param string $content
     * @param bool   $overwrite
     */
    public function store(UploadContext $context, $content, array $config = [], $overwrite = false): UploadedFile
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
     * @param bool     $overwrite
     */
    public function storeStream(UploadContext $context, $resource, array $config = [], $overwrite = false): UploadedFile
    {
        $path = $this->getFilePathFromContext($context);
        if (true === $overwrite) {
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

        $name = $this->namingStrategy->getName($context);
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

    /**
     * @return NamingStrategy
     */
    public function getNamingStrategy(): \Doctrine\ORM\Mapping\NamingStrategy|NamingStrategy
    {
        return $this->namingStrategy;
    }

    public function getStorageStrategy(): StorageStrategy
    {
        return $this->storageStrategy;
    }
}
