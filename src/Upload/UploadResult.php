<?php

namespace SRIO\RestUploadBundle\Upload;

use Symfony\Component\HttpFoundation\Response;
use SRIO\RestUploadBundle\Exception\UploadException;
class UploadResult extends UploadContext
{
    /**
     * @var ?Response
     */
    protected $response;

    /**
     * @var UploadException
     */
    protected $exception;

    /**
     * @param UploadException $exception
     */
    public function setException($exception): void
    {
        $this->exception = $exception;
    }

    public function getException(): UploadException
    {
        return $this->exception;
    }

    /**
     * @param Response $response
     */
    public function setResponse(?Response $response): void
    {
        $this->response = $response;
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }
}
