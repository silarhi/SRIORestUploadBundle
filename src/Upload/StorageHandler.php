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
     * @var StorageVoter
     */
    protected $voter;

    /**
     * Constructor.
     */
    public function __construct(StorageVoter $voter)
    {
        $this->voter = $voter;
    }

    /**
     * Store a file's content.
     *
     * @param bool $overwrite
     */
    public function store(UploadContext $context, $contents, array $config = [], $overwrite = false): UploadedFile
    {
        return $this->getStorage($context)->store($context, $contents, $config, $overwrite);
    }

    /**
     * Store a file's content.
     *
     * @param resource $resource
     * @param bool     $overwrite
     */
    public function storeStream(UploadContext $context, $resource, array $config = [], $overwrite = false): UploadedFile
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
        $storage = $this->voter->getStorage($context);
        if (!$storage instanceof FileStorage) {
            throw new UploadException("Storage returned by voter isn't instanceof FileStorage");
        }

        return $storage;
    }
}
