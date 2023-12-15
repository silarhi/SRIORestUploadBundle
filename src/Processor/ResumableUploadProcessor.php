<?php

namespace SRIO\RestUploadBundle\Processor;

use Doctrine\ORM\EntityManagerInterface;
use SRIO\RestUploadBundle\Exception\UploadException;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManager;
use Exception;
use SRIO\RestUploadBundle\Entity\ResumableUploadSession;
use SRIO\RestUploadBundle\Exception\UploadProcessorException;
use SRIO\RestUploadBundle\Storage\FileAdapterInterface;
use SRIO\RestUploadBundle\Storage\FileStorage;
use SRIO\RestUploadBundle\Storage\UploadedFile;
use SRIO\RestUploadBundle\Upload\StorageHandler;
use SRIO\RestUploadBundle\Upload\UploadContext;
use SRIO\RestUploadBundle\Upload\UploadResult;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ResumableUploadProcessor extends AbstractUploadProcessor
{
    /**
     * @var string
     */
    final public const PARAMETER_UPLOAD_ID = 'uploadId';

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * Constructor.
     *
     * @param string $resumableEntity
     */
    public function __construct(StorageHandler $storageHandler, EntityManagerInterface $em, protected $resumableEntity)
    {
        parent::__construct($storageHandler);

        $this->em = $em;
    }

    /**
     * @throws Exception|UploadException
     */
    public function handleRequest(Request $request): UploadResult
    {
        if (empty($this->resumableEntity)) {
            throw new UploadProcessorException(sprintf('You must configure the "%s" option', 'resumable_entity'));
        }

        if ($request->query->has(self::PARAMETER_UPLOAD_ID)) {
            $this->checkHeaders($request, ['Content-Length']);

            $uploadId = $request->query->get(self::PARAMETER_UPLOAD_ID);

            $repository = $this->getRepository();
            $resumableUpload = $repository->findOneBy(['sessionId' => $uploadId]);

            if (null == $resumableUpload) {
                throw new UploadProcessorException('Unable to find upload session');
            }

            return $this->handleResume($request, $resumableUpload);
        }

        return $this->handleStartSession($request);
    }

    /**
     * Handle a start session.
     *
     * @throws UploadProcessorException
     */
    protected function handleStartSession(Request $request): UploadResult
    {
        // Check that needed headers exists
        $this->checkHeaders($request, ['Content-Type', 'X-Upload-Content-Type', 'X-Upload-Content-Length']);
        $expectedContentType = 'application/json';
        if (!str_starts_with($request->headers->get('Content-Type'), $expectedContentType)) {
            throw new UploadProcessorException(sprintf('Expected content type is %s. Found %s', $expectedContentType, $request->headers->get('Content-Type')));
        }

        // Create the result object
        $result = new UploadResult();
        $result->setRequest($request);
        $result->setConfig($this->config);
        $result->setForm($this->form);

        $formData = [];
        if (null != $this->form) {
            // Submit form data
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $formData = $this->createFormData($data);
            $this->form->submit($formData);
        }

        if (null == $this->form || $this->form->isValid()) {
            // Form is valid, store it
            $repository = $this->getRepository();
            $className = $repository->getClassName();

            // Create file from storage handler
            $file = $this->storageHandler->store($result, '', ['metadata' => [FileStorage::METADATA_CONTENT_TYPE => $request->headers->get('X-Upload-Content-Type')]]);

            /** @var $resumableUpload ResumableUploadSession */
            $resumableUpload = new $className();

            $resumableUpload->setData(serialize($formData));
            $resumableUpload->setStorageName($file->getStorage()->getName());
            $resumableUpload->setFilePath($file->getFile()->getName());
            $resumableUpload->setSessionId($this->createSessionId());
            $resumableUpload->setContentType($request->headers->get('X-Upload-Content-Type'));
            $resumableUpload->setContentLength($request->headers->get('X-Upload-Content-Length'));

            // Store resumable session
            $this->em->persist($resumableUpload);
            $this->em->flush($resumableUpload);

            // Compute redirect location path
            $location = $request->getPathInfo().'?'.http_build_query(array_merge($request->query->all(), [self::PARAMETER_UPLOAD_ID => $resumableUpload->getSessionId()]));

            $response = new Response(null);
            $response->headers->set('Location', $location);

            $result->setResponse($response);
        }

        return $result;
    }

    /**
     * Handle an upload resume.
     *
     * @throws UploadProcessorException
     */
    protected function handleResume(Request $request, ResumableUploadSession $uploadSession): UploadResult
    {
        $filePath = $uploadSession->getFilePath();

        $context = new UploadContext();
        $context->setStorageName($uploadSession->getStorageName());

        $file = $this->storageHandler->getFilesystem($context)->get($filePath);
        $context->setFile(new UploadedFile(
            $this->storageHandler->getStorage($context),
            $file
        ));

        $contentLength = $request->headers->get('Content-Length');
        if ($request->headers->has('Content-Range')) {
            $range = $this->parseContentRange($request->headers->get('Content-Range'));

            if ($range['total'] != $uploadSession->getContentLength()) {
                throw new UploadProcessorException(sprintf('File size must be "%d", range total length is %d', $uploadSession->getContentLength(), $range['total']));
            } elseif ('*' === $range['start']) {
                if (0 == $contentLength) {
                    $file = $this->storageHandler->getFilesystem($context)->get($filePath);

                    return $this->requestUploadStatus($context, $uploadSession, $file, $range);
                }

                throw new UploadProcessorException('Content-Length must be 0 if asking upload status');
            }

            $uploaded = $this->storageHandler->getFilesystem($context)->getSize($filePath);
            if ($range['start'] != $uploaded) {
                throw new UploadProcessorException(sprintf('Unable to start at %d while uploaded is %d', $range['start'], $uploaded));
            }
        } else {
            $range = ['start' => 0, 'end' => $uploadSession->getContentLength() - 1, 'total' => $uploadSession->getContentLength() - 1];
        }

        // Handle upload from
        $handler = $this->getRequestContentHandler($request);
        $stream = $this->storageHandler->getFilesystem($context)->getStreamCopy($filePath);

        fseek($stream, $range['start']);
        $wrote = 0;
        while (!$handler->eof()) {
            if (($bytes = fwrite($stream, $handler->gets())) !== false) {
                $wrote += $bytes;
            } else {
                throw new UploadProcessorException('Unable to write to file');
            }
        }

        // Get file in context and its size
        $uploadedFile = $this->storageHandler->storeStream($context, $stream, ['metadata' => [FileStorage::METADATA_CONTENT_TYPE => $request->headers->get('X-Upload-Content-Type')]], true);
        fclose($stream);

        $file = $uploadedFile->getFile();
        $size = $file->getSize();

        // If upload is completed, create the upload file, else
        // return like the request upload status
        if ($size < $uploadSession->getContentLength()) {
            return $this->requestUploadStatus($context, $uploadSession, $file, $range);
        } elseif ($size == $uploadSession->getContentLength()) {
            return $this->handleCompletedUpload($context, $uploadSession, $file);
        } else {
            throw new UploadProcessorException('Written file size is greater that expected Content-Length');
        }
    }

    /**
     * Handle a completed upload.
     */
    protected function handleCompletedUpload(UploadContext $context, ResumableUploadSession $uploadSession, FileAdapterInterface $file): UploadResult
    {
        $result = new UploadResult();
        $result->setForm($this->form);

        if (null != $this->form) {
            // Submit the form data
            $formData = unserialize($uploadSession->getData());
            $this->form->submit($formData);
        }

        if (null == $this->form || $this->form->isValid()) {
            // Create the uploaded file
            $uploadedFile = new UploadedFile(
                $this->storageHandler->getStorage($context),
                $file
            );

            $result->setFile($uploadedFile);
        }

        return $result;
    }

    /**
     * Return the upload status.
     */
    protected function requestUploadStatus(UploadContext $context, ResumableUploadSession $uploadSession, FileAdapterInterface $file, array $range): UploadResult
    {
        $length = $file->exists() ? $file->getSize() : 0;

        $response = new Response(null, $length == $range['total'] ? 201 : 308);

        if ($length < 1) {
            $length = 1;
        }

        $response->headers->set('Range', '0-'.($length - 1));

        $result = new UploadResult();
        $result->setResponse($response);

        return $result;
    }

    /**
     * Parse the Content-Range header.
     *
     * It returns an array with these keys:
     * - `start` Start index of range
     * - `end`   End index of range
     * - `total` Total number of bytes
     *
     * @param string $contentRange
     *
     * @throws UploadProcessorException
     */
    protected function parseContentRange($contentRange): array
    {
        $contentRange = trim($contentRange);
        if (!preg_match('#^bytes (\*|(\d+)-(\d+))/(\d+)$#', $contentRange, $matches)) {
            throw new UploadProcessorException('Invalid Content-Range header. Must start with "bytes ", range and total length');
        }

        $range = ['start' => '*' === $matches[1] ? '*' : ('' === $matches[2] ? null : (int) $matches[2]), 'end' => '' === $matches[3] ? null : (int) $matches[3], 'total' => (int) $matches[4]];

        if (empty($range['total'])) {
            throw new UploadProcessorException('Content-Range total length not found');
        }

        if ('*' === $range['start']) {
            if (null !== $range['end']) {
                throw new UploadProcessorException('Content-Range end must not be present if start is "*"');
            }
        } elseif (null === $range['start'] || null === $range['end']) {
            throw new UploadProcessorException('Content-Range end or start is empty');
        } elseif ($range['start'] > $range['end']) {
            throw new UploadProcessorException('Content-Range start must be lower than end');
        } elseif ($range['end'] > $range['total']) {
            throw new UploadProcessorException('Content-Range end must be lower or equal to total length');
        }

        return $range;
    }

    /**
     * Get resumable upload session entity repository.
     */
    protected function getRepository(): EntityRepository
    {
        return $this->em->getRepository($this->resumableEntity);
    }

    /**
     * Create a session ID.
     */
    protected function createSessionId(): string
    {
        return uniqid();
    }
}
