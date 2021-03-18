<?php

namespace Hgabka\MediaBundle\Helper\RemoteSlide;

use Hgabka\MediaBundle\Entity\Media;
use Hgabka\MediaBundle\Helper\Remote\AbstractRemoteHelper;
use Hgabka\MediaBundle\Helper\Remote\RemoteInterface;

/**
 * Hgabka\MediaBundle\Entity\Video
 * Class that defines a video in the system.
 */
class RemoteSlideHelper extends AbstractRemoteHelper implements RemoteInterface
{
    public function __construct(Media $media)
    {
        parent::__construct($media);
        $this->media->setContentType(RemoteSlideHandler::CONTENT_TYPE);
    }
}
