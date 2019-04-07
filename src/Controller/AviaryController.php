<?php

namespace Hgabka\MediaBundle\Controller;

use Hgabka\MediaBundle\Entity\Folder;
use Hgabka\MediaBundle\Entity\Media;
use Hgabka\MediaBundle\Helper\MediaManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller class which Aviary can use to upload the edited image and add it to the database.
 */
class AviaryController extends BaseMediaController
{
    /**
     * @param Request $request
     * @param int     $folderId The id of the Folder
     * @param int     $mediaId  The id of the image
     *
     * @Route("/aviary/{folderId}/{mediaId}", requirements={"folderId" = "\d+", "mediaId" = "\d+"}, name="HgabkaMediaBundle_aviary")
     *
     * @return RedirectResponse
     */
    public function indexAction(Request $request, $folderId, $mediaId)
    {
        $em = $this->getDoctrine()->getManager();

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
