<?php

namespace SRIO\RestUploadBundle\Processor;

use Exception;
use SRIO\RestUploadBundle\Exception\UploadException;
use SRIO\RestUploadBundle\Exception\UploadProcessorException;
use SRIO\RestUploadBundle\Storage\FileStorage;
use SRIO\RestUploadBundle\Upload\UploadResult;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class MultipartUploadProcessor extends AbstractUploadProcessor
{
    /**
     * @throws Exception|UploadException
     */
    public function handleRequest(Request $request): UploadResult
    {
        // Check that needed headers exists
        $this->checkHeaders($request);

        // Create the response
        $result = new UploadResult();
        $result->setRequest($request);
        $result->setConfig($this->config);
        $result->setForm($this->form);

        // Submit form data
        if (null != $this->form) {
            // Get formData
            $formData = $this->getFormData($request);
            $formData = $this->createFormData($formData);

            $this->form->submit($formData);
        }

        if (!$this->form instanceof FormInterface || $this->form->isValid()) {
            [$contentType, $content] = $this->getContent($request);

            $file = $this->storageHandler->store($result, $content, ['metadata' => [FileStorage::METADATA_CONTENT_TYPE => $contentType]]);

            $result->setFile($file);
        }

        return $result;
    }

    /**
     * Get the form data from the request.
     *
     * Note: MUST be called before getContent, and just one time.
     *
     * @throws UploadProcessorException
     */
    protected function getFormData(Request $request): array
    {
        [$boundaryContentType, $boundaryContent] = $this->getPart($request);

        $expectedContentType = 'application/json';
        if (!str_starts_with((string) $boundaryContentType, $expectedContentType)) {
            throw new UploadProcessorException(sprintf('Expected content type of first part is %s. Found %s', $expectedContentType, $boundaryContentType));
        }

        $jsonContent = json_decode((string) $boundaryContent, true, 512, JSON_THROW_ON_ERROR);
        if (null === $jsonContent) {
            throw new UploadProcessorException('Unable to parse JSON');
        }

        return $jsonContent;
    }

    /**
     * Get the content part of the request.
     *
     * Note: MUST be called after getFormData, and just one time.
     */
    protected function getContent(Request $request): array
    {
        return $this->getPart($request);
    }

    /**
     * Check multipart headers.
     *
     * @throws UploadProcessorException
     */
    protected function checkHeaders(Request $request, array $headers = []): void
    {
        [$contentType] = $this->parseContentTypeAndBoundary($request);

        $expectedContentType = 'multipart/related';
        if ($contentType != $expectedContentType) {
            throw new UploadProcessorException(sprintf('Content-Type must be %s', $expectedContentType));
        }

        parent::checkHeaders($request, ['Content-Type', 'Content-Length']);
    }

    /**
     * Get a part of request.
     *
     * @throws UploadProcessorException
     */
    protected function getPart(Request $request): array
    {
        [$contentType, $boundary] = $this->parseContentTypeAndBoundary($request);
        $content = $this->getRequestPart($request, $boundary);

        if ('' === $content) {
            throw new UploadProcessorException('An empty content found');
        }

        $headerLimitation = strpos($content, "\r\n\r\n");
        if (false == $headerLimitation) {
            throw new UploadProcessorException('Unable to determine headers limit');
        }

        ++$headerLimitation;

        $headersContent = substr($content, 0, $headerLimitation);
        $headersContent = trim($headersContent);

        $body = substr($content, $headerLimitation);
        $body = trim($body);

        foreach (explode("\r\n", $headersContent) as $header) {
            $parts = explode(':', $header);
            if (2 != count($parts)) {
                continue;
            }

            $name = trim($parts[0]);
            if ('content-type' == strtolower($name)) {
                $contentType = trim($parts[1]);
                break;
            }
        }

        return [$contentType, $body];
    }

    /**
     * Get part of a resource.
     *
     * @throws UploadProcessorException
     */
    protected function getRequestPart(Request $request, string $boundary): string
    {
        $contentHandler = $this->getRequestContentHandler($request);

        $delimiter = '--'.$boundary."\r\n";
        $endDelimiter = '--'.$boundary.'--';
        $boundaryCount = 0;
        $content = '';
        while (!$contentHandler->eof()) {
            $line = $contentHandler->gets();
            if (false === $line) {
                throw new UploadProcessorException('An error appears while reading input');
            }

            if (0 === $boundaryCount) {
                if ($line !== $delimiter) {
                    if ($contentHandler->getCursor() == strlen($line)) {
                        throw new UploadProcessorException('Expected boundary delimiter');
                    }
                } else {
                    continue;
                }

                ++$boundaryCount;
            } elseif ($line === $delimiter) {
                break;
            } elseif ($line === $endDelimiter || $line === $endDelimiter."\r\n") {
                break;
            }

            $content .= $line;
        }

        return trim($content);
    }

    /**
     * Parse the content type and boudary from Content-Type header.
     *
     * @throws UploadProcessorException
     */
    protected function parseContentTypeAndBoundary(Request $request): array
    {
        $contentParts = explode(';', $request->headers->get('Content-Type'));
        if (2 != count($contentParts)) {
            throw new UploadProcessorException('Boundary may be missing');
        }

        $contentType = trim($contentParts[0]);
        $boundaryPart = trim($contentParts[1]);

        $shouldStart = 'boundary=';
        if (!str_starts_with($boundaryPart, $shouldStart)) {
            throw new UploadProcessorException('Boundary is not set');
        }

        $boundary = substr($boundaryPart, strlen($shouldStart));
        if (str_starts_with($boundary, '"') && str_ends_with($boundary, '"')) {
            $boundary = substr($boundary, 1, -1);
        }

        return [$contentType, $boundary];
    }
}
