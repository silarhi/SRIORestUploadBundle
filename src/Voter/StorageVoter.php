<?php

namespace SRIO\RestUploadBundle\Voter;

use RuntimeException;
use SRIO\RestUploadBundle\Exception\UploadException;
use SRIO\RestUploadBundle\Storage\FileStorage;
use SRIO\RestUploadBundle\Upload\UploadContext;
use Symfony\Component\HttpFoundation\Request;

/**
 * This storage voter has the role to chose the storage
 * that will be used for the current file upload.
 */
class StorageVoter
{
    /**
     * @var FileStorage[]
     */
    protected $storages = [];

    /**
     * Constructor.
     *
     * @param string $defaultStorage
     */
    public function __construct(protected $defaultStorage = null)
    {
    }

    /**
     * Add a storage.
     *
     * @throws RuntimeException
     */
    public function addStorage(FileStorage $storage): void
    {
        if (array_key_exists($storage->getName(), $this->storages)) {
            throw new RuntimeException(sprintf('Storage with name %s already exists', $storage->getName()));
        }

        $this->storages[$storage->getName()] = $storage;
    }

    /**
     * Get the best storage based on request and/or parameters.
     *
     * @throws UploadException
     * @throws RuntimeException
     */
    public function getStorage(UploadContext $context): FileStorage
    {
        if (0 == count($this->storages)) {
            throw new UploadException('No storage found');
        }

        if (($storageName = $context->getStorageName()) !== null
            || (($storageName = $this->defaultStorage) !== null)) {
            if (!array_key_exists($storageName, $this->storages)) {
                throw new RuntimeException(sprintf('Storage with name %s do not exists', $storageName));
            }

            return $this->storages[$storageName];
        }

        return current($this->storages);
    }
}
