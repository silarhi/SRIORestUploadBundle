<?php

namespace SRIO\RestUploadBundle\Upload;

use SRIO\RestUploadBundle\Exception\UploadException;
use Symfony\Component\HttpFoundation\Response;

class UploadResult extends UploadContext
{
    protected ?Response $response = null;

    protected ?UploadException $exception = null;

    public function setException(?UploadException $exception): void
    {
        $this->exception = $exception;
    }

    public function getException(): ?UploadException
    {
        return $this->exception;
    }

    public function setResponse(?Response $response): void
    {
        $this->response = $response;
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }
}
