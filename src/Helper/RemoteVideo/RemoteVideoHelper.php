<?php

namespace Hgabka\MediaBundle\Helper\RemoteVideo;

use Hgabka\MediaBundle\Entity\Media;
use Hgabka\MediaBundle\Helper\Remote\AbstractRemoteHelper;
use Hgabka\MediaBundle\Helper\Remote\RemoteInterface;

/**
 * Hgabka\MediaBundle\Entity\Video
 * Class that defines a video in the system.
 */
class RemoteVideoHelper extends AbstractRemoteHelper implements RemoteInterface
{
    /**
     * @param Media $media
     */
    public function __construct(Media $media)
    {
        parent::__construct($media);
        $this->media->setContentType(RemoteVideoHandler::CONTENT_TYPE);
    }
}
