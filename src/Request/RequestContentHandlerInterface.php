<?php

namespace SRIO\RestUploadBundle\Request;

interface RequestContentHandlerInterface
{
    /**
     * Get a line.
     */
    public function gets(): string|false;

    /**
     * Is the end of file.
     */
    public function eof(): bool;

    /**
     * Get cursor position.
     */
    public function getCursor(): int;
}
