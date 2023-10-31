<?php

namespace SRIO\RestUploadBundle\Strategy;

use SRIO\RestUploadBundle\Upload\UploadContext;

interface StorageStrategy
{
    public function getDirectory(UploadContext $context, string $fileName): string;
}
