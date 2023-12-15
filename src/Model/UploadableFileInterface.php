<?php

namespace SRIO\RestUploadBundle\Model;

use SRIO\RestUploadBundle\Storage\UploadedFile;

/**
 * A file object that will be uploaded with RestUploadBundle must implements
 * this interface.
 */
interface UploadableFileInterface
{
    /**
     * Set the uploaded file instance.
     */
    public function setFile(UploadedFile $file): void;
}
