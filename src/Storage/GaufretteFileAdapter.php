<?php

namespace SRIO\RestUploadBundle\Storage;

use Gaufrette\File;

class GaufretteFileAdapter implements FileAdapterInterface
{
    /**
     * @var File
     */
    protected $file;

    public function __construct(File $file)
    {
        $this->file = $file;
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

    public function getAdapter()
    {
        return $this->file;
    }
}
