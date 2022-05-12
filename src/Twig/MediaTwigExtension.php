<?php

namespace Hgabka\MediaBundle\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Hgabka\MediaBundle\Entity\Folder;
use Hgabka\MediaBundle\Entity\Media;
use Hgabka\MediaBundle\Helper\FolderManager;
use Hgabka\MediaBundle\Helper\MediaManager;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

class MediaTwigExtension extends AbstractExtension implements GlobalsInterface
{
    /** @var MediaManager */
    protected MediaManager $mediaManager;

    protected EntityManagerInterface $doctrine;

    protected FolderManager $folderManager;

    /**
     * MediaTwigExtension constructor.
     */
    public function __construct(MediaManager $mediaManager, EntityManagerInterface $doctrine, FolderManager $folderManager)
    {
        $this->mediaManager = $mediaManager;
        $this->doctrine = $doctrine;
        $this->folderManager = $folderManager;
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

    public function getMediaForFolder(Folder $folder, ?string $orderByField = null, string $orderDirection = 'ASC')
    {
        return $this->doctrine->getRepository(Media::class)->getMediaForFolder($folder, $orderByField, $orderDirection);
    }

    public function getLastMediaForFolder(Folder $folder): ?Media
    {
        $media = $this->doctrine->getRepository(Media::class)->getMediaForFolder($folder, 'createdAt', 'DESC');

        return empty($media) ? null : current($media);
    }

    public function getFolderByInternalName(string $internalName): ?Folder
    {
        return $this->doctrine->getRepository(Folder::class)->findOneByInternalName($internalName);
    }

    public function isFolderTraversable(Folder|int|string $folder): bool
    {
        return $this->folderManager->isFolderTraversable($folder);
    }
}
