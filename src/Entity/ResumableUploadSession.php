<?php

namespace SRIO\RestUploadBundle\Entity;

/**
 * This model represent a resumable upload session. It is used to store
 * a session ID and the related file path.
 */
class ResumableUploadSession
{
    /**
     * The session ID.
     */
    protected ?string $sessionId = null;

    /**
     * The destination file path.
     */
    protected ?string $filePath = null;

    /**
     * Name of storage used.
     */
    protected ?string $storageName = null;

    /**
     * The form data.
     */
    protected ?string $data = null;

    /**
     * Content type.
     */
    protected ?string $contentType = null;

    /**
     * Content length.
     */
    protected ?int $contentLength = null;

    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    public function setSessionId(?string $sessionId): void
    {
        $this->sessionId = $sessionId;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): void
    {
        $this->filePath = $filePath;
    }

    public function getStorageName(): ?string
    {
        return $this->storageName;
    }

    public function setStorageName(?string $storageName): void
    {
        $this->storageName = $storageName;
    }

    public function getData(): ?string
    {
        return $this->data;
    }

    public function setData(?string $data): void
    {
        $this->data = $data;
    }

    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    public function setContentType(?string $contentType): void
    {
        $this->contentType = $contentType;
    }

    public function getContentLength(): ?int
    {
        return $this->contentLength;
    }

    public function setContentLength(?int $contentLength): void
    {
        $this->contentLength = $contentLength;
    }
}
