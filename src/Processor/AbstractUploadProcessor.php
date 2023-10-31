<?php

namespace SRIO\RestUploadBundle\Processor;

use SRIO\RestUploadBundle\Upload\UploadResult;
use SRIO\RestUploadBundle\Exception\UploadException;
use SRIO\RestUploadBundle\Exception\UploadProcessorException;
use SRIO\RestUploadBundle\Model\UploadableFileInterface;
use SRIO\RestUploadBundle\Request\RequestContentHandler;
use SRIO\RestUploadBundle\Request\RequestContentHandlerInterface;
use SRIO\RestUploadBundle\Upload\StorageHandler;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractUploadProcessor implements ProcessorInterface
{
    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var RequestContentHandler
     */
    protected $contentHandler;

    /**
     * @var StorageHandler
     */
    protected $storageHandler;

    /**
     * Constructor.
     */
    public function __construct(StorageHandler $storageHandler)
    {
        $this->storageHandler = $storageHandler;
    }

    /**
     * Constructor.
     *
     * @return bool
     */
    public function handleUpload(Request $request, FormInterface $form = null, array $config = []): UploadResult
    {
        $this->form = $form;
        $this->config = $config;

        return $this->handleRequest($request);
    }

    /**
     * Handle an upload request.
     *
     * This method return a Response object that will be sent back
     * to the client or will be caught by controller.
     */
    abstract public function handleRequest(Request $request): UploadResult;

    /**
     * Create the form data that the form will be able to handle.
     *
     * It walk one the form and make an intersection between its keys and
     * provided data.
     */
    protected function createFormData(array $data): array
    {
        $keys = $this->getFormKeys($this->form);

        return array_intersect_key($data, $keys);
    }

    /**
     * Get keys of the form.
     */
    protected function getFormKeys(FormInterface $form): array
    {
        $keys = [];
        foreach ($form->all() as $child) {
            $keys[$child->getName()] = $child->all() !== [] ? $this->getFormKeys($child) : null;
        }

        return $keys;
    }

    /**
     * Get a request content handler.
     *
     * @return RequestContentHandlerInterface
     */
    protected function getRequestContentHandler(Request $request): RequestContentHandler|RequestContentHandlerInterface
    {
        if (null === $this->contentHandler) {
            $this->contentHandler = new RequestContentHandler($request);
        }

        return $this->contentHandler;
    }

    /**
     * Check that needed headers are here.
     *
     * @param Request $request the request
     * @param array   $headers the headers to check
     *
     * @throws UploadException
     */
    protected function checkHeaders(Request $request, array $headers): void
    {
        foreach ($headers as $header) {
            $value = $request->headers->get($header, null);
            if (null === $value) {
                throw new UploadException(sprintf('%s header is needed', $header));
            } elseif (!is_int($value) && empty($value) && '0' !== $value) {
                throw new UploadException(sprintf('%s header must not be empty', $header));
            }
        }
    }

    /**
     * Set the uploaded file on the form data.
     *
     * @throws UploadProcessorException
     *
     * @deprecated
     */
    protected function setUploadedFile(UploadedFile $file): void
    {
        $data = $this->form->getData();
        if ($data instanceof UploadableFileInterface) {
            $data->setFile($file);
        } else {
            throw new UploadProcessorException(sprintf('Unable to set file, %s do not implements %s', $data::class, UploadableFileInterface::class));
        }
    }
}
