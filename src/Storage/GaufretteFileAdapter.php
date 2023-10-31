<?php

namespace SRIO\RestUploadBundle\Storage;

use Gaufrette\File;

class GaufretteFileAdapter implements FileAdapterInterface
{
    public function __construct(protected File $file)
    {
    }

    public function exists(): bool
    {
        return $this->file->exists();
    }

    public function getSize(): int
    {
        return $this->file->getSize();
    }

    public function getName(): string
    {
        return $this->file->getKey();
    }

    public function getAdapter(): File
    {
        return $this->file;
    }
}
