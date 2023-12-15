<?php

namespace SRIO\RestUploadBundle\Processor;

use Exception;
use SRIO\RestUploadBundle\Exception\UploadException;
use SRIO\RestUploadBundle\Storage\FileStorage;
use SRIO\RestUploadBundle\Upload\UploadResult;
use Symfony\Component\HttpFoundation\Request;

class SimpleUploadProcessor extends AbstractUploadProcessor
{
    /**
     * @throws Exception|UploadException
     */
    public function handleRequest(Request $request): UploadResult
    {
        // Check that needed headers exists
        $this->checkHeaders($request, ['Content-Type']);

        $result = new UploadResult();
        $result->setForm($this->form);
        $result->setRequest($request);
        $result->setConfig($this->config);

        // Submit form data
        if (null !== $this->form) {
            $formData = $this->createFormData($request->query->all());
            $this->form->submit($formData);
        }

        if (null === $this->form || ($this->form->isSubmitted() && $this->form->isValid())) {
            $content = $request->getContent();

            // Nothing to store
            if (empty($content)) {
                throw new UploadException('There is no content to upload');
            }

            $file = $this->storageHandler->store($result, $content, ['metadata' => [FileStorage::METADATA_CONTENT_TYPE => $request->headers->get('Content-Type')]]);

            $result->setFile($file);
        }

        return $result;
    }
}
