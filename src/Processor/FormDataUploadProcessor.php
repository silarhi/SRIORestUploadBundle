<?php

namespace SRIO\RestUploadBundle\Processor;

use Exception;
use SRIO\RestUploadBundle\Exception\UploadException;
use SRIO\RestUploadBundle\Storage\FileStorage;
use SRIO\RestUploadBundle\Upload\UploadResult;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

class FormDataUploadProcessor extends SimpleUploadProcessor
{
    final public const KEY_FIELD_FILE = 'key_file';

    final public const KEY_FIELD_FORM = 'key_form';

    public function handleUpload(Request $request, FormInterface $form = null, array $config = []): UploadResult
    {
        $config = [self::KEY_FIELD_FILE => 'file', self::KEY_FIELD_FORM => 'form', ...$config];

        return parent::handleUpload($request, $form, $config);
    }

    /**
     * @throws Exception|UploadException
     */
    public function handleRequest(Request $request): UploadResult
    {
        // Check that needed headers exists
        $this->checkHeaders($request, ['Content-Length', 'Content-Type']);
        if (!$request->files->has($this->config[self::KEY_FIELD_FILE])) {
            throw new UploadException(sprintf('%s file not found', $this->config[self::KEY_FIELD_FILE]));
        }

        $response = new UploadResult();
        $response->setRequest($request);
        $response->setConfig($this->config);

        if (null !== $this->form) {
            $response->setForm($this->form);

            if (!$request->request->has($this->config[self::KEY_FIELD_FORM])) {
                throw new UploadException(sprintf('%s request field not found in (%s)', $this->config[self::KEY_FIELD_FORM], implode(', ', $request->request->keys())));
            }

            $submittedValues = $request->request->all();
            $submittedValue = $submittedValues[$this->config[self::KEY_FIELD_FORM]] ?? null;
            if (is_string($submittedValue)) {
                $submittedValue = json_decode($submittedValue, true, 512, JSON_THROW_ON_ERROR);
                if (!$submittedValue) {
                    throw new UploadException('Unable to decode JSON');
                }
            } elseif (!is_array($submittedValue)) {
                throw new UploadException('Unable to parse form data');
            }

            // Submit form data
            $formData = $this->createFormData($submittedValue);
            $this->form->submit($formData);
            if (!$this->form->isValid()) {
                return $response;
            }
        }

        /** @var UploadedFile */
        $uploadedFile = $request->files->get($this->config[self::KEY_FIELD_FILE]);
        $contents = file_get_contents($uploadedFile->getPathname());
        $file = $this->storageHandler->store($response, $contents, ['metadata' => [FileStorage::METADATA_CONTENT_TYPE => $uploadedFile->getMimeType()]]);

        $response->setFile($file);

        return $response;
    }
}
