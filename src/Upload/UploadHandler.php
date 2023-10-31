<?php

namespace SRIO\RestUploadBundle\Upload;

use LogicException;
use SRIO\RestUploadBundle\Exception\UploadException;
use SRIO\RestUploadBundle\Exception\UploadProcessorException;
use SRIO\RestUploadBundle\Processor\ProcessorInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class UploadHandler
{
    protected array $processors = [];

    public function __construct(protected string $uploadTypeParameter)
    {
    }

    /**
     * Add an upload processor.
     *
     * @throws LogicException
     */
    public function addProcessor(string $uploadType, ProcessorInterface $processor): void
    {
        if (array_key_exists($uploadType, $this->processors)) {
            throw new LogicException(sprintf('A processor is already registered for type %s', $uploadType));
        }

        $this->processors[$uploadType] = $processor;
    }

    /**
     * Handle the upload request.
     *
     * @throws UploadException
     */
    public function handleRequest(Request $request, FormInterface $form = null, array $config = []): UploadResult
    {
        try {
            $processor = $this->getProcessor($request, $config);

            return $processor->handleUpload($request, $form, $config);
        } catch (UploadException $uploadException) {
            if (null != $form) {
                $form->addError(new FormError($uploadException->getMessage()));
            }

            $result = new UploadResult();
            $result->setException($uploadException);
            $result->setForm($form);

            return $result;
        }
    }

    /**
     * Get the upload processor.
     *
     * @throws UploadProcessorException
     */
    protected function getProcessor(Request $request, array $config): ProcessorInterface
    {
        $uploadType = $request->get($this->getUploadTypeParameter($config));

        if (!array_key_exists($uploadType, $this->processors)) {
            throw new UploadProcessorException(sprintf('Unknown upload processor for upload type %s', $uploadType));
        }

        return $this->processors[$uploadType];
    }

    /**
     * Get the current upload type parameter.
     *
     * @internal param $parameter
     * @internal param $config
     */
    protected function getUploadTypeParameter(array $extraConfiguration): mixed
    {
        return array_key_exists('uploadTypeParameter', $extraConfiguration)
            ? $extraConfiguration['uploadTypeParameter']
            : $this->uploadTypeParameter;
    }
}
