<?php

namespace SRIO\RestUploadBundle\Processor;

use SRIO\RestUploadBundle\Upload\UploadResult;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Upload processor interface.
 */
interface ProcessorInterface
{
    /**
     * Handle the upload request.
     */
    public function handleUpload(Request $request, FormInterface $form = null, array $options = []): UploadResult;
}
