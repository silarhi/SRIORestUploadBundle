<?php

namespace SRIO\RestUploadBundle\Upload;

use SRIO\RestUploadBundle\Storage\UploadedFile;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class UploadContext
{
    /**
     * @var UploadedFile
     */
    protected $file;

    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    protected $storageName;

    /**
     * Constructor.
     */
    public function __construct(Request $request = null, FormInterface $form = null, array $config = [])
    {
        $this->request = $request;
        $this->form = $form;
        $this->config = $config;
    }

    /**
     * @param array $config
     */
    public function setConfig($config): void
    {
        $this->config = $config;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @param UploadedFile $file
     */
    public function setFile($file): void
    {
        $this->file = $file;
    }

    public function getFile(): UploadedFile
    {
        return $this->file;
    }

    /**
     * @param FormInterface $form
     */
    public function setForm($form): void
    {
        $this->form = $form;
    }

    public function getForm(): ?FormInterface
    {
        return $this->form;
    }

    /**
     * @param Request $request
     */
    public function setRequest($request): void
    {
        $this->request = $request;
    }

    public function getRequest(): ?Request
    {
        return $this->request;
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
