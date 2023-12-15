<?php

namespace SRIO\RestUploadBundle\Tests\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;
use SRIO\RestUploadBundle\Model\UploadableFileInterface;
use SRIO\RestUploadBundle\Storage\UploadedFile;

/**
 * @ORM\Entity
 *
 * @ORM\Table(name="media")
 */
class Media implements UploadableFileInterface
{
    /**
     * @ORM\Id
     *
     * @ORM\Column(type="integer")
     *
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public ?int $id = null;

    /**
     * @ORM\Column(type="string")
     */
    public ?string $name = null;

    /**
     * @ORM\Column(type="string")
     */
    public ?string $mimeType = null;

    /**
     * @ORM\Column(type="string")
     */
    public ?string $path = null;

    /**
     * @ORM\Column(type="integer")
     */
    public ?int $size = null;

    /**
     * @ORM\Column(type="string")
     */
    public ?string $originalName = null;

    /**
     * Set uploaded file.
     */
    public function setFile(UploadedFile $uploaded): void
    {
        $this->path = $uploaded->getFile()->getName();
        $this->size = $uploaded->getFile()->getSize();

        // TODO Add mimetype on `UploadedFile`
        $this->mimeType = $uploaded->getStorage()->getFilesystem()->getMimeType($this->path);

        // TODO Add original name
        $this->originalName = $uploaded->getFile()->getName();
    }
}
