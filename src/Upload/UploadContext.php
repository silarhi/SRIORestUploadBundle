<?php

namespace SRIO\RestUploadBundle\Upload;

use SRIO\RestUploadBundle\Storage\UploadedFile;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class UploadContext
{
    protected ?UploadedFile $file = null;

    protected ?string $storageName = null;

    /**
     * Constructor.
     */
    public function __construct(protected ?Request $request = null, protected ?FormInterface $form = null, protected array $config = [])
    {
    }

    public function getFile(): ?UploadedFile
    {
        return $this->file;
    }

    public function setFile(?UploadedFile $file): void
    {
        $this->file = $file;
    }

    public function getForm(): ?FormInterface
    {
        return $this->form;
    }

    public function setForm(?FormInterface $form): void
    {
        $this->form = $form;
    }

    public function getRequest(): ?Request
    {
        return $this->request;
    }

    public function setRequest(?Request $request): void
    {
        $this->request = $request;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    public function getStorageName(): ?string
    {
        return $this->storageName;
    }

    public function setStorageName(?string $storageName): void
    {
        $this->storageName = $storageName;
    }
}
