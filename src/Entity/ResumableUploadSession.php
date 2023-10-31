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
     *
     * @var string
     */
    protected $sessionId;

    /**
     * The destination file path.
     *
     * @var string
     */
    protected $filePath;

    /**
     * Name of storage used.
     *
     * @var string
     */
    protected $storageName;

    /**
     * The form data.
     *
     * @var string
     */
    protected $data;

    /**
     * Content type.
     *
     * @var string
     */
    protected $contentType;

    /**
     * Content length.
     *
     * @var int
     */
    protected $contentLength;

    /**
     * @param string $filePath
     */
    public function setFilePath($filePath): void
    {
        $this->filePath = $filePath;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    /**
     * @param string $sessionId
     */
    public function setSessionId($sessionId): void
    {
        $this->sessionId = $sessionId;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    /**
     * @param string $data
     */
    public function setData($data): void
    {
        $this->data = $data;
    }

    public function getData(): string
    {
        return $this->data;
    }

    /**
     * @param int $contentLength
     */
    public function setContentLength($contentLength): void
    {
        $this->contentLength = $contentLength;
    }

    public function getContentLength(): int
    {
        return $this->contentLength;
    }

    /**
     * @param string $contentType
     */
    public function setContentType($contentType): void
    {
        $this->contentType = $contentType;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * @param string $storageName
     */
    public function setStorageName($storageName): void
    {
        $this->storageName = $storageName;
    }

    public function getStorageName(): string
    {
        return $this->storageName;
    }
}
