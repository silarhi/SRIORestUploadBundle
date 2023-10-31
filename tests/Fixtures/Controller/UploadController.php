<?php

namespace SRIO\RestUploadBundle\Tests\Fixtures\Controller;

use Doctrine\ORM\EntityManagerInterface;
use SRIO\RestUploadBundle\Storage\UploadedFile;
use SRIO\RestUploadBundle\Tests\Fixtures\Entity\Media;
use SRIO\RestUploadBundle\Tests\Fixtures\Form\Type\MediaFormType;
use SRIO\RestUploadBundle\Upload\UploadHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\Routing\Annotation\Route;

class UploadController extends AbstractController
{
    /**
     * @Route("/upload", methods={"POST", "GET", "PUT"})
     */
    public function uploadAction(Request $request, UploadHandler $uploadHandler, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MediaFormType::class);

        $result = $uploadHandler->handleRequest($request, $form);

        if (($response = $result->getResponse()) instanceof Response) {
            return $response;
        }

        if (!$form->isSubmitted() || !$form->isValid()) {
            throw new BadRequestHttpException();
        }

        if (($file = $result->getFile()) instanceof UploadedFile) {
            /** @var Media */
            $media = $form->getData();
            $media->setFile($file);

            $entityManager->persist($media);
            $entityManager->flush();

            return new JsonResponse($media);
        }

        throw new NotAcceptableHttpException();
    }
}
