<?php

namespace SRIO\RestUploadBundle\Upload;

use SRIO\RestUploadBundle\Exception\UploadException;
use SRIO\RestUploadBundle\Storage\FileStorage;
use SRIO\RestUploadBundle\Storage\FilesystemAdapterInterface;
use SRIO\RestUploadBundle\Storage\UploadedFile;
use SRIO\RestUploadBundle\Voter\StorageVoter;

/**
 * This class defines the storage handler.
 */
class StorageHandler
{
    /**
     * Constructor.
     */
    public function __construct(protected StorageVoter $voter)
    {
    }

    public function store(UploadContext $context, string|false $contents, array $config = [], bool $overwrite = false): UploadedFile
    {
        return $this->getStorage($context)->store($context, $contents, $config, $overwrite);
    }

    /**
     * Store a file's content.
     *
     * @param resource $resource
     */
    public function storeStream(UploadContext $context, $resource, array $config = [], bool $overwrite = false): UploadedFile
    {
        return $this->getStorage($context)->storeStream($context, $resource, $config, $overwrite);
    }

    public function getFilesystem(UploadContext $context): FilesystemAdapterInterface
    {
        return $this->getStorage($context)->getFilesystem();
    }

    /**
     * Get storage by upload context.
     *
     * @throws UploadException
     */
    public function getStorage(UploadContext $context): FileStorage
    {
        return $this->voter->getStorage($context);
    }
}
