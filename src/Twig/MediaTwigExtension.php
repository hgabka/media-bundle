<?php

namespace Hgabka\MediaBundle\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Hgabka\MediaBundle\Entity\Folder;
use Hgabka\MediaBundle\Entity\Media;
use Hgabka\MediaBundle\Helper\MediaManager;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

class MediaTwigExtension extends AbstractExtension implements GlobalsInterface
{
    /** @var MediaManager */
    protected $mediaManager;

    /** @var EntityManagerInterface */
    protected $doctrine;

    /**
     * MediaTwigExtension constructor.
     */
    public function __construct(MediaManager $mediaManager, EntityManagerInterface $doctrine)
    {
        $this->mediaManager = $mediaManager;
        $this->doctrine = $doctrine;
    }

    public function getGlobals(): array
    {
        return [
            'mediaManager' => $this->mediaManager,
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_media_for_folder', [$this, 'getMediaForFolder']),
            new TwigFunction('get_last_media_for_folder', [$this, 'getLastMediaForFolder']),
            new TwigFunction('get_folder_by_internal_name', [$this, 'getFolderByInternalName']),
        ];
    }

    public function getMediaForFolder($folder, $orderByField = null, $orderDirection = 'ASC')
    {
        return $this->doctrine->getRepository(Media::class)->getMediaForFolder($folder, $orderByField, $orderDirection);
    }

    public function getLastMediaForFolder($folder)
    {
        $media = $this->doctrine->getRepository(Media::class)->getMediaForFolder($folder, 'createdAt', 'DESC');

        return empty($media) ? null : current($media);
    }

    public function getFolderByInternalName($internalName)
    {
        return $this->doctrine->getRepository(Folder::class)->findOneByInternalName($internalName);
    }
}
