<?php

namespace SRIO\RestUploadBundle\Storage;

class UploadedFile
{
    /**
     * @var FileStorage
     */
    protected $storage;

    /**
     * @var FileAdapterInterface
     */
    protected $file;

    public function __construct(FileStorage $storage, FileAdapterInterface $file)
    {
        $this->storage = $storage;
        $this->file = $file;
    }

    public function getFile(): FileAdapterInterface
    {
        return $this->file;
    }

    public function getStorage(): FileStorage
    {
        return $this->storage;
    }
}
