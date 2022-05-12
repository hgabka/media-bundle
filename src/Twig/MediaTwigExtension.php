<?php

namespace Hgabka\MediaBundle\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Hgabka\MediaBundle\Entity\Folder;
use Hgabka\MediaBundle\Entity\Media;
use Hgabka\MediaBundle\Helper\FolderManager;
use Hgabka\MediaBundle\Helper\MediaManager;
use Symfony\Component\Security\Core\Security;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

class MediaTwigExtension extends AbstractExtension implements GlobalsInterface
{
    /** @var MediaManager */
    protected $mediaManager;

    /** @var EntityManagerInterface */
    protected $doctrine;

    /** @var Security */
    protected $security;

    /**
     * MediaTwigExtension constructor.
     */
    public function __construct(MediaManager $mediaManager, EntityManagerInterface $doctrine, Security $security)
    {
        $this->mediaManager = $mediaManager;
        $this->doctrine = $doctrine;
        $this->security = $security;
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
            new TwigFunction('is_folder_traversable', [$this, 'isFolderTraversable']),
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

    public function isFolderTraversable($folder): bool
    {
        if (!$folder instanceof Folder) {
            $folder = $this->doctrine->getRepository(Folder::class)->find($folder);
        }

        if (!$folder) {
            return false;
        }

        if ($this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return $folder->getLevel() > 1;
        }

        return !$folder->isInternal();
    }
}
