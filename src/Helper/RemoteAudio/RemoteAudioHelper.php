<?php

namespace Hgabka\MediaBundle\Helper\RemoteAudio;

use Hgabka\MediaBundle\Entity\Media;
use Hgabka\MediaBundle\Helper\Remote\AbstractRemoteHelper;
use Hgabka\MediaBundle\Helper\Remote\RemoteInterface;

/**
 * Hgabka\MediaBundle\Entity\Audio
 * Class that defines audio in the system.
 */
class RemoteAudioHelper extends AbstractRemoteHelper implements RemoteInterface
{
    /**
     * @param Media $media
     */
    public function __construct(Media $media)
    {
        parent::__construct($media);
        $this->media->setContentType(RemoteAudioHandler::CONTENT_TYPE);
    }
}
