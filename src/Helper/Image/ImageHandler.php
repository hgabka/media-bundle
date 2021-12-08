<?php

namespace Hgabka\MediaBundle\Helper\Image;

use Hgabka\MediaBundle\Entity\Media;
use Hgabka\MediaBundle\Helper\ExtensionGuesserFactoryInterface;
use Hgabka\MediaBundle\Helper\File\FileHandler;
use Hgabka\MediaBundle\Helper\MimeTypeGuesserFactoryInterface;
use Symfony\Component\HttpFoundation\File\File;

/**
 * FileHandler.
 */
class ImageHandler extends FileHandler
{
    protected $aviaryApiKey;

    /**
     * @param int    $priority
     * @param string $aviaryApiKey The aviary key
     */
    public function __construct($priority, $aviaryApiKey)
    {
        parent::__construct($priority);
        $this->aviaryApiKey = $aviaryApiKey;
    }

    /**
     * @return string
     */
    public function getAviaryApiKey()
    {
        return $this->aviaryApiKey;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'Image Handler';
    }

    /**
     * @return string
     */
    public function getType()
    {
        return 'image';
    }

    /**
     * @param mixed $object
     *
     * @return bool
     */
    public function canHandle($object)
    {
        if (parent::canHandle($object) && ($object instanceof File || 0 === strpos($object->getContentType(), 'image'))) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getShowTemplate(Media $media)
    {
        return '@HgabkaMedia/Media/Image/show.html.twig';
    }

    /**
     * @param Media  $media    The media entity
     * @param string $basepath The base path
     *
     * @return string
     */
    public function getImageUrl(Media $media, $basepath)
    {
        return $basepath.$media->getUrl();
    }

    public function prepareMedia(Media $media)
    {
        parent::prepareMedia($media);

        if ($media->getContent()) {
            $imageInfo = getimagesize($media->getContent());
            $width = $imageInfo[0];
            $height = $imageInfo[1];

            $media
                ->setMetadataValue('original_width', $width)
                ->setMetadataValue('original_height', $height);
        }
    }
}
