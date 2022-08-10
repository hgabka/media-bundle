<?php

namespace Hgabka\MediaBundle\Controller;

use Hgabka\MediaBundle\Entity\Media;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\Routing\Annotation\Route;

class PublicController extends BaseMediaController
{
    #[Route(
        '/download/{media}',
        name: 'HgabkaMediaBundle_media_download_attachment',
    )]
    public function download(Media $media, ParameterBagInterface $params)
    {
        return $this->getDownloadResponse($media, $params);
    }

    #[Route(
        '/download-inline/{media}',
        name: 'HgabkaMediaBundle_media_download_inline',
    )]
    public function inline(Media $media, ParameterBagInterface $params)
    {
        return $this->getDownloadResponse($media, $params, HeaderUtils::DISPOSITION_INLINE);
    }
}
