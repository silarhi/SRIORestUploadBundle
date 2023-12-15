<?php

namespace SRIO\RestUploadBundle\Strategy;

use SRIO\RestUploadBundle\Upload\UploadContext;

class DefaultNamingStrategy implements NamingStrategy
{
    public function getName(UploadContext $context): string
    {
        return uniqid();
    }
}
