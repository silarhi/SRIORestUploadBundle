<?php

namespace SRIO\RestUploadBundle\Storage;

interface FileAdapterInterface
{
    /**
     * Get the file size.
     *
     * @return int file size
     */
    public function getSize(): int;

    /**
     * Check whether the file exists.
     */
    public function exists(): bool;

    /**
     * Retrieve the file path.
     *
     * @return string path
     */
    public function getName(): string;

    /**
     * Returns the underlying file instance for processing by specialized code.
     */
    public function getAdapter(): mixed;
}
