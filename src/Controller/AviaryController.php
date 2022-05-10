<?php

namespace Hgabka\MediaBundle\Controller;

use Hgabka\MediaBundle\Entity\Folder;
use Hgabka\MediaBundle\Entity\Media;
use Hgabka\MediaBundle\Helper\MediaManager;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller class which Aviary can use to upload the edited image and add it to the database.
 */
class AviaryController extends BaseMediaController
{
    #[Route(
        '/aviary/{folderId}/{mediaId}',
        name: 'HgabkaMediaBundle_aviary',
        requirements: ['folderId' => '\d+', 'mediaId' => '\d+']
    )]
    public function indexAction(Request $request, ManagerRegistry $doctrine, int $folderId, int $mediaId): Response
    {
        $em = $doctrine->getManager();

        // @var Folder $folder
        $folder = $em->getRepository(Folder::class)->getFolder($folderId);
        // @var Media $media
        $media = $em->getRepository(Media::class)->getMedia($mediaId);
        // @var MediaManager $mediaManager
        $mediaManager = $this->getManager();

        $media = clone $media;
        $handler = $mediaManager->getHandler($media);
        $fileHelper = $handler->getFormHelper($media);
        $fileHelper->getMediaFromUrl($request->get('url'));
        $media = $fileHelper->getMedia();

        $media->setUuid(null);
        $handler->prepareMedia($media);

        $em->persist($media);
        $em->flush();

        $media->setCreatedAt($media->getUpdatedAt());
        $em->persist($media);
        $em->flush();

        return new RedirectResponse(
            $this->generateUrl(
                'HgabkaMediaBundle_folder_show',
                ['folderId' => $folder->getId()]
            )
        );
    }
}
