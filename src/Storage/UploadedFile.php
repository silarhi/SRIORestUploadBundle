<?php

namespace SRIO\RestUploadBundle\Storage;

class UploadedFile
{
    public function __construct(protected FileStorage $storage, protected FileAdapterInterface $file)
    {
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
